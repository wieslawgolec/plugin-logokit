<?php

declare(strict_types=1);

namespace MauticPlugin\LogokitBundle\Integration;

/**
 * Integration settings object (standalone-testable).
 * In full Mautic this would extend AbstractIntegration.
 */
class LogokitIntegration
{
    public const NAME = 'Logokit';

    private bool $published = false;

    /** @var array<string, string> */
    private array $apiKeys = [
        'apitoken'         => '',
        'publishabletoken' => '',
    ];

    private bool $autoUpdate = false;

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'LogoKit (Clearbit replacement)';
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getApiKeys(): array
    {
        return $this->apiKeys;
    }

    /**
     * @param array<string, string> $keys
     */
    public function setApiKeys(array $keys): self
    {
        $this->apiKeys = array_merge($this->apiKeys, $keys);

        return $this;
    }

    public function getApiToken(): string
    {
        return $this->apiKeys['apitoken'] ?? '';
    }

    public function getPublishableToken(): string
    {
        return $this->apiKeys['publishabletoken'] ?? '';
    }

    public function shouldAutoUpdate(): bool
    {
        return $this->autoUpdate;
    }

    public function setAutoUpdate(bool $auto): self
    {
        $this->autoUpdate = $auto;

        return $this;
    }

    /**
     * Required form fields for the plugin settings UI.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getFormSettings(): array
    {
        return [
            'apitoken' => [
                'label'    => 'mautic.plugin.logokit.apitoken',
                'required' => true,
                'type'     => 'password',
            ],
            'publishabletoken' => [
                'label'    => 'mautic.plugin.logokit.publishabletoken',
                'required' => false,
                'type'     => 'text',
            ],
            'auto_update' => [
                'label'    => 'mautic.plugin.logokit.auto_update',
                'required' => false,
                'type'     => 'yesno',
            ],
        ];
    }

    public function isConfigured(): bool
    {
        return $this->getApiToken() !== '';
    }
}
