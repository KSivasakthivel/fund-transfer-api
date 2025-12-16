<?php

namespace App\Service\Observability;

class InMemoryMetricsCollector implements MetricsCollectorInterface
{
    private array $metrics = [];

    public function increment(string $metric, int $value = 1, array $tags = []): void
    {
        $key = $this->buildKey($metric, 'counter', $tags);
        
        if (!isset($this->metrics[$key])) {
            $this->metrics[$key] = [
                'type' => 'counter',
                'name' => $metric,
                'value' => 0,
                'tags' => $tags,
            ];
        }

        $this->metrics[$key]['value'] += $value;
    }

    public function timing(string $metric, float $duration, array $tags = []): void
    {
        $key = $this->buildKey($metric, 'timing', $tags);
        
        if (!isset($this->metrics[$key])) {
            $this->metrics[$key] = [
                'type' => 'timing',
                'name' => $metric,
                'values' => [],
                'tags' => $tags,
            ];
        }

        $this->metrics[$key]['values'][] = $duration;
    }

    public function gauge(string $metric, float $value, array $tags = []): void
    {
        $key = $this->buildKey($metric, 'gauge', $tags);
        
        $this->metrics[$key] = [
            'type' => 'gauge',
            'name' => $metric,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => time(),
        ];
    }

    public function getMetrics(): array
    {
        return array_values($this->metrics);
    }

    private function buildKey(string $metric, string $type, array $tags): string
    {
        ksort($tags);
        $tagString = http_build_query($tags);
        return "{$type}:{$metric}:{$tagString}";
    }
}
