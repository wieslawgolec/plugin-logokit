<?php

declare(strict_types=1);

namespace MauticPlugin\LogokitBundle;

// PluginBundleBase is only available inside a full Mautic install.
// Unit tests run without mautic/core-lib, so extend a lightweight base there.
if (class_exists(\Mautic\PluginBundle\Bundle\PluginBundleBase::class)) {
    class MauticLogokitBundle extends \Mautic\PluginBundle\Bundle\PluginBundleBase
    {
    }
} else {
    class MauticLogokitBundle
    {
    }
}
