<?php

namespace App\DTO;

/**
 * Immutable bag of raw fields extracted from a message.
 */
class FieldBag
{
    private array $fields;

    public function __construct(array $fields = [])
    {
        $this->fields = $fields;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->fields[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->fields[$key]);
    }

    public function all(): array
    {
        return $this->fields;
    }
}
