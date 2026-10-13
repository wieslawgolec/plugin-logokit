<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Tests\Unit\Helper;

use MauticPlugin\MauticLogokitBundle\Services\LogokitClient;

/**
 * Test double that returns canned brand data without HTTP.
 */
final class FakeLogokitClient extends LogokitClient
{
    /** @var array<string, array<string, mixed>> */
    private array $brands = [];

    public function setBrand(string $id, array $data): void
    {
        $this->brands[strtolower($id)] = $data;
    }

    public function getBrand(string $identifier): ?array
    {
        $key = strtolower(trim($identifier));

        return $this->brands[$key] ?? null;
    }
}
