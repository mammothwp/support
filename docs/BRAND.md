# Mammoth WP brand identity

Source of truth for tokens, logos and templates: `github.com/mammothwp/brand`. Never hand-copy assets from it.

## Name

- Brand name: **Mammoth WP** (two words, capital M, capital WP). Never "Mammoth" alone as a product name. "MammothWP" appears only as the PHP namespace `MammothWP\`.
- Product naming: **Mammoth WP &lt;Function&gt;**, up to four words (for example Mammoth WP Share Checkout, Mammoth WP Acceptance Logs). The slug follows the name; the text domain equals the slug. "WP" is allowed; "WordPress" never appears in a name, slug, handle or prefix.

## Handles and domains

- GitHub org, Packagist vendor and wordpress.org user: `mammothwp`. Composer packages: `mammothwp/<slug>`.
- Domain: `mammothwp.com` only. `mammoth-wp.com` is a 301 redirect and is never linked.
- Contact: info@mammothwp.com. Support: support@mammothwp.com (documentation and examples only, never a code default).

## Code identifiers

| Kind | Rule |
|---|---|
| PHP namespace | `MammothWP\` |
| PHP functions, options, hooks | `mmwp_` |
| CSS, JS, HTML ids, handles, page slugs | `mmwp-` |
| REST namespace | `mmwp/v1` |
| WP-CLI | `wp mmwp` |
| Abilities | `mmwp/*` |

Never a three-letter prefix.

## Visual identity

- Warm ivory with espresso / umber brown: umber `#4e2d16` on light, `#a87a52` on dark.
- Commissioner for UI and headings, JetBrains Mono for code. Self-hosted, never Google Fonts.
- Logo: a geometric block mammoth in profile plus the "Mammoth WP" wordmark. Plugin icons and banners use the "split panel" layout.

## wp-admin

Core admin UI only (form tables, notices, buttons). The brand appears only in the shared Support screen header and the menu icon, and both can be white-labelled (see [WHITELABEL.md](WHITELABEL.md)). No brand colours or fonts inside wp-admin.

## Tone

Plain, direct, technical, with light dry humour. No hype, no "AI-powered". Plugins for people who read the source. Heavy name, light plugins.
