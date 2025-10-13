<?php

namespace DirectoryTree\Metrics;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;

class RedisMetricRepository implements MetricRepository
{
    /**
     * Constructor.
     */
    public function __construct(
        protected RedisFactory $redis,
        protected Repository $config,
    ) {}

    /**
     * Add a cached metric to be committed.
     */
    public function add(Measurable $metric): void
    {
        $this->connection()->rpush($this->key(), serialize($metric));
    }

    /**
     * Get all cached metrics.
     *
     * @return Measurable[]
     */
    public function all(): array
    {
        return array_map(function (string $serialized) {
            return unserialize($serialized);
        }, $this->connection()->lrange($this->key(), 0, -1));
    }

    /**
     * Flush all cached metrics.
     */
    public function flush(): void
    {
        $this->connection()->del($this->key());
    }

    /**
     * Get the Redis key for storing metrics.
     */
    protected function key(): string
    {
        return $this->config->get('metrics.redis.key', 'metrics:pending');
    }

    /**
     * Resolve the Redis connection.
     */
    protected function connection(): Connection
    {
        return $this->redis->connection(
            $this->config->get('metrics.redis.connection')
        );
    }
}
