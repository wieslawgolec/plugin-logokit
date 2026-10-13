# MauticLogokitBundle

**LogoKit (Clearbit replacement) plugin for Mautic 7.2+**

Enrich Contacts and Companies with brand, firmographic and logo data via the [LogoKit](https://logokit.com) Brand API and Logo CDN — a modern drop-in replacement for the retired Clearbit free logo / enrichment endpoints.

**Current version: 1.0.0**

**Namespace:** `MauticPlugin\MauticLogokitBundle`  
**Install directory:** `plugins/MauticLogokitBundle/`

---

## Why this plugin?

The original Mautic Clearbit plugin (`MauticClearbitBundle`) is being **removed in Mautic 8**. At the same time, Clearbit’s free Logo API has already reached end-of-life ([HubSpot announcement](https://community.hubspot.com/t/upcoming-sunset-of-clearbit-s-free-logo-api/128173)), so the built-in plugin loses its data source even before removal.

**plugin-logokit** was created to:

1. **Preserve the old Clearbit plugin functionality** for Mautic 7.2+ and beyond (contact/company lookup, batch buttons, optional auto-enrich on save, logo URLs).
2. **Replace the dead Clearbit data source** with LogoKit Brand API + Logo CDN.
3. **Support migration** of existing Clearbit JSON payloads via `ClearbitImporter`.

If you relied on the stock Clearbit plugin, install this bundle so enrichment continues to work after Clearbit’s API sunset and after the core Clearbit plugin is dropped in Mautic 8.

---

## Requirements

- **Mautic 7.2+**
- **PHP 8.1+** (CI validates 8.2, 8.5, 8.6)
- LogoKit account (Secret API token for Brand API; Publishable token for Logo CDN)

---

## Installation

```text
1. Copy this repository into:

   plugins/MauticLogokitBundle/

2. Mautic admin → Settings → Plugins → Install/Upgrade Plugins

3. Clear cache:
```

```bash
php bin/console cache:clear
```

```bash
composer require wieslawgolec/plugin-logokit
```

---

## Programmatic example

```php
$client = new \MauticPlugin\MauticLogokitBundle\Services\LogokitClient($secretToken, $publishableToken);
$helper = new \MauticPlugin\MauticLogokitBundle\Helper\LookupHelper($client);

$enrichment = $helper->lookupContact(['email' => 'ceo@stripe.com']);
$enrichment = $helper->lookupCompanyByDomain('AAPL');
$normalized = $helper->importClearbitData($oldClearbitJson);
$fields = $helper->toMauticCompanyFields($enrichment);
```

---

## Testing

```bash
cd plugins/MauticLogokitBundle
composer remove --no-update mautic/core-lib || true
composer update --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs
vendor/bin/phpunit --colors=always
```

CI: PHPUnit 11.5 on PHP 8.2 / 8.5 / 8.6

---

## Support the project

- **GitHub Sponsors:** [github.com/sponsors/wieslawgolec](https://github.com/sponsors/wieslawgolec)
- **Buy Me a Coffee:** [buymeacoffee.com/wieslawgolec](https://buymeacoffee.com/wieslawgolec)

---

## License

MIT

## Author

Wieslaw Golec
