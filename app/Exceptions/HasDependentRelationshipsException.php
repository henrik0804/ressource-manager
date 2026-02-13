<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class HasDependentRelationshipsException extends RuntimeException
{
    /**
     * @param  array<string, int>  $dependents
     */
    public function __construct(
        public readonly array $dependents,
        string $message = 'This record has dependent relationships that must be confirmed for deletion.',
    ) {
        parent::__construct($message);
    }
}
