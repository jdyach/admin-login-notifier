# Admin Login Notifier

Sends an email alert to the site admin whenever a user logs into the WordPress dashboard. Configurable notification email, role filtering, and an option to notify on all logins regardless of role.

## Features
- Hooks into `wp_login` to fire on every successful login
- Email includes username, role(s), IP address, timestamp, and admin URL
- Handles proxy headers (`X-Forwarded-For`, etc.) for accurate IP detection
- Role-based filtering — only notify on logins from specific roles (e.g. Administrator only)
- "Notify all" override to catch every login
- Settings page under **Settings → Login Notifier**

## WordPress Concepts Demonstrated
| Concept | Where Used |
|---|---|
| `wp_login` action hook | `class-aln-notifier.php` |
| `WP_User` object + `$user->roles` | `class-aln-notifier.php` |
| `wp_mail()` | `class-aln-notifier.php` |
| `wp_date()` with site date/time format options | `class-aln-notifier.php` |
| IP detection via `$_SERVER` headers | `class-aln-notifier.php` |
| `wp_roles()->get_names()` | `class-aln-settings.php` |
| Settings API | `class-aln-settings.php` |

## Requirements
- WordPress 6.0+ / PHP 8.0+
## License
GPL-2.0-or-later
