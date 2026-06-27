# Phase 4 — VPC / Network

One CloudFormation stack (`superlms-network`) for all networking. Region
`ap-south-1`, 2 AZ, **no NAT gateway** (saves ~$32/mo).

## Run

```powershell
cd infra/vpc
./deploy.ps1
```

Idempotent — re-run to apply changes. Tear down with:

```powershell
aws cloudformation delete-stack --stack-name superlms-network --region ap-south-1
```

> Delete only works once later stacks (RDS/ECS) that import these exports
> are gone — CloudFormation blocks deleting an export still in use.

## Layout

```
VPC 10.0.0.0/16  (DNS hostnames on — needed for ECR/private DNS)
├── Public  10.0.0.0/24 (az a) + 10.0.1.0/24 (az b)  -> Internet Gateway
│     hosts: ALB, Fargate tasks (public IP, pull from ECR free via IGW)
└── Private 10.0.10.0/24 (az a) + 10.0.11.0/24 (az b)  -> no internet
      hosts: RDS MySQL, ElastiCache Redis
```

### Why tasks in public subnets (and not a NAT)?
Fargate tasks must reach ECR/CloudWatch/Secrets over the internet. A NAT
gateway costs ~$32/mo + data. Instead tasks run in public subnets with a
public IP and egress through the free Internet Gateway. They stay safe
because the **ECS security group only allows inbound from the ALB** — the
public IP is not reachable from outside on the app port.

## Security group chain

| SG | Inbound | From |
|----|---------|------|
| `superlms-alb-sg` | 80, 443 | `0.0.0.0/0` (internet) |
| `superlms-ecs-sg` | container port (80) | ALB SG only |
| `superlms-rds-sg` | 3306 | ECS SG only |
| `superlms-redis-sg` | 6379 | ECS SG only |

(Egress is the SG default = allow all.)

## Exports (consumed by later phases via `Fn::ImportValue`)

| Export name | Used by |
|-------------|---------|
| `superlms-vpc-id` | RDS, ElastiCache, ECS |
| `superlms-public-subnets` | ALB, ECS services |
| `superlms-private-subnets` | RDS subnet group, ElastiCache subnet group |
| `superlms-alb-sg` / `superlms-ecs-sg` / `superlms-rds-sg` / `superlms-redis-sg` | respective resources |
