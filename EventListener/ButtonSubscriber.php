<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\EventListener;

/**
 * Injects "Lookup with LogoKit" buttons on Contact / Company list & detail views.
 */
class ButtonSubscriber
{
    public function __construct(
        private object $integrationHelper,
        private object $translator,
        private object $router,
    ) {
    }

    /** @return array<string, array{0: string, 1: int}> */
    public static function getSubscribedEvents(): array
    {
        return [
            'mautic.core_on_view_inject_custom_buttons' => ['injectViewButtons', 0],
        ];
    }

    /** @return array<string, mixed>|null */
    public function buildLookupButton(string $routePrefix, bool $isBatch = false): ?array
    {
        if (!method_exists($this->integrationHelper, 'getIntegrationObject')) {
            return null;
        }

        $integration = $this->integrationHelper->getIntegrationObject('Logokit');
        if ($integration === false || !$integration->isPublished()) {
            return null;
        }

        $action = $isBatch ? 'batchLookup' : 'lookup';
        $caption = method_exists($this->translator, 'trans')
            ? $this->translator->trans('mautic.plugin.logokit.button.caption')
            : 'Lookup with LogoKit';

        $href = method_exists($this->router, 'generate')
            ? $this->router->generate('mautic_plugin_logokit_action', ['objectAction' => $action])
            : '/s/logokit/' . $action;

        return [
            'attr' => [
                'class'       => 'btn btn-ghost btn-sm btn-nospin',
                'data-toggle' => 'ajaxmodal',
                'data-target' => '#MauticSharedModal',
                'data-header' => $caption,
                'href'        => $href,
            ],
            'btnText'   => $caption,
            'iconClass' => 'ri-search-line',
            'priority'  => 0,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function getAvailableButtons(string $route): array
    {
        $buttons = [];

        if (str_starts_with($route, 'mautic_contact_') || str_starts_with($route, 'mautic_company_')) {
            $btn = $this->buildLookupButton(
                str_starts_with($route, 'mautic_contact_') ? 'contact' : 'company',
                str_contains($route, 'index') || str_contains($route, 'list')
            );
            if ($btn !== null) {
                $buttons[] = $btn;
            }
        }

        return $buttons;
    }
}
