<?php

declare(strict_types=1);

namespace MauticPlugin\LogokitBundle\Controller;

/**
 * Public (unauthenticated) endpoints if needed for webhooks / logo proxies.
 * LogoKit itself is CDN-based; this controller is a safe stub for route wiring.
 */
class PublicController
{
    /**
     * @param array<string, mixed> $requestData
     *
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
