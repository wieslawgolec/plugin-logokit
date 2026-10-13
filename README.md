# LogokitBundle

**LogoKit (Clearbit replacement) plugin for Mautic 7.2+**

Enrich Contacts and Companies with brand, firmographic and logo data via the [LogoKit](https://logokit.com) Brand API and Logo CDN — a modern drop-in replacement for the retired Clearbit free logo / enrichment endpoints.

**Current version: 1.0.0**

---

## Why this plugin?

Clearbit’s free Logo API reached end-of-life ([HubSpot announcement](https://community.hubspot.com/t/upcoming-sunset-of-clearbit-s-free-logo-api/128173)).  
Mautic’s built-in Clearbit plugin therefore loses its data source.  
**plugin-logokit** restores the same (and more) functionality using LogoKit:

| Capability | Clearbit plugin | Logokit plugin |
|------------|-----------------|----------------|
| Company enrichment by domain | ✅ | ✅ (Brand API) |
| Contact enrichment via email domain | ✅ | ✅ |
| Logo URL / CDN | ✅ (logo.clearbit.com) | ✅ (img.logokit.com) |
| Batch lookup buttons on Contact / Company lists | ✅ | ✅ |
| Auto-enrich on lead create/update | ✅ | ✅ (configurable) |
| Import existing Clearbit JSON payloads | ❌ | ✅ |
| Stock ticker / crypto brand lookup | ❌ | ✅ (LogoKit) |
| Brand colours, GICS industry, social profiles | partial | ✅ |

---

## Features

| Area | What you get |
|------|----------------|
| **Brand enrichment** | Name, legal name, domain, website, description, employees, founded year, GICS sector/industry, address, social URLs, brand colours |
| **Logo CDN** | Hotlinkable logo URLs with size & format parameters |
| **Contact lookup** | Derive company domain from email → enrich |
| **Company lookup** | Direct domain or ticker lookup |
| **Clearbit import** | Map historical Clearbit person/company JSON into the same normalized structure |
| **UI buttons** | “Lookup with LogoKit” on Contact & Company list/detail views |
| **Auto-update** | Optional enrichment on lead post-save |
| **CI** | PHPUnit 11.5 on PHP 8.2 / 8.5 / 8.6 |

---

## Requirements

- **Mautic 7.2+**
- **PHP 8.1+** (CI validates 8.2, 8.5, 8.6)
- LogoKit account (Secret API token for Brand API; Publishable token for Logo CDN)

Get tokens at [logokit.com](https://logokit.com) → Account → API tokens.

---

## Installation

```text
1. Copy this repository into:

   plugins/LogokitBundle/

2. Mautic admin → Settings → Plugins → Install/Upgrade Plugins

3. Clear cache:
```

```bash
php bin/console cache:clear
```

Optional (Composer path if you vendor plugins):

```bash
composer require wieslawgolec/plugin-logokit
```

Then configure the integration under **Settings → Plugins → LogoKit**:

- **Secret API Token** (required) – Brand enrichment
- **Publishable Token** (optional) – Logo CDN URLs
- **Auto-update** – enrich contacts automatically on save

---

## Architecture

```text
LogokitBundle/
├── Config/config.php
├── Controller/                    # Lookup modals (batch & single)
├── EventListener/
│   ├── ButtonSubscriber.php       # Injects UI buttons
│   └── LeadSubscriber.php         # Optional auto-enrich
├── Form/Type/
│   ├── LookupType.php
│   └── BatchLookupType.php
├── Helper/LookupHelper.php        # Orchestration + field mapping
├── Integration/LogokitIntegration.php
├── Services/
│   ├── LogokitClient.php          # HTTP client for Brand + Logo APIs
│   └── ClearbitImporter.php       # Import / map legacy Clearbit JSON
├── Translations/en_US/messages.ini
├── Views/
└── Tests/Unit/...
```

---

## Usage

1. Enable the plugin and enter your LogoKit tokens.
2. On a Contact or Company list, select rows → **Lookup with LogoKit**.
3. Or open a single Contact/Company and use the toolbar button.
4. To migrate old Clearbit data, pass the stored Clearbit JSON through `LookupHelper::importClearbitData()`.

### Programmatic example

```php
$client = new \MauticPlugin\LogokitBundle\Services\LogokitClient($secretToken, $publishableToken);
$helper = new \MauticPlugin\LogokitBundle\Helper\LookupHelper($client);

// Contact by email
$enrichment = $helper->lookupContact(['email' => 'ceo@stripe.com']);

// Company by domain / ticker
$enrichment = $helper->lookupCompanyByDomain('AAPL');

// Import legacy Clearbit payload
$normalized = $helper->importClearbitData($oldClearbitJson);

// Map to Mautic company fields
$fields = $helper->toMauticCompanyFields($enrichment);
```

---

## Clearbit data import

`ClearbitImporter` understands typical Clearbit person and company response shapes and maps them into the same normalized structure used by LogoKit enrichment.  
The original payload is preserved under `_clearbit` for audit / re-processing.

Supported company paths include: `name`, `legalName`, `domain`, `url`, `description`, `logo`, `foundedYear`, `metrics.employees`, `category.industry|sector`, `geo.*`, social handles.  
Supported person paths include: `name.givenName|familyName|fullName`, `email`, `employment.*`, `geo.*`, social handles.

---

## Testing

### Local (standalone, no full Mautic)

```bash
cd plugins/LogokitBundle   # or repo root

# mautic/core-lib is not on public Packagist
composer remove --no-update mautic/core-lib || true
composer update --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs
vendor/bin/phpunit --colors=always
```

### CI

GitHub Actions workflow `.github/workflows/tests.yml`:

- Triggers: `push`, `pull_request`, `workflow_dispatch`
- Matrix: **PHP 8.2, 8.5, 8.6**
- PHPUnit **^11.5**
- Version constraints are **quoted** so the shell does not treat `^` or `|` as operators

### What the suite covers

- Domain extraction from email / URL sanitisation
- Logo CDN URL construction
- Brand → enrichment mapping (name, geo, social, GICS, colours)
- Clearbit company & person import + type detection
- LookupHelper orchestration (contact / company / field mapping)
- Integration settings (publish, tokens, form schema)
- ButtonSubscriber (published vs disabled)
- LeadSubscriber auto-enrich gate
- Form type block prefixes & fields

---

## Changes vs original Clearbit plugin

1. **Data provider** – LogoKit Brand API + Logo CDN instead of Clearbit.
2. **Modern PHP** – strict types, PHP 8.1+, PHPUnit 11.5.
3. **Clearbit import** – first-class support for migrating existing enrichment JSON.
4. **Ticker / broader identifiers** – LogoKit accepts domains and stock tickers.
5. **Standalone unit tests** – no full Mautic bootstrap required for CI.
6. **CI matrix** – PHP 8.2 / 8.5 / 8.6 matching other wieslawgolec Mautic plugins.

---

## Support the project

- **GitHub Sponsors:** [github.com/sponsors/wieslawgolec](https://github.com/sponsors/wieslawgolec)
- **Buy Me a Coffee:** [buymeacoffee.com/wieslawgolec](https://buymeacoffee.com/wieslawgolec)

---

## License

MIT

## Author

Wieslaw Golec

Contributions and issues welcome.
