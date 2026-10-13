<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Controller;

/**
 * Public (unauthenticated) endpoints if needed for webhooks / logo proxies.
 */
class PublicController
{
    /**
     * @param array<string, mixed> $requestData
     * @return array{success: bool, message: string}
     */
    public function executeAction(string $objectAction, array $requestData = []): array
    {
        return match ($objectAction) {
            'health' => ['success' => true, 'message' => 'ok'],
            default  => ['success' => false, 'message' => 'Unknown public action: ' . $objectAction],
        };
    }
}
