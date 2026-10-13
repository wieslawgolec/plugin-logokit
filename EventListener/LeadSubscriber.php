<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\EventListener;

use MauticPlugin\MauticLogokitBundle\Helper\LookupHelper;

/**
 * Optionally auto-enriches contacts on create/update when auto_update is enabled.
 */
class LeadSubscriber
{
    public function __construct(
        private LookupHelper $lookupHelper,
    ) {
    }

    /** @return array<string, array{0: string, 1: int}> */
    public static function getSubscribedEvents(): array
    {
        return [
            'mautic.lead_post_save' => ['onLeadSave', 0],
        ];
    }

    /**
     * @param array{email?: string, company?: string, website?: string} $fields
     * @return array<string, mixed>|null
     */
    public function enrichIfNeeded(array $fields, bool $autoUpdateEnabled): ?array
    {
        if (!$autoUpdateEnabled) {
            return null;
        }

        if (empty($fields['email']) && empty($fields['website']) && empty($fields['company'])) {
            return null;
        }

        return $this->lookupHelper->lookupContact($fields);
    }
}
