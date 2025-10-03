<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Contract;

/**
 * Metrics interface for lightweight counters and timings.
 * Implementations can bridge to Prometheus/StatsD/etc.
 */
interface MetricsInterface
{
    /**
     * Increment a named counter by a value (default 1).
     *
     * @param  array<string,string|int|float>  $tags
     */
    public function increment(string $name, float $by = 1.0, array $tags = []): void;

    /**
     * Observe a duration in seconds for a named timer/histogram.
     *
     * @param  array<string,string|int|float>  $tags
     */
    public function observe(string $name, float $seconds, array $tags = []): void;

    /**
     * Record a size (bytes) for a named measure.
     *
     * @param  array<string,string|int|float>  $tags
     */
    public function size(string $name, int $bytes, array $tags = []): void;
}
