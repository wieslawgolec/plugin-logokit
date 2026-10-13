<?php

declare(strict_types=1);

namespace MauticPlugin\LogokitBundle\Tests\Unit\Integration;

use MauticPlugin\LogokitBundle\Integration\LogokitIntegration;
use PHPUnit\Framework\TestCase;

final class LogokitIntegrationTest extends TestCase
{
    public function testDefaults(): void
    {
        $i = new LogokitIntegration();
        $this->assertSame('Logokit', $i->getName());
        $this->assertFalse($i->isPublished());
        $this->assertFalse($i->isConfigured());
        $this->assertFalse($i->shouldAutoUpdate());
        $this->assertSame('', $i->getApiToken());
    }

    public function testSetters(): void
    {
        $i = new LogokitIntegration();
        $i->setPublished(true)
          ->setApiKeys(['apitoken' => 'secret', 'publishabletoken' => 'pub'])
          ->setAutoUpdate(true);

        $this->assertTrue($i->isPublished());
        $this->assertTrue($i->isConfigured());
        $this->assertTrue($i->shouldAutoUpdate());
        $this->assertSame('secret', $i->getApiToken());
        $this->assertSame('pub', $i->getPublishableToken());
    }

    public function testFormSettingsContainRequiredKeys(): void
    {
        $i = new LogokitIntegration();
        $fields = $i->getFormSettings();
        $this->assertArrayHasKey('apitoken', $fields);
        $this->assertArrayHasKey('publishabletoken', $fields);
        $this->assertArrayHasKey('auto_update', $fields);
        $this->assertTrue($fields['apitoken']['required']);
    }
}
