<?php
namespace App\DTOs;

class MulticastResult
{
    public function __construct(
        public int $successCount = 0,
        public int $failureCount = 0,
        public array $invalidTokens = [],
    ) {}
}
