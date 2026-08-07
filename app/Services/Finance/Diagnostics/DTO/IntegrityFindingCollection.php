<?php

namespace App\Services\Finance\Diagnostics\DTO;

use Illuminate\Support\Collection;

class IntegrityFindingCollection
{
    private Collection $findings;

    public function __construct(array $findings = [])
    {
        $this->findings = collect($findings);
    }

    public function add(IntegrityFinding $finding): void
    {
        $this->findings->push($finding);
    }

    public function all(): Collection
    {
        return $this->findings;
    }

    public function merge(IntegrityFindingCollection $other): self
    {
        return new self($this->findings->merge($other->all())->all());
    }

    public function count(): int
    {
        return $this->findings->count();
    }
}
