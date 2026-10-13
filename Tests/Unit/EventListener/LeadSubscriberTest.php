<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Tests\Unit\EventListener;

use MauticPlugin\MauticLogokitBundle\EventListener\LeadSubscriber;
use MauticPlugin\MauticLogokitBundle\Helper\LookupHelper;
use MauticPlugin\MauticLogokitBundle\Services\ClearbitImporter;
use MauticPlugin\MauticLogokitBundle\Tests\Unit\Helper\FakeLogokitClient;
use PHPUnit\Framework\TestCase;

final class LeadSubscriberTest extends TestCase
{
    public function testEnrichSkippedWhenAutoUpdateDisabled(): void
    {
        $client = new FakeLogokitClient();
        $helper = new LookupHelper($client, new ClearbitImporter());
        $sub    = new LeadSubscriber($helper);

        $this->assertNull($sub->enrichIfNeeded(['email' => 'a@acme.com'], false));
    }

    public function testEnrichRunsWhenEnabled(): void
    {
        $client = new FakeLogokitClient();
        $client->setBrand('acme.com', [
            'name'   => 'Acme',
            'domain' => 'acme.com',
        ]);
        $helper = new LookupHelper($client, new ClearbitImporter());
        $sub    = new LeadSubscriber($helper);

        $result = $sub->enrichIfNeeded(['email' => 'a@acme.com'], true);
        $this->assertNotNull($result);
        $this->assertSame('Acme', $result['name']);
    }

    public function testSubscribedEvents(): void
    {
        $events = LeadSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey('mautic.lead_post_save', $events);
    }
}
