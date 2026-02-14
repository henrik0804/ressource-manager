<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConflictType;
use Illuminate\Support\Collection;

final class ConflictReport
{
    /**
     * @var array<string, list<array{related_ids: list<int>, metrics?: array<string, float|int|string|null>}>>
     */
    private array $conflicts = [];

    /**
     * @param  array{related_ids: list<int>, metrics?: array<string, float|int|string|null>}  $conflict
     */
    public function add(ConflictType $type, array $conflict): void
    {
        $this->conflicts[$type->value][] = $conflict;
    }

    public function hasConflicts(): bool
    {
        return $this->conflicts !== [];
    }

    /**
     * @return list<ConflictType>
     */
    public function types(): array
    {
        return array_map(
            ConflictType::from(...),
            array_keys($this->conflicts),
        );
    }

    /**
     * @return Collection<int, array{related_ids: list<int>, metrics?: array<string, float|int|string|null>}>
     */
    public function conflictsFor(ConflictType $type): Collection
    {
        /** @phpstan-ignore return.type (Collection template covariance limitation) */
        return new Collection($this->conflicts[$type->value] ?? []);
    }

    /**
     * @return array<string, array{label: string, description: string, entries: list<array{related_ids: list<int>, metrics?: array<string, float|int|string|null>}>}>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->types() as $type) {
            $result[$type->value] = [
                'label' => $type->label(),
                'description' => $type->description(),
                'entries' => $this->conflictsFor($type)->all(),
            ];
        }

        return $result;
    }
}
