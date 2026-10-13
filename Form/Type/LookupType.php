<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Form\Type;

class LookupType
{
    public function getBlockPrefix(): string
    {
        return 'logokit_lookup';
    }

    /** @return array<string, array<string, mixed>> */
    public function getFields(): array
    {
        return [
            'objectId' => [
                'type' => 'hidden',
            ],
            'notify' => [
                'type'    => 'yesno',
                'label'   => 'mautic.plugin.logokit.notify',
                'default' => true,
            ],
        ];
    }
}
