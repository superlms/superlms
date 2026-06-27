# Phase 7 - ElastiCache Redis

Stack `edyonelms-redis`: Redis 7, `cache.t4g.micro`, single node, private
subnets, reachable only from the ECS security group. Backs cache + sessions +
queue (`CACHE_STORE` / `SESSION_DRIVER` / `QUEUE_CONNECTION` = redis).

## Run

```powershell
cd infra/redis
.\deploy.ps1
```

Takes ~5-8 min. Requires the Phase 4 VPC stack.

## App wiring (Phase 8)

| Export | App env |
|--------|---------|
| `edyonelms-redis-host` | `REDIS_HOST` |
| `edyonelms-redis-port` | `REDIS_PORT` (6379) |

`REDIS_PASSWORD` stays empty - the node is private and SG-restricted (no auth
token). To add auth later, switch to a replication group with `AuthToken`.

## Notes

- Single node now (cost). For HA: use `AWS::ElastiCache::ReplicationGroup`
  with automatic failover and 2 nodes.
