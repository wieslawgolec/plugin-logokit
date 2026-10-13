<?php

declare(strict_types=1);

return [
    'name'        => 'LogoKit',
    'description' => 'LogoKit (Clearbit replacement) — company & contact enrichment + logo API',
    'version'     => '1.0.0',
    'author'      => 'Wieslaw Golec',

    'routes' => [
        'main' => [
            'mautic_plugin_logokit_action' => [
                'path'       => '/logokit/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\LogokitBundle\Controller\LogokitController::executeAction',
                'defaults'   => [
                    'objectId' => 0,
                ],
            ],
        ],
        'public' => [
            'mautic_plugin_logokit_public' => [
                'path'       => '/logokit/public/{objectAction}',
                'controller' => 'MauticPlugin\LogokitBundle\Controller\PublicController::executeAction',
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.plugin.logokit.button_subscriber' => [
                'class'     => \MauticPlugin\LogokitBundle\EventListener\ButtonSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'translator',
                    'router',
                ],
            ],
            'mautic.plugin.logokit.lead_subscriber' => [
                'class'     => \MauticPlugin\LogokitBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.plugin.logokit.lookup_helper',
                ],
            ],
        ],
        'other' => [
            'mautic.plugin.logokit.client' => [
                'class' => \MauticPlugin\LogokitBundle\Services\LogokitClient::class,
            ],
            'mautic.plugin.logokit.importer' => [
                'class' => \MauticPlugin\LogokitBundle\Services\ClearbitImporter::class,
            ],
            'mautic.plugin.logokit.lookup_helper' => [
                'class'     => \MauticPlugin\LogokitBundle\Helper\LookupHelper::class,
                'arguments' => [
                    'mautic.plugin.logokit.client',
                    'mautic.plugin.logokit.importer',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.logokit' => [
                'class' => \MauticPlugin\LogokitBundle\Integration\LogokitIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
        ],
    ],
];
