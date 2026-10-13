<?php

declare(strict_types=1);

namespace MauticPlugin\LogokitBundle\Tests\Unit\Helper;

use MauticPlugin\LogokitBundle\Helper\LookupHelper;
use MauticPlugin\LogokitBundle\Services\ClearbitImporter;
use PHPUnit\Framework\TestCase;

final class LookupHelperTest extends TestCase
{
    private FakeLogokitClient $client;
    private LookupHelper $helper;

    protected function setUp(): void
    {
        $this->client = new FakeLogokitClient('secret', 'pub');
        $this->client->setBrand('acme.com', [
            'name'              => 'Acme',
            'domain'            => 'acme.com',
            'website'           => 'https://acme.com',
            'short_description' => 'Widgets',
            'gics_industry'     => 'Manufacturing',
            'address'           => [
                'city'    => 'Austin',
                'state'   => 'TX',
                'country' => 'US',
            ],
            'social_media'      => [
                'x'        => 'https://x.com/acme',
                'linkedin' => 'https://linkedin.com/company/acme',
            ],
        ]);
        $this->helper = new LookupHelper($this->client, new ClearbitImporter());
    }

    public function testLookupContactByEmailDomain(): void
    {
        $result = $this->helper->lookupContact(['email' => 'bob@acme.com']);
        $this->assertNotNull($result);
        $this->assertSame('Acme', $result['name']);
        $this->assertSame('acme.com', $result['domain']);
    }

    public function testLookupContactReturnsNullWithoutIdentifier(): void
    {
        $this->assertNull($this->helper->lookupContact([]));
        $this->assertNull($this->helper->lookupContact(['email' => 'invalid']));
    }

    public function testLookupCompanyByDomain(): void
    {
        $result = $this->helper->lookupCompanyByDomain('acme.com');
        $this->assertNotNull($result);
        $this->assertSame('Manufacturing', $result['industry']);
    }

    public function testToMauticCompanyFields(): void
    {
        $enrichment = $this->helper->lookupCompanyByDomain('acme.com');
        $this->assertNotNull($enrichment);
        $fields = $this->helper->toMauticCompanyFields($enrichment);

        $this->assertSame('Acme', $fields['companyname']);
        $this->assertSame('https://acme.com', $fields['companywebsite']);
        $this->assertSame('Austin', $fields['companycity']);
        $this->assertSame('TX', $fields['companystate']);
        $this->assertSame('US', $fields['companycountry']);
        $this->assertSame('Manufacturing', $fields['companyindustry']);
    }

    public function testToMauticContactFieldsIncludesSocial(): void
    {
        $enrichment = $this->helper->lookupCompanyByDomain('acme.com');
        $this->assertNotNull($enrichment);
        $fields = $this->helper->toMauticContactFields($enrichment);

        $this->assertSame('acme', $fields['twitter']);
        $this->assertArrayHasKey('linkedin', $fields);
    }

    public function testImportClearbitCompany(): void
    {
        $payload = [
            'name'   => 'OldCo',
            'domain' => 'oldco.com',
            'metrics'=> ['employees' => 10],
        ];
        $result = $this->helper->importClearbitData($payload);
        $this->assertSame('OldCo', $result['name']);
        $this->assertSame('oldco.com', $result['domain']);
        $this->assertSame(10, $result['employees']);
    }

    public function testImportClearbitPerson(): void
    {
        $payload = [
            'email' => 'a@b.com',
            'name'  => ['givenName' => 'A', 'familyName' => 'B'],
        ];
        $result = $this->helper->importClearbitData($payload);
        $this->assertSame('A', $result['firstname']);
        $this->assertSame('B', $result['lastname']);
    }
}
