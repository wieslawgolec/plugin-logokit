<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Services;

/**
 * Imports / maps existing Clearbit enrichment payloads into LogoKit-compatible
 * normalized structures so historical data can be retained or re-mapped.
 */
class ClearbitImporter
{
    /** @var array<string, string> */
    private const COMPANY_FIELD_MAP = [
        'name'              => 'name',
        'legalName'         => 'legalName',
        'domain'            => 'domain',
        'url'               => 'website',
        'description'       => 'description',
        'logo'              => 'logo',
        'foundedYear'       => 'foundedYear',
        'metrics.employees' => 'employees',
        'category.industry' => 'industry',
        'category.sector'   => 'sector',
        'geo.city'          => 'geo.city',
        'geo.state'         => 'geo.state',
        'geo.country'       => 'geo.country',
        'geo.streetAddress' => 'location.streetAddress',
        'geo.postalCode'    => 'location.postalCode',
        'facebook.handle'   => 'facebook',
        'twitter.handle'    => 'twitter',
        'linkedin.handle'   => 'linkedin',
    ];

    /** @var array<string, string> */
    private const PERSON_FIELD_MAP = [
        'name.givenName'    => 'firstname',
        'name.familyName'   => 'lastname',
        'name.fullName'     => 'name',
        'email'             => 'email',
        'employment.title'  => 'position',
        'employment.name'   => 'company',
        'employment.domain' => 'companyDomain',
        'geo.city'          => 'city',
        'geo.state'         => 'state',
        'geo.country'       => 'country',
        'facebook.handle'   => 'facebook',
        'twitter.handle'    => 'twitter',
        'linkedin.handle'   => 'linkedin',
    ];

    /** @param array<string, mixed> $clearbitPayload @return array<string, mixed> */
    public function importCompany(array $clearbitPayload): array
    {
        return $this->mapNested($clearbitPayload, self::COMPANY_FIELD_MAP);
    }

    /** @param array<string, mixed> $clearbitPayload @return array<string, mixed> */
    public function importPerson(array $clearbitPayload): array
    {
        return $this->mapNested($clearbitPayload, self::PERSON_FIELD_MAP);
    }

    public function detectType(array $payload): string
    {
        if (isset($payload['person']) || isset($payload['name']['givenName']) || isset($payload['email'])) {
            return 'person';
        }
        if (isset($payload['company']) || isset($payload['domain']) || isset($payload['metrics'])) {
            return 'company';
        }

        return 'unknown';
    }

    /** @param array<string, mixed> $source @param array<string, string> $map @return array<string, mixed> */
    private function mapNested(array $source, array $map): array
    {
        $result = [];

        foreach ($map as $clearbitPath => $targetPath) {
            $value = $this->getByPath($source, $clearbitPath);
            if ($value !== null && $value !== '') {
                $this->setByPath($result, $targetPath, $value);
            }
        }

        $result['_clearbit'] = $source;

        return $result;
    }

    /** @param array<string, mixed> $data */
    private function getByPath(array $data, string $path): mixed
    {
        $segments = explode('.', $path);
        $current  = $data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /** @param array<string, mixed> $data */
    private function setByPath(array &$data, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $current  = &$data;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;
            } else {
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }
    }
}
