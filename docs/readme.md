# Fexa AI Connector — User Documentation

Connect your PrestaShop store to Fexa AI to automate SEO and AEO (answer-engine
optimization): AI-written titles, meta descriptions, product descriptions, ALT tags,
JSON-LD structured data, and an auto-served `/llms.txt` map of your catalog.

Compatibility: PrestaShop 8.1.x and 9.x — PHP 8.1+.

## Installation

1. In your Back Office, go to **Modules → Module Manager**.
2. Click **Upload a module** and drop the module archive.
3. Once installed, open the module's **Configure** page.

## Configuration

1. On the **Configure** page, copy your **API Key**.
2. Paste it into your Fexa AI dashboard to connect this store.
3. (Optional) Under **Structured data (JSON-LD)**, choose which schemas Fexa injects
   (FAQ is recommended; Product and Breadcrumb are off by default to avoid duplicating
   what most themes already emit).

## What it does

- **AI SEO**: rewrites titles, meta descriptions, descriptions and image ALT tags.
- **Structured data**: injects JSON-LD (FAQ / Product / Breadcrumb) into the page head.
- **AEO / `/llms.txt`**: publishes a machine-readable map of your catalog at the domain root.
- **Translation**: optimizes content per active language.

The module exposes a secure, API-key-protected endpoint that Fexa AI reads from and writes
to. It never modifies PrestaShop core files or core database tables.

## FAQ

**Do I need a Fexa AI account?** Yes — the connector links your store to the Fexa AI service.

**Is my data safe?** The endpoint is protected by a unique API key (regenerated per install)
and CORS is restricted. The module only reads/writes your own catalog content.

**Which PrestaShop versions?** 8.1.x and 9.x on PHP 8.1+. A separate build exists for 1.7.x.

## Support

Support is provided through your PrestaShop Addons account for the Marketplace edition.
