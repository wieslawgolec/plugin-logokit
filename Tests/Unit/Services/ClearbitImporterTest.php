<?php

declare(strict_types=1);

namespace MauticPlugin\LogokitBundle\Tests\Unit\Services;

use MauticPlugin\LogokitBundle\Services\ClearbitImporter;
use PHPUnit\Framework\TestCase;

final class ClearbitImporterTest extends TestCase
{
    private ClearbitImporter $importer;

    protected function setUp(): void
    {
        $this->importer = new ClearbitImporter();
    }

    public function testImportCompanyMapsCoreFields(): void
    {
        $payload = [
            'name'        => 'Acme Corp',
            'legalName'   => 'Acme Corporation',
            'domain'      => 'acme.com',
            'url'         => 'https://acme.com',
            'description' => 'Widgets',
            'logo'        => 'https://logo.clearbit.com/acme.com',
            'foundedYear' => 1999,
            'metrics'     => ['employees' => 250],
            'category'    => ['industry' => 'Manufacturing', 'sector' => 'Industrials'],
            'geo'         => [
                'city'          => 'Austin',
                'state'         => 'TX',
                'country'       => 'US',
                'streetAddress' => '123 Main St',
                'postalCode'    => '78701',
            ],
            'facebook' => ['handle' => 'acme'],
            'twitter'  => ['handle' => 'acmecorp'],
            'linkedin' => ['handle' => 'company/acme'],
        ];

        $result = $this->importer->importCompany($payload);

        $this->assertSame('Acme Corp', $result['name']);
        $this->assertSame('Acme Corporation', $result['legalName']);
        $this->assertSame('acme.com', $result['domain']);
        $this->assertSame('https://acme.com', $result['website']);
        $this->assertSame(250, $result['employees']);
        $this->assertSame('Manufacturing', $result['industry']);
        $this->assertSame('Austin', $result['geo']['city']);
        $this->assertSame('TX', $result['geo']['state']);
        $this->assertSame('acme', $result['facebook']);
        $this->assertArrayHasKey('_clearbit', $result);
    }

    public function testImportPersonMapsCoreFields(): void
    {
        $payload = [
            'name'  => ['givenName' => 'Jane', 'familyName' => 'Doe', 'fullName' => 'Jane Doe'],
            'email' => 'jane@acme.com',
            'employment' => [
                'title'  => 'CTO',
                'name'   => 'Acme Corp',
                'domain' => 'acme.com',
            ],
            'geo' => [
                'city'    => 'Austin',
                'state'   => 'TX',
                'country' => 'US',
            ],
            'twitter'  => ['handle' => 'janedoe'],
            'linkedin' => ['handle' => 'in/janedoe'],
        ];

        $result = $this->importer->importPerson($payload);

        $this->assertSame('Jane', $result['firstname']);
        $this->assertSame('Doe', $result['lastname']);
        $this->assertSame('Jane Doe', $result['name']);
        $this->assertSame('jane@acme.com', $result['email']);
        $this->assertSame('CTO', $result['position']);
        $this->assertSame('Acme Corp', $result['company']);
        $this->assertSame('acme.com', $result['companyDomain']);
        $this->assertSame('Austin', $result['city']);
        $this->assertSame('janedoe', $result['twitter']);
        $this->assertArrayHasKey('_clearbit', $result);
    }

    public function testDetectType(): void
    {
        $this->assertSame('person', $this->importer->detectType(['email' => 'a@b.com']));
        $this->assertSame('person', $this->importer->detectType(['name' => ['givenName' => 'A']]));
        $this->assertSame('company', $this->importer->detectType(['domain' => 'x.com']));
        $this->assertSame('company', $this->importer->detectType(['metrics' => []]));
        $this->assertSame('unknown', $this->importer->detectType(['foo' => 'bar']));
    }
}
