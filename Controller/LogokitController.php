<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Controller;

use MauticPlugin\MauticLogokitBundle\Helper\LookupHelper;

/**
 * Handles single and batch lookup actions from the Mautic UI.
 */
class LogokitController
{
    public function __construct(
        private ?LookupHelper $lookupHelper = null,
    ) {
    }

    /**
     * @param array<string, mixed> $requestData
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function executeAction(string $objectAction, int $objectId = 0, array $requestData = []): array
    {
        return match ($objectAction) {
            'lookup'      => $this->lookup($objectId, $requestData),
            'batchLookup' => $this->batchLookup($requestData),
            default       => ['success' => false, 'message' => 'Unknown action: ' . $objectAction],
        };
    }

    /**
     * @param array<string, mixed> $requestData
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function lookup(int $objectId, array $requestData = []): array
    {
        if ($this->lookupHelper === null) {
            return ['success' => false, 'message' => 'Lookup helper not configured'];
        }

        $fields = [
            'email'   => $requestData['email'] ?? null,
            'company' => $requestData['company'] ?? null,
            'website' => $requestData['website'] ?? null,
        ];

        $result = $this->lookupHelper->lookupContact(array_filter($fields));

        if ($result === null) {
            return ['success' => false, 'message' => 'No domain or email found to look up'];
        }

        return [
            'success' => true,
            'message' => 'Enrichment completed successfully',
            'data'    => $result,
        ];
    }

    /**
     * @param array<string, mixed> $requestData
     * @return array{success: bool, message: string, processed: int, results: list<array<string, mixed>>}
     */
    public function batchLookup(array $requestData = []): array
    {
        if ($this->lookupHelper === null) {
            return ['success' => false, 'message' => 'Lookup helper not configured', 'processed' => 0, 'results' => []];
        }

        $ids = $requestData['ids'] ?? [];
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids     = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No ids provided', 'processed' => 0, 'results' => []];
        }

        $results   = [];
        $processed = 0;

        foreach ($ids as $id) {
            $email = is_array($id) ? ($id['email'] ?? null) : null;
            if ($email === null && is_array($requestData['contacts'] ?? null)) {
                $email = $requestData['contacts'][(string) $id]['email'] ?? null;
            }

            $item = $this->lookupHelper->lookupContact(['email' => (string) $email]);
            if ($item !== null) {
                $results[] = ['id' => $id, 'data' => $item];
                ++$processed;
            }
        }

        return [
            'success'   => $processed > 0,
            'message'   => sprintf('Processed %d of %d', $processed, count($ids)),
            'processed' => $processed,
            'results'   => $results,
        ];
    }
}
