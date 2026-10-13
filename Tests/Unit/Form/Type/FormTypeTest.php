<?php

declare(strict_types=1);

namespace MauticPlugin\MauticLogokitBundle\Tests\Unit\Form\Type;

use MauticPlugin\MauticLogokitBundle\Form\Type\BatchLookupType;
use MauticPlugin\MauticLogokitBundle\Form\Type\LookupType;
use PHPUnit\Framework\TestCase;

final class FormTypeTest extends TestCase
{
    public function testLookupType(): void
    {
        $form = new LookupType();
        $this->assertSame('logokit_lookup', $form->getBlockPrefix());
        $fields = $form->getFields();
        $this->assertArrayHasKey('objectId', $fields);
        $this->assertArrayHasKey('notify', $fields);
        $this->assertTrue($fields['notify']['default']);
    }

    public function testBatchLookupType(): void
    {
        $form = new BatchLookupType();
        $this->assertSame('logokit_batch_lookup', $form->getBlockPrefix());
        $fields = $form->getFields();
        $this->assertArrayHasKey('ids', $fields);
        $this->assertArrayHasKey('notify', $fields);
    }
}
