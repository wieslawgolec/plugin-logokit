<?php

declare(strict_types=1);

namespace MauticPlugin\LogokitBundle\Helper;

use MauticPlugin\LogokitBundle\Services\ClearbitImporter;
use MauticPlugin\LogokitBundle\Services\LogokitClient;

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
     * Enrich a contact using the domain part of its email.
     *
     * @param array{email?: string, company?: string, website?: string} $contactFields
     *
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
            // best-effort: treat company name as identifier (LogoKit accepts domain or ticker)
            $domain = strtolower(preg_replace('/[^a-z0-9.\-]/', '', (string) $contactFields['company']) ?? '');
        }

        if ($domain === '') {
            return null;
        }

        return $this->lookupCompanyByDomain($domain);
    }

    /**
     * Enrich a company by domain or ticker.
     *
     * @return array<string, mixed>|null
     */
    public function lookupCompanyByDomain(string $domainOrTicker): ?array
    {
        $brand = $this->client->getBrand($domainOrTicker);
        if ($brand === null) {
            return null;
        }

        $mapped = $this->client->mapBrandToEnrichment($brand);

        // Attach a ready-to-use logo URL if publishable token is configured
        if (empty($mapped['logo']) && !empty($mapped['domain'])) {
            $mapped['logo'] = $this->client->getLogoUrl((string) $mapped['domain']);
        }

        return $mapped;
    }

    /**
     * Import a previously stored Clearbit payload and return normalized data.
     *
     * @param array<string, mixed> $clearbitPayload
     *
     * @return array<string, mixed>
     */
    public function importClearbitData(array $clearbitPayload): array
    {
        $type = $this->importer->detectType($clearbitPayload);

        return match ($type) {
            'person'  => $this->importer->importPerson($clearbitPayload),
            'company' => $this->importer->importCompany($clearbitPayload),
            default   => ['_clearbit' => $clearbitPayload, '_type' => 'unknown'],
        };
    }

    /**
     * Build field updates that can be applied to a Mautic Lead / Company entity.
     *
     * @param array<string, mixed> $enrichment
     *
     * @return array<string, string|null>
     */
    public function toMauticCompanyFields(array $enrichment): array
    {
        $loc = is_array($enrichment['location'] ?? null) ? $enrichment['location'] : [];
        $geo = is_array($enrichment['geo'] ?? null) ? $enrichment['geo'] : [];

        return array_filter([
            'companyname'    => $enrichment['name'] ?? null,
            'companywebsite' => $enrichment['website'] ?? $enrichment['domain'] ?? null,
            'companyemail'   => null,
            'companyphone'   => null,
            'companyaddress1'=> $loc['streetAddress'] ?? null,
            'companycity'    => $loc['city'] ?? $geo['city'] ?? null,
            'companystate'   => $loc['state'] ?? $geo['state'] ?? null,
            'companyzipcode' => $loc['postalCode'] ?? null,
            'companycountry' => $loc['country'] ?? $geo['country'] ?? null,
            'companydescription' => $enrichment['description'] ?? null,
            'companyindustry'=> $enrichment['industry'] ?? $enrichment['sector'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @param array<string, mixed> $enrichment
     *
     * @return array<string, string|null>
     */
    public function toMauticContactFields(array $enrichment): array
    {
        // Contact enrichment is primarily company context from domain
        $fields = $this->toMauticCompanyFields($enrichment);

        // Also surface social if present
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
