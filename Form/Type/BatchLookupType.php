<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Form\Type;

class BatchLookupType
{
    public function getBlockPrefix(): string
    {
        return 'logokit_batch_lookup';
    }

    /** @return array<string, array<string, mixed>> */
    public function getFields(): array
    {
        return [
            'ids' => [
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
