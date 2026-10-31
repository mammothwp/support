# White-label guide for agencies

Everything is configured in `wp-config.php` (or a must-use plugin). White-label wins over any plugin's own branding and over Mammoth WP defaults.

## Fully neutral

```php
define( 'MMWP_SUPPORT_WHITELABEL', true );
```

Result: menu and page title "Support", `dashicons-sos`, no logo, subject prefix `[Support]`, references `SUP-<yyyymmdd>-<6 chars>`, no "Powered by", no vendor column. No Mammoth WP name, mark or link appears in the screen, the emails or the plugin action links. The site report still lists package versions (needed for debugging), under the heading "Support package". Plugin names themselves come from the plugins and are not rewritten.

## Your own brand

```php
define( 'MMWP_SUPPORT_WHITELABEL', [
    'name'           => 'Acme Care',
    'menu_title'     => 'Acme Care',
    'page_title'     => 'Acme Care Support',
    'icon'           => 'dashicons-heart',
    'logo_url'       => '/wp-content/uploads/acme-mark.svg', // local only
    'subject_prefix' => '[Acme Care]',
    'ref_prefix'     => 'ACME',
] );
```

Unset keys stay neutral. A `logo_url` on another host is silently dropped; the logo is never loaded from a remote site.

## Your own inbox

```php
define( 'MMWP_SUPPORT_EMAIL_MY_PLUGIN', 'helpdesk@agency.example' );
// or per plugin in code:
add_filter( 'mmwp_support_email', fn( $email, $slug ) => 'my-plugin' === $slug ? 'helpdesk@agency.example' : $email, 10, 2 );
```

## Placement

```php
define( 'MMWP_SUPPORT_MENU', 'settings' );           // Settings > Support
add_filter( 'mmwp_support_menu', fn() => 'parent:acme-care' ); // under your own menu
```

## Filter for dynamic rules

```php
add_filter( 'mmwp_support_branding', function ( array $values ): array {
    $values['name'] = get_bloginfo( 'name' ) . ' help';
    return $values;
} );
```

The filter runs last and wins over the constant.

## Checklist

- Menu, page title, header and "Powered by" read from the resolved branding.
- Email subject prefix and reference prefix read from the resolved branding.
- Check with `wp mmwp support plugins` and `wp mmwp support send --plugin=<slug> --message=test --dry-run`.
