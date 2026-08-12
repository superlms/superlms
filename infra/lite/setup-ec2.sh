#!/usr/bin/env bash
# One-shot bootstrap for the lite single-box, on a FRESH Amazon Linux 2023
# (arm64) EC2 instance. Run as ec2-user:
#
#   curl -fsSL https://raw.githubusercontent.com/<you>/superlms/main/infra/lite/setup-ec2.sh | bash
#   # ...or scp this file over and: bash setup-ec2.sh
#
# It installs Docker + compose, adds swap, clones the repo, and starts the
# stack. You still must create infra/lite/.env before the app is usable
# (the script pauses and tells you).
set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/superlms/superlms.git}"   # <-- set to your repo
BRANCH="${BRANCH:-main}"
APP_DIR="/opt/superlms"

echo "==> [1/6] System packages + Docker"
sudo dnf -y update
sudo dnf -y install docker git
sudo systemctl enable --now docker
sudo usermod -aG docker ec2-user

echo "==> [2/6] Docker Compose v2 plugin (arm64)"
COMPOSE_VER="v2.29.7"
sudo mkdir -p /usr/libexec/docker/cli-plugins
sudo curl -fsSL \
  "https://github.com/docker/compose/releases/download/${COMPOSE_VER}/docker-compose-linux-aarch64" \
  -o /usr/libexec/docker/cli-plugins/docker-compose
sudo chmod +x /usr/libexec/docker/cli-plugins/docker-compose

echo "==> [3/6] 4 GB swap (safety net so builds/exports never OOM the 4 GB box)"
if ! sudo swapon --show | grep -q /swapfile; then
  sudo dd if=/dev/zero of=/swapfile bs=1M count=4096 status=progress
  sudo chmod 600 /swapfile
  sudo mkswap /swapfile
  sudo swapon /swapfile
  echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
  echo 'vm.swappiness=10' | sudo tee /etc/sysctl.d/99-swappiness.conf
  sudo sysctl -p /etc/sysctl.d/99-swappiness.conf || true
fi

echo "==> [4/6] Clone the repo to ${APP_DIR}"
if [ ! -d "${APP_DIR}/.git" ]; then
  sudo git clone --branch "${BRANCH}" "${REPO_URL}" "${APP_DIR}"
fi
sudo chown -R ec2-user:ec2-user "${APP_DIR}"

cd "${APP_DIR}/infra/lite"

echo "==> [5/6] Environment file"
if [ ! -f .env ]; then
  cp .env.prod.example .env
  chmod 600 .env
  cat <<'MSG'

  ⚠️  ACTION NEEDED: infra/lite/.env was created from the template.
      Edit it now and fill in APP_KEY, DB passwords, AWS_*, ZeptoMail,
      PhonePe, Firebase, etc.:

        nano /opt/superlms/infra/lite/.env

      Then finish with:

        cd /opt/superlms/infra/lite
        newgrp docker         # or log out/in so 'docker' works without sudo
        ./deploy.sh           # builds the image, starts everything, migrates

MSG
  exit 0
fi

echo "==> [6/6] .env present — building + starting the stack"
exec ./deploy.sh
