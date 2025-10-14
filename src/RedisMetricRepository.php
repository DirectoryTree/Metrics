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
        protected Repository $config,
        protected RedisFactory $redis,
        protected MeasurableEncoder $encoder,
    ) {}

    /**
     * Add a cached metric to be committed.
     */
    public function add(Measurable $metric): void
    {
        $key = $this->encoder->encode($metric);

        $this->connection()->pipeline(function ($pipe) use ($key, $metric) {
            // Increment the metric value.
            $pipe->hincrby($this->key(), $key, $metric->value());

            // Set or bump the expiration time.
            $pipe->expire($this->key(), $this->config->get('metrics.redis.ttl'));
        });
    }

    /**
     * Get all cached metrics.
     *
     * @return Measurable[]
     */
    public function all(): array
    {
        $metrics = $this->connection()->hgetall($this->key());

        return array_map(function (string $value, string $field) {
            return $this->encoder->decode($field, (int) $value);
        }, array_values($metrics), array_keys($metrics));
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
        return $this->config->get('metrics.redis.key') ?? 'metrics:pending';
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
