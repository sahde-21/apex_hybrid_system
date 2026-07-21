<?php

namespace App\Support\Workflow;

use InvalidArgumentException;

final class WorkflowDefinition
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public readonly string $key,
        public readonly array $config,
    ) {}

    public static function fromConfig(string $key): self
    {
        $config = config('workflows.definitions.'.$key);

        if (! is_array($config)) {
            throw new InvalidArgumentException("Unknown workflow definition [{$key}].");
        }

        return new self($key, $config);
    }

    public function label(): string
    {
        return (string) ($this->config['label'] ?? $this->key);
    }

    public function initialStatus(): string
    {
        return (string) ($this->config['initial_status'] ?? 'draft');
    }

    public function statusColumn(): string
    {
        return (string) ($this->config['status_column'] ?? 'status');
    }

    public function module(): string
    {
        return (string) ($this->config['module'] ?? 'workflow');
    }

    public function showRoute(): ?string
    {
        $route = $this->config['show_route'] ?? null;

        return is_string($route) && $route !== '' ? $route : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function transitions(): array
    {
        /** @var array<string, array<string, mixed>> */
        return $this->config['transitions'] ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function transition(string $action): ?array
    {
        return $this->transitions()[$action] ?? null;
    }

    /**
     * @return list<string>
     */
    public function availableActionsFor(string $status): array
    {
        $actions = [];

        foreach ($this->transitions() as $action => $rule) {
            $from = $rule['from'] ?? [];
            if (in_array($status, $from, true)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }
}
