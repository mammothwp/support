# Configuration

## Install / integrate

Plugins require `mammothwp/support: ^1.1` from Packagist; `mammothwp/wp-kit` `^2.0` arrives transitively at default stability. The wp-kit version guard reads the `Kit::version()` constant (>= 1.0.0), not Composer's installed version string.

## `Support::register_plugin( string $file, array $args = [] )`

| Key | Type | Default | Notes |
|---|---|---|---|
| `email` | string | none | **Required.** Validated with `is_email()`. Invalid or missing: the plugin is listed, Send is disabled, a notice is shown and `_doing_it_wrong()` is raised. No fallback address, ever. |
| `name` | string | plugin header `Plugin Name` | Display name. |
| `slug` | string | plugin folder name | Passed through `sanitize_title()`. Used in the subject, the email constant and `?plugin=`. |
| `version` | string | plugin header `Version` | |
| `vendor` | string | plugin header `Author` (tags stripped) | "Mammoth WP" (case-insensitive) selects the Mammoth WP defaults. |
| `docs_url` | string | none | Shown in the plugin list. |
| `menu` | array | none | `[ 'parent' => '<menu slug>', 'title' => 'Support' ]`: adds a shortcut submenu item to the canonical screen. |
| `branding` | array | none | See below; honoured only when every registered plugin has the same vendor. |

Header fallbacks use `get_file_data()`, so the minimal call is `[ 'email' => '...' ]`.

## `mammothwp-support.json`

Same keys, in the plugin root. Read by `register_plugin( __FILE__ )`; array keys override file keys. Unknown keys and wrong types are ignored with a `_doing_it_wrong()` notice.

```json
{
    "email": "support@example.com",
    "name": "Example Plugin",
    "slug": "example-plugin",
    "version": "1.2.0",
    "vendor": "Example Ltd",
    "docs_url": "https://example.com/docs",
    "menu": { "parent": "example-settings", "title": "Support" },
    "branding": { "name": "Example Help", "subject_prefix": "[Example]" }
}
```

Types: `menu` and `branding` are objects, everything else is a string.

## Recipient precedence

1. filter `mmwp_support_email( $email, $slug )`
2. constant `MMWP_SUPPORT_EMAIL_<SLUG_UPPER_UNDERSCORED>` (`my-plugin` becomes `MMWP_SUPPORT_EMAIL_MY_PLUGIN`)
3. `email` in the `register_plugin()` array
4. `email` in `mammothwp-support.json`

The result must pass `is_email()`; otherwise sending is disabled for that plugin. `wp mmwp support plugins` shows `email_source`.

## Placement precedence

1. filter `mmwp_support_menu( null, $default )`
2. constant `MMWP_SUPPORT_MENU`
3. default: `toplevel` if any registered plugin has vendor "Mammoth WP", otherwise `tools`

Values: `toplevel`, `tools`, `settings`, `hidden`, `parent:<menu-slug>`. Invalid values are ignored. Plugins cannot move the canonical screen. In the network admin, `tools` and `settings` both use Settings, and `hidden` has no menu item.

## Branding precedence (lowest to highest)

1. Defaults: Mammoth WP (when a Mammoth WP plugin is registered) or neutral.
2. Plugin `'branding'` arrays, only if all registered plugins share one vendor (first plugin that sets a key wins).
3. `MMWP_SUPPORT_WHITELABEL`: `true` (fully neutral) or an array merged over the neutral defaults.
4. filter `mmwp_support_branding( $values, $mode )`.

| Key | Neutral | Mammoth WP |
|---|---|---|
| `name` | Support | Mammoth WP |
| `menu_title` | Support | Mammoth WP |
| `page_title` | Support | Mammoth WP Support |
| `icon` | `dashicons-sos` | monochrome SVG data URI |
| `logo_url` | empty | empty |
| `subject_prefix` | `[Support]` | `[Mammoth WP]` |
| `ref_prefix` | `SUP` | `MMWP` |
| `show_powered_by` | false | true |
| `show_vendor_names` | true | true |

`icon` is a dashicon name or a `data:image/svg+xml;base64,...` URI; anything else is dropped. `logo_url` must be a path starting with a single `/` or an `http(s)` URL on the site's own host (home, site, content or admin URL); anything remote is dropped. `ref_prefix` is not in the original field list; it makes the reference `SUP-<yyyymmdd>-<6 chars>` under white-label.

## Email

- To: the plugin's resolved address. Reply-To: the user's address. From: the site default.
- Subject: `<subject_prefix>[<plugin-slug>] <subject> (<ref>)`, one line, filter `mmwp_support_subject`.
- Reference: `<ref_prefix>-<yyyymmdd>-<6 hex characters>`.
- Body (plain text): message, a `--` block (name, reply-to, plugin and version, reference, whether a report is included), then the report when included.
- With the report: `site-report-<host>-<date>.json` is attached; `mmwp_support_attach_report` turns that off.

## Limits and defaults

| Knob | Default | How |
|---|---|---|
| Capability | `manage_options` | filter `mmwp_support_capability` |
| Rate limit | 3 sends / 600 s / user | filter `mmwp_support_rate_limit` |
| Send log | last 20 in option `mmwp_support_log` (time, plugin, ref, result) | constant `Config::LOG_CAP` |
| debug.log lines | 50 | constant `Config::DEBUG_LOG_LINES` |
| Menu position | 80 | filter `mmwp_support_menu_position` |
| Transport | `MailTransport` | filter `mmwp_support_transport` |

There is no settings page.
