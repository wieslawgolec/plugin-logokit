<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Tests\Unit\Controller;

use MauticPlugin\MauticLogokitBundle\Controller\LogokitController;
use MauticPlugin\MauticLogokitBundle\Controller\PublicController;
use MauticPlugin\MauticLogokitBundle\Helper\LookupHelper;
use MauticPlugin\MauticLogokitBundle\Services\ClearbitImporter;
use MauticPlugin\MauticLogokitBundle\Tests\Unit\Helper\FakeLogokitClient;
use PHPUnit\Framework\TestCase;

final class LogokitControllerTest extends TestCase
{
    private LogokitController $controller;

    protected function setUp(): void
    {
        $client = new FakeLogokitClient('secret', 'pub');
        $client->setBrand('acme.com', [
            'name'   => 'Acme',
            'domain' => 'acme.com',
        ]);
        $helper = new LookupHelper($client, new ClearbitImporter());
        $this->controller = new LogokitController($helper);
    }

    public function testLookupSuccess(): void
    {
        $result = $this->controller->lookup(1, ['email' => 'bob@acme.com']);
        $this->assertTrue($result['success']);
        $this->assertSame('Acme', $result['data']['name']);
    }

    public function testLookupFailsWithoutEmail(): void
    {
        $result = $this->controller->lookup(1, []);
        $this->assertFalse($result['success']);
    }

    public function testBatchLookup(): void
    {
        $result = $this->controller->batchLookup([
            'ids'      => [1, 2],
            'contacts' => [
                '1' => ['email' => 'a@acme.com'],
                '2' => ['email' => 'b@acme.com'],
            ],
        ]);
        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['processed']);
    }

    public function testUnknownAction(): void
    {
        $result = $this->controller->executeAction('nope');
        $this->assertFalse($result['success']);
    }

    public function testPublicHealth(): void
    {
        $public = new PublicController();
        $result = $public->executeAction('health');
        $this->assertTrue($result['success']);
    }
}
