<?php
namespace App\DTOs;

class MulticastResult
{
    public int $successCount;
    public int $failureCount;
    public array $invalidTokens; // tokens that need deletion

    public function __construct(int $successCount = 0, int $failureCount = 0, array $invalidTokens = [])
    {
        $this->successCount  = $successCount;
        $this->failureCount  = $failureCount;
        $this->invalidTokens = $invalidTokens;
    }
}
