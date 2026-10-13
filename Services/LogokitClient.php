<?php

declare(strict_types=1);

namespace MauticPlugin\LogokitBundle\Services;

/**
 * HTTP client for LogoKit Brand API and Logo CDN.
 *
 * Brand API: https://api.logokit.com/brands/{identifier}
 * Logo CDN:  https://img.logokit.com/{domain}?token=...
 */
class LogokitClient
{
    public const BRAND_API_BASE = 'https://api.logokit.com/brands/';
    public const LOGO_CDN_BASE  = 'https://img.logokit.com/';

    private string $apiToken;
    private string $publishableToken;
    private int $timeout;

    public function __construct(string $apiToken = '', string $publishableToken = '', int $timeout = 15)
    {
        $this->apiToken          = $apiToken;
        $this->publishableToken  = $publishableToken;
        $this->timeout           = $timeout;
    }

    public function setApiToken(string $token): self
    {
        $this->apiToken = $token;

        return $this;
    }

    public function setPublishableToken(string $token): self
    {
        $this->publishableToken = $token;

        return $this;
    }

    /**
     * Fetch brand / company enrichment data by domain or ticker.
     *
     * @return array<string, mixed>|null
     */
    public function getBrand(string $identifier): ?array
    {
        $identifier = $this->sanitizeIdentifier($identifier);
        if ($identifier === '') {
            return null;
        }

        $url = self::BRAND_API_BASE . rawurlencode($identifier);
        $response = $this->request('GET', $url, [
            'Authorization: Bearer ' . $this->apiToken,
            'Accept: application/json',
        ]);

        if ($response === null || !is_array($response)) {
            return null;
        }

        return $response;
    }

    /**
     * Build a Logo CDN URL for a domain (hotlink-friendly).
     */
    public function getLogoUrl(string $domain, int $size = 128, string $format = 'png'): string
    {
        $domain = $this->sanitizeIdentifier($domain);
        if ($domain === '') {
            return '';
        }

        $params = [
            'token'  => $this->publishableToken,
            'size'   => (string) $size,
            'format' => $format,
        ];

        return self::LOGO_CDN_BASE . rawurlencode($domain) . '?' . http_build_query($params);
    }

    /**
     * Map LogoKit brand response to a normalized enrichment payload
     * compatible with former Clearbit field names where possible.
     *
     * @param array<string, mixed> $brand
     *
     * @return array<string, mixed>
     */
    public function mapBrandToEnrichment(array $brand): array
    {
        $address = is_array($brand['address'] ?? null) ? $brand['address'] : [];
        $social  = is_array($brand['social_media'] ?? null) ? $brand['social_media'] : [];

        return [
            'name'               => $brand['name'] ?? $brand['long_name'] ?? null,
            'legalName'          => $brand['long_name'] ?? null,
            'domain'             => $brand['domain'] ?? null,
            'website'            => $brand['website'] ?? null,
            'description'        => $brand['short_description'] ?? $brand['long_description'] ?? null,
            'longDescription'    => $brand['long_description'] ?? null,
            'logo'               => $brand['logo'] ?? null,
            'foundedYear'        => $brand['founded_year'] ?? null,
            'employees'          => $brand['number_of_employees'] ?? null,
            'industry'           => $brand['gics_industry'] ?? $brand['gics_sector'] ?? null,
            'sector'             => $brand['gics_sector'] ?? null,
            'industryGroup'      => $brand['gics_industry_group'] ?? null,
            'subIndustry'        => $brand['gics_sub_industry'] ?? null,
            'location'           => [
                'streetAddress'  => trim(($address['address1'] ?? '') . ' ' . ($address['address2'] ?? '')),
                'city'           => $address['city'] ?? null,
                'state'          => $address['state'] ?? null,
                'postalCode'     => $address['zip'] ?? null,
                'country'        => $address['country'] ?? null,
            ],
            'geo'                => [
                'city'    => $address['city'] ?? null,
                'state'   => $address['state'] ?? null,
                'country' => $address['country'] ?? null,
            ],
            'facebook'           => $social['facebook'] ?? null,
            'twitter'            => $social['x'] ?? null,
            'linkedin'           => $social['linkedin'] ?? null,
            'instagram'          => $social['instagram'] ?? null,
            'youtube'            => $social['youtube'] ?? null,
            'colors'             => $brand['colors'] ?? [],
            'raw'                => $brand,
        ];
    }

    /**
     * Extract domain from an email address.
     */
    public function domainFromEmail(string $email): string
    {
        $email = trim(strtolower($email));
        if (!str_contains($email, '@')) {
            return '';
        }

        $parts = explode('@', $email, 2);

        return $this->sanitizeIdentifier($parts[1] ?? '');
    }

    private function sanitizeIdentifier(string $value): string
    {
        $value = trim(strtolower($value));
        // strip protocol / path if someone passes a full URL
        $value = preg_replace('#^https?://#i', '', $value) ?? $value;
        $value = preg_replace('#/.*$#', '', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9.\-]/', '', $value) ?? $value;

        return $value;
    }

    /**
     * @param array<int, string> $headers
     *
     * @return array<string, mixed>|null
     */
    protected function request(string $method, string $url, array $headers = []): ?array
    {
        if ($this->apiToken === '') {
            return null;
        }

        $ch = curl_init();
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body     = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
