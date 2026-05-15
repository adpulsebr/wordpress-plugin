# AdPulse sGTM - LLM Documentation Hub

## Purpose

This directory contains documentation for AI assistants (Claude, GPT, o1, etc.) working on the AdPulse sGTM WordPress plugin codebase.

## How to Use This Documentation

When an LLM is working on this codebase:

1. **Start with `AGENTS.md`** (root directory) for high-level understanding
2. **Read `wordpress-plugin.md`** in this directory for detailed plugin architecture
3. **Refer to this file** for documentation structure and navigation
4. **Check implementation plan** at `/Users/gurodrigues/.claude/plans/streamed-weaving-teapot.md`

## Documentation Structure

```
wordpress-plugin/
├── AGENTS.md                          # Root LLM guide (START HERE)
└── docs/
    └── for-llms/                      # This directory
        ├── README.md                  # This file
        └── wordpress-plugin.md        # Detailed plugin architecture
```

## Key Concepts

### First-Party Proxy

All GTM traffic routes through WordPress domain instead of `googletagmanager.com`:

- Scripts load from: `https://yourdomain.com/c/gtm.js`
- Cookies set as: first-party (SameSite=Lax, domain=site domain)
- No third-party requests in browser
- Improved privacy and reliability

### Backend-Only Parsing

User agent and device detection happens in PHP, not JavaScript:

- File: `includes/class-adpulse-gtm-manager.php`
- Methods: `detect_platform()`, `detect_browser()`, `detect_device()`
- Data layer populated server-side
- Avoids blocking and ensures consistency

### Container ID from NestJS

Container ID format and source:

- Provided by AdPulse NestJS backend
- Numeric format (e.g., 12345), NOT GTM-XXXXX
- User gets ID from AdPulse dashboard
- Entered in WordPress plugin settings

### Cookie Rewriting

All upstream cookies from sGTM server are rewritten as first-party:

- Domain: set to WordPress site domain
- Path: set to WordPress site path
- SameSite: set to Lax (not None)
- Secure: set based on SSL status

## Quick Reference

### Settings Structure

```php
$default_settings = [
    'enabled' => false,
    'sgtm' => [
        'container_id' => '',           // Numeric from NestJS
        'proxy_path' => '/c/',
        'proxy_timeout' => 15,
        'ip_consent_enabled' => true,
    ],
];
```

### GTM URL Format

```php
// WordPress domain, NOT googletagmanager.com
$gtm_url = site_url( $settings['sgtm']['proxy_path'] ) . 'gtm.js?id=' . $container_id;
// Example: https://yourdomain.com/c/gtm.js?id=12345
```

### Cookie Rewriting

```php
$cookie_parts['domain'] = $site_domain;      // WordPress domain
$cookie_parts['path'] = $site_path;          // WordPress path
$cookie_parts['samesite'] = 'Lax';           // First-party
$cookie_parts['secure'] = is_ssl();          // HTTPS if SSL
```

## Critical Files

| File | Purpose | Key Methods/Features |
|------|---------|---------------------|
| `adpulse.php` | Main bootstrap | Plugin registration, constants, activation/deactivation |
| `includes/class-adpulse-proxy.php` | First-party proxy | Cookie rewriting, request forwarding |
| `includes/class-adpulse-gtm-manager.php` | GTM injection | Data layer, user agent detection (PHP) |
| `includes/class-adpulse-settings.php` | Settings API | Settings registration, sanitization |
| `includes/class-adpulse-admin-menu.php` | Admin menu | Menu registration, page rendering |
| `includes/class-adpulse-settings-page.php` | Settings page | UI rendering, status display |
| `AGENTS.md` | Root LLM guide | Architecture, common pitfalls, how-to guides |

## Common Tasks

### Adding a New Settings Field

See `AGENTS.md` section "Adding New Features"

### Adding a New Proxy Route

See `AGENTS.md` section "Adding New Features"

### Adding Data Layer Properties

See `AGENTS.md` section "Adding New Features"

## Testing Checklist

- [ ] Plugin installs and activates
- [ ] Admin menu appears
- [ ] Settings page renders
- [ ] Settings save and persist
- [ ] Proxy intercepts `/c/` requests
- [ ] Cookies rewritten as first-party
- [ ] GTM scripts inject with correct hostname
- [ ] Data layer populated correctly
- [ ] User agent detection works (PHP)
- [ ] IP address included only with consent

## Common Pitfalls

1. **Using `googletagmanager.com`** - Use WordPress hostname instead
2. **Setting SameSite=None** - Use Lax for first-party
3. **Parsing UA in JS** - Do it in PHP only
4. **Forgetting cookie domain** - Must be site domain
5. **Using GTM-XXXXX format** - Container ID is numeric
6. **Missing data-cfasync="false"** - Required on all script tags
7. **Including IP without consent** - Check consent first

## Related Documentation

- **Implementation Plan**: `/Users/gurodrigues/.claude/plans/streamed-weaving-teapot.md`
- **Plugin Details**: `wordpress-plugin.md` (this directory)
- **Root Guide**: `AGENTS.md` (plugin root)

## Support

- Documentation: https://docs.adpulse.com.br
- Dashboard: https://dashboard.adpulse.com.br
- Support: support@adpulse.com.br
