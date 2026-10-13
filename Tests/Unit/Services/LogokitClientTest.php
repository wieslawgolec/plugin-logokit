<?php

declare(strict_types=1);

namespace MauticPlugin\LogokitBundle\Tests\Unit\Services;

use MauticPlugin\LogokitBundle\Services\LogokitClient;
use PHPUnit\Framework\TestCase;

final class LogokitClientTest extends TestCase
{
    public function testDomainFromEmail(): void
    {
        $client = new LogokitClient();
        $this->assertSame('example.com', $client->domainFromEmail('user@example.com'));
        $this->assertSame('sub.domain.co.uk', $client->domainFromEmail('a@sub.domain.co.uk'));
        $this->assertSame('', $client->domainFromEmail('not-an-email'));
        $this->assertSame('', $client->domainFromEmail(''));
    }

    public function testGetLogoUrlBuildsCorrectUrl(): void
    {
        $client = new LogokitClient('', 'pub-token-123');
        $url = $client->getLogoUrl('stripe.com', 128, 'png');

        $this->assertStringStartsWith('https://img.logokit.com/stripe.com?', $url);
        $this->assertStringContainsString('token=pub-token-123', $url);
        $this->assertStringContainsString('size=128', $url);
        $this->assertStringContainsString('format=png', $url);
    }

    public function testGetLogoUrlSanitizesDomain(): void
    {
        $client = new LogokitClient('', 'tok');
        $url = $client->getLogoUrl('https://WWW.Example.COM/path', 64);

        $this->assertStringContainsString('img.logokit.com/www.example.com?', $url);
    }

    public function testMapBrandToEnrichment(): void
    {
        $client = new LogokitClient();
        $brand = [
            'name'               => 'Apple',
            'domain'             => 'apple.com',
            'long_name'          => 'Apple Inc.',
            'short_description'  => 'Consumer electronics',
            'website'            => 'https://www.apple.com',
            'logo'               => 'https://img.logokit.com/apple.com',
            'founded_year'       => 1976,
            'number_of_employees'=> '150000',
            'gics_sector'        => 'Information Technology',
            'gics_industry'      => 'Technology Hardware',
            'address'            => [
                'address1' => 'One Apple Park Way',
                'city'     => 'Cupertino',
                'state'    => 'CA',
                'zip'      => '95014',
                'country'  => 'US',
            ],
            'social_media'       => [
                'facebook' => 'https://facebook.com/apple',
                'x'        => 'https://x.com/apple',
                'linkedin' => 'https://linkedin.com/company/apple',
            ],
            'colors'             => ['#000000', '#ffffff'],
        ];

        $mapped = $client->mapBrandToEnrichment($brand);

        $this->assertSame('Apple', $mapped['name']);
        $this->assertSame('Apple Inc.', $mapped['legalName']);
        $this->assertSame('apple.com', $mapped['domain']);
        $this->assertSame('Consumer electronics', $mapped['description']);
        $this->assertSame('Cupertino', $mapped['geo']['city']);
        $this->assertSame('CA', $mapped['geo']['state']);
        $this->assertSame('US', $mapped['geo']['country']);
        $this->assertSame('https://x.com/apple', $mapped['twitter']);
        $this->assertSame(['#000000', '#ffffff'], $mapped['colors']);
        $this->assertArrayHasKey('raw', $mapped);
    }

    public function testGetBrandReturnsNullWithoutToken(): void
    {
        $client = new LogokitClient(''); // no token
        $this->assertNull($client->getBrand('apple.com'));
    }

    public function testSanitizeRejectsEmptyAfterClean(): void
    {
        $client = new LogokitClient('', 't');
        $this->assertSame('', $client->getLogoUrl('!!!'));
    }
}
