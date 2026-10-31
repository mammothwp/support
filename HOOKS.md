# Hooks

Every custom hook fired by `mammothwp/support`. All names are prefixed `mmwp_support_`.
Each `apply_filters()` / `do_action()` call has a docblock directly above it.

| Name | Type | Parameters | Where fired |
|---|---|---|---|
| `mmwp_support_booted` | action | `string $version`, `string $path` | `Kernel::start()`, once the winning copy has booted. |
| `mmwp_support_capability` | filter | `string $capability`, `bool $network` | `Config::capability()`. Default `manage_options` (`manage_network_options` on the network screen). |
| `mmwp_support_email` | filter | `string $email`, `string $slug` | `Plugin_Factory::email()`. Wins over the constant, the array and the JSON file. |
| `mmwp_support_menu` | filter | `?string $location`, `string $default` | `Kernel::location()`. Return `toplevel`, `tools`, `settings`, `hidden` or `parent:<menu-slug>`; `null` = no override. Wins over `MMWP_SUPPORT_MENU`. |
| `mmwp_support_menu_position` | filter | `int $position` | `Config::menu_position()`. Default `80`. |
| `mmwp_support_menu_icon` | filter | `string $icon` | `Kernel::icon()`. SVG data URI (20x20 viewBox, one path, `fill="black"`) or dashicon name for the Mammoth WP menu icon. Invalid values fall back to `dashicons-sos`. |
| `mmwp_support_branding` | filter | `array $values`, `string $mode` | `Kernel::branding()`. Wins over everything, including `MMWP_SUPPORT_WHITELABEL`. |
| `mmwp_support_action_link` | filter | `bool $enabled`, `string $slug` | `Config::action_link_enabled()`. Return `false` to drop the "Support" link on the Plugins screen. |
| `mmwp_support_subject` | filter | `string $subject`, `string $prefix`, `string $slug`, `string $user_subject`, `string $ref` | `Ticket_Formatter::subject()`. Newlines are stripped afterwards. |
| `mmwp_support_rate_limit` | filter | `array{max:int,window:int} $limit`, `int $user_id` | `Config::rate_limit()`. Default `3` sends per `600` seconds per user. |
| `mmwp_support_attach_report` | filter | `bool $attach`, `string $slug` | `Config::attach_report()`. Return `false` to send the report in the body only. |
| `mmwp_support_report_sections` | filter | `Section_Interface[] $sections` | `Report_Builder::all_sections()`. Contract: no secrets. Redaction runs over everything anyway. |
| `mmwp_support_transport` | filter | `Transport_Interface $transport` | `Kernel::transport()`. Only `Mail_Transport` (wp_mail) ships. |

Site-owner constants (not hooks): `MMWP_SUPPORT_EMAIL_<SLUG_UPPER_UNDERSCORED>`, `MMWP_SUPPORT_MENU`, `MMWP_SUPPORT_WHITELABEL`.
