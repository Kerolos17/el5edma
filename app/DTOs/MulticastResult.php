<?php
namespace App\DTOs;

class MulticastResult
{
    public function __construct(
        public readonly int $successCount = 0,
        public readonly int $failureCount = 0,
        public readonly array $invalidTokens = [],
    ) {}
}
