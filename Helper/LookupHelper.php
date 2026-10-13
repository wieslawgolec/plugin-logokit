<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Helper;

use MauticPlugin\MauticLogokitBundle\Services\ClearbitImporter;
use MauticPlugin\MauticLogokitBundle\Services\LogokitClient;

/**
 * Orchestrates enrichment lookups for contacts (via email domain) and companies.
 * Designed to be unit-testable without a full Mautic container.
 */
class LookupHelper
{
    public function __construct(
        private LogokitClient $client,
        private ClearbitImporter $importer = new ClearbitImporter(),
    ) {
    }

    /**
     * @param array{email?: string, company?: string, website?: string} $contactFields
     * @return array<string, mixed>|null
     */
    public function lookupContact(array $contactFields): ?array
    {
        $domain = '';

        if (!empty($contactFields['email'])) {
            $domain = $this->client->domainFromEmail((string) $contactFields['email']);
        }

        if ($domain === '' && !empty($contactFields['website'])) {
            $domain = $this->client->domainFromEmail('x@' . $contactFields['website']);
        }

        if ($domain === '' && !empty($contactFields['company'])) {
            $domain = strtolower(preg_replace('/[^a-z0-9.\-]/', '', (string) $contactFields['company']) ?? '');
        }

        if ($domain === '') {
            return null;
        }

        return $this->lookupCompanyByDomain($domain);
    }

    /** @return array<string, mixed>|null */
    public function lookupCompanyByDomain(string $domainOrTicker): ?array
    {
        $brand = $this->client->getBrand($domainOrTicker);
        if ($brand === null) {
            return null;
        }

        $mapped = $this->client->mapBrandToEnrichment($brand);

        if (empty($mapped['logo']) && !empty($mapped['domain'])) {
            $mapped['logo'] = $this->client->getLogoUrl((string) $mapped['domain']);
        }

        return $mapped;
    }

    /** @param array<string, mixed> $clearbitPayload @return array<string, mixed> */
    public function importClearbitData(array $clearbitPayload): array
    {
        $type = $this->importer->detectType($clearbitPayload);

        return match ($type) {
            'person'  => $this->importer->importPerson($clearbitPayload),
            'company' => $this->importer->importCompany($clearbitPayload),
            default   => ['_clearbit' => $clearbitPayload, '_type' => 'unknown'],
        };
    }

    /** @param array<string, mixed> $enrichment @return array<string, string|null> */
    public function toMauticCompanyFields(array $enrichment): array
    {
        $loc = is_array($enrichment['location'] ?? null) ? $enrichment['location'] : [];
        $geo = is_array($enrichment['geo'] ?? null) ? $enrichment['geo'] : [];

        return array_filter([
            'companyname'        => $enrichment['name'] ?? null,
            'companywebsite'     => $enrichment['website'] ?? $enrichment['domain'] ?? null,
            'companyemail'       => null,
            'companyphone'       => null,
            'companyaddress1'    => $loc['streetAddress'] ?? null,
            'companycity'        => $loc['city'] ?? $geo['city'] ?? null,
            'companystate'       => $loc['state'] ?? $geo['state'] ?? null,
            'companyzipcode'     => $loc['postalCode'] ?? null,
            'companycountry'     => $loc['country'] ?? $geo['country'] ?? null,
            'companydescription' => $enrichment['description'] ?? null,
            'companyindustry'    => $enrichment['industry'] ?? $enrichment['sector'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');
    }

    /** @param array<string, mixed> $enrichment @return array<string, string|null> */
    public function toMauticContactFields(array $enrichment): array
    {
        $fields = $this->toMauticCompanyFields($enrichment);

        if (!empty($enrichment['twitter'])) {
            $fields['twitter'] = $this->extractHandle((string) $enrichment['twitter']);
        }
        if (!empty($enrichment['facebook'])) {
            $fields['facebook'] = $this->extractHandle((string) $enrichment['facebook']);
        }
        if (!empty($enrichment['linkedin'])) {
            $fields['linkedin'] = (string) $enrichment['linkedin'];
        }

        return array_filter($fields, static fn ($v) => $v !== null && $v !== '');
    }

    private function extractHandle(string $urlOrHandle): string
    {
        $urlOrHandle = trim($urlOrHandle);
        if (str_starts_with($urlOrHandle, 'http')) {
            $path = parse_url($urlOrHandle, PHP_URL_PATH) ?: '';
            $parts = array_values(array_filter(explode('/', $path)));

            return $parts[0] ?? $urlOrHandle;
        }

        return ltrim($urlOrHandle, '@');
    }
}
