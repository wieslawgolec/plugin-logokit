<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Tests\Unit\EventListener;

use MauticPlugin\MauticLogokitBundle\EventListener\ButtonSubscriber;
use MauticPlugin\MauticLogokitBundle\Integration\LogokitIntegration;
use PHPUnit\Framework\TestCase;

final class ButtonSubscriberTest extends TestCase
{
    public function testNoButtonsWhenIntegrationMissing(): void
    {
        $helper = new class {
            public function getIntegrationObject(string $name): false
            {
                return false;
            }
        };
        $translator = new class {
            public function trans(string $id): string
            {
                return $id;
            }
        };
        $router = new class {
            public function generate(string $name, array $params = []): string
            {
                return '/s/logokit/' . ($params['objectAction'] ?? '');
            }
        };

        $sub = new ButtonSubscriber($helper, $translator, $router);
        $this->assertSame([], $sub->getAvailableButtons('mautic_contact_index'));
    }

    public function testButtonsWhenPublished(): void
    {
        $integration = (new LogokitIntegration())->setPublished(true)->setApiKeys(['apitoken' => 'x']);
        $helper = new class ($integration) {
            public function __construct(private LogokitIntegration $i)
            {
            }

            public function getIntegrationObject(string $name): LogokitIntegration|false
            {
                return $name === 'Logokit' ? $this->i : false;
            }
        };
        $translator = new class {
            public function trans(string $id): string
            {
                return 'Lookup with LogoKit';
            }
        };
        $router = new class {
            public function generate(string $name, array $params = []): string
            {
                return '/s/logokit/' . ($params['objectAction'] ?? '');
            }
        };

        $sub = new ButtonSubscriber($helper, $translator, $router);
        $buttons = $sub->getAvailableButtons('mautic_contact_index');
        $this->assertCount(1, $buttons);
        $this->assertSame('Lookup with LogoKit', $buttons[0]['btnText']);
        $this->assertStringContainsString('batchLookup', $buttons[0]['attr']['href']);
    }

    public function testSubscribedEvents(): void
    {
        $events = ButtonSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey('mautic.core_on_view_inject_custom_buttons', $events);
    }
}
