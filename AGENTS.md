# Ad Pulse WordPress Plugin — Agent Instructions

## Primary Instructions

This repository uses a `CLAUDE.md` file (WordPress ecosystem convention) as its primary agent instructions file. For the complete project guide, read:

- [CLAUDE.md](./CLAUDE.md) — Full architecture, patterns, constraints, and common pitfalls
- [SKILLS.md](./SKILLS.md) — Skills, tool preferences, and agent activations
- [docs/for-llms/README.md](./docs/for-llms/README.md) — LLM documentation hub (if present)

## Quick Reference

- **Type**: WordPress plugin (PHP)
- **Core Function**: First-party sGTM proxy — all GTM traffic routes through the publisher's WordPress domain (`yourdomain.com/c/`)
- **Container ID**: Numeric (from NestJS backend), NOT GTM-XXXXX format
- **File Naming**: `class-adpulse-{feature}.php`
- **User Agent Parsing**: Done in PHP backend, never in JavaScript
- **Key Constraint**: Cookie rewriting must preserve publisher domain context

## Agent Role

This repository is assigned to the **Backend Sub-Agent (Role B)** in the Ad Pulse three-role orchestration hierarchy.

## LightRAG Context

- Search Collection: `wordpress-plugin_voyage_code_3`
- **Always query LightRAG before generating code** to understand existing patterns and plugin conventions.

## Skills & Capabilities

See [./SKILLS.md](./SKILLS.md) for tool preferences, agent skill activations, and limitations.

# AdPulse WordPress Plugin - AI Assistant Guide

## Purpose

Integrates WordPress sites with AdPulse server-side Google Tag Manager (sGTM) container infrastructure using first-party tracking.

## Key Architecture Decisions

### First-Party Proxy

- All GTM traffic routes through WordPress domain: `https://yourdomain.com/c/`
- Original `googletagmanager.com` requests intercepted and proxied
- Cookies rewritten as first-party (SameSite=Lax, domain=site domain)
- GTM scripts load from WordPress hostname, NOT `googletagmanager.com`

### Backend-Only Parsing

- User agent parsing happens in PHP (`class-adpulse-gtm-manager.php`)
- NOT in frontend JavaScript (avoids blocking, ensures consistency)
- Uses `getallheaders()`, `$_SERVER['HTTP_USER_AGENT']`
- Methods: `detect_platform()`, `detect_browser()`, `detect_device()`

### Container ID from NestJS

- Container ID provided by AdPulse NestJS backend
- NOT Google GTM container ID (GTM-XXXXX)
- User creates container in AdPulse dashboard, gets ID, enters in plugin
- ID is numeric (e.g., 12345), not alphanumeric
- GTM URL format: `site_url('/c/gtm.js?id=' . $container_id)`

### File Naming Convention

- Prefix: `AdPulse_`
- Pattern: `class-adpulse-{feature}.php`
- Example: `class-adpulse-proxy.php` → `AdPulse_Proxy` class

### Settings Structure

All GTM settings grouped under `sgtm` parent object:

```php
$default_settings = [
    'enabled' => false,
    'sgtm' => [
        'container_id' => '',           // From NestJS backend (numeric)
        'proxy_path' => '/c/',
        'proxy_timeout' => 15,
        'ip_consent_enabled' => true,
    ],
];
```

## Common Pitfalls

1. **Don't use `googletagmanager.com` in scripts** - use WordPress hostname
2. **Don't set SameSite=None** - first-party uses Lax or Strict
3. **Don't parse UA in JS** - do it in PHP class (class-adpulse-gtm-manager.php)
4. **Don't forget cookie domain** - must be site domain for first-party
5. **Don't use GTM-XXXXX format** - Container ID is numeric from NestJS
6. **Don't forget data-cfasync="false"** - required for all script tags
7. **Don't skip IP consent check** - only include IP when consent granted

## Critical Files

- `/wordpress-plugin/adpulse.php` - Main bootstrap and plugin registration
- `/wordpress-plugin/includes/class-adpulse-proxy.php` - First-party proxy with cookie rewriting
- `/wordpress-plugin/includes/class-adpulse-gtm-manager.php` - Data layer + GTM injection
- `/wordpress-plugin/includes/class-adpulse-settings.php` - Settings API with sgtm grouping
- `/wordpress-plugin/includes/class-adpulse-admin-menu.php` - Admin menu registration
- `/wordpress-plugin/includes/class-adpulse-settings-page.php` - Settings page rendering
- `/wordpress-plugin/AGENTS.md` - This file (root LLM guide)
- `/wordpress-plugin/docs/for-llms/README.md` - LLM documentation hub

## How It Works

### Request Flow

1. User visits WordPress site
2. Plugin injects GTM script from WordPress domain: `yourdomain.com/c/gtm.js?id=12345`
3. WordPress rewrite rule intercepts `/c/*` requests
4. `AdPulse_Proxy` forwards to sGTM server: `gtm.adpulse.com.br/c12345/*`
5. sGTM server processes request
6. Response cookies rewritten as first-party (domain=site domain, SameSite=Lax)
7. Response forwarded to browser with first-party cookies

### Data Layer Population

- Data layer built in PHP (`build_data_layer()` method)
- Contains page data, user data, website data
- User agent detection in PHP only
- IP address only included when consent enabled
- Output in `wp_head` action with priority 1

### Cookie Rewriting Logic

All upstream cookies from sGTM server are rewritten:

```php
$cookie_parts['domain'] = $site_domain;      // WordPress domain
$cookie_parts['path'] = $site_path;          // WordPress path
$cookie_parts['samesite'] = 'Lax';           // First-party, not None
$cookie_parts['secure'] = is_ssl();          // HTTPS if SSL
```

## Adding New Features

### Adding a New Settings Field

1. In `class-adpulse-settings.php`, add field registration in `register_fields()`:

```php
add_settings_field(
    'adpulse_sgtm_new_field',
    __( 'New Field', 'adpulse' ),
    array( $this, 'render_new_field' ),
    'adpulse-settings',
    'adpulse_sgtm_section'
);
```

2. Add render method:

```php
public function render_new_field() {
    $settings = self::get_settings();
    $value = isset( $settings['sgtm']['new_field'] ) ? $settings['sgtm']['new_field'] : '';
    ?>
    <input
        type="text"
        name="adpulse_settings[sgtm][new_field]"
        value="<?php echo esc_attr( $value ); ?>"
        class="regular-text"
    >
    <?php
}
```

3. Add sanitization in `sanitize_settings()`:

```php
$sanitized['sgtm']['new_field'] = sanitize_text_field( $input['sgtm']['new_field'] ?? '' );
```

### Adding a New Proxy Route

1. In `class-adpulse-proxy.php`, modify `add_rewrite_rules()` to handle new pattern:

```php
add_rewrite_rule(
    '^' . $path_pattern . '/new-route/(.*)$',
    'index.php?adpulse_proxy=1&adpulse_proxy_path=new-route/$matches[1]',
    'top'
);
```

2. Handle in `proxy_request()` method.

### Adding Data Layer Properties

In `class-adpulse-gtm-manager.php`, add to `build_data_layer()`:

```php
$data_layer = array(
    'page' => $this->get_page_data(),
    'user' => $this->get_user_data( $settings ),
    'website' => $this->get_website_data(),
    'custom' => $this->get_custom_data(),  // New property
);
```

Add new method:

```php
private function get_custom_data() {
    return array(
        'property1' => 'value1',
        'property2' => 'value2',
    );
}
```

## Testing

### Manual Testing Checklist

- [ ] Plugin installs and activates
- [ ] Admin menu appears under "AdPulse"
- [ ] Settings page renders correctly
- [ ] Settings save and persist
- [ ] Container ID field accepts numeric values only
- [ ] Proxy path auto-formats with leading/trailing slashes
- [ ] Proxy intercepts requests to `/c/`
- [ ] Cookies rewritten as first-party
- [ ] GTM scripts inject with correct hostname
- [ ] Data layer populated correctly
- [ ] User agent detection works (PHP)
- [ ] IP address included only with consent
- [ ] No JavaScript UA parsing

### Debugging

Enable WordPress debug mode:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Check debug log: `wp-content/debug.log`

## Support & Documentation

- Full Documentation: https://docs.adpulse.com.br
- Dashboard: https://dashboard.adpulse.com.br
- Support: support@adpulse.com.br
- LLM Docs: `/docs/for-llms/`

## Agent Configuration

### Code Generation Rules
- Language: PHP (WordPress plugin conventions)
- Run `php -l <file>` for syntax validation before completing tasks
- Follow WordPress Coding Standards for PHP
- Query `context7` MCP for latest WordPress plugin development docs when needed

### Testing Rules
- Validate PHP syntax: `php -l path/to/file.php`
- Manual testing checklist provided in root AGENTS.md
- Debug mode: define WP_DEBUG, WP_DEBUG_LOG, WP_DEBUG_DISPLAY in wp-config.php

### Plugin Architecture
- Bootstrap file: adpulse.php (plugin registration, constants, autoloader)
- Includes: class-adpulse-proxy.php (first-party proxy), class-adpulse-gtm-manager.php (data layer + GTM injection), class-adpulse-settings.php (Settings API), class-adpulse-admin-menu.php, class-adpulse-settings-page.php
- File naming: `class-adpulse-{feature}.php` → class `AdPulse_{Feature}`
- Settings grouped under `sgtm` parent object in WordPress options

### Key Rules
- Container ID is numeric (from NestJS backend), NOT GTM-XXXXX format
- All GTM traffic routes through WordPress domain, NOT googletagmanager.com
- Cookie rewriting: SameSite=Lax, domain=site domain (first-party)
- User agent parsing in PHP only, never in frontend JavaScript
- IP consent check required before including IP in data layer
- All script tags need `data-cfasync="false"` attribute
- No package.json or composer.json — pure PHP plugin

### SSH & Production Access
- Production server: root@177.136.255.193 (via ssh-production MCP)
- Only use for: debugging on customer WordPress sites
- NEVER modify production code directly — go through PR workflow