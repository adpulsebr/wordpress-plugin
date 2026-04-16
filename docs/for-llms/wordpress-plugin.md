# AdPulse WordPress Plugin Architecture

## Overview

The AdPulse WordPress plugin integrates WordPress sites with AdPulse server-side Google Tag Manager (sGTM) using first-party tracking. This document provides detailed architecture information for AI assistants working on the codebase.

## File Structure

```
wordpress-plugin/
├── adpulse.php                             # Main plugin file & bootstrap
├── includes/
│   ├── class-adpulse-admin-menu.php        # Admin menu registration
│   ├── class-adpulse-settings.php          # Settings API & validation
│   ├── class-adpulse-settings-page.php     # Settings page rendering
│   ├── class-adpulse-proxy.php             # Request proxy handler
│   └── class-adpulse-gtm-manager.php       # GTM injection & data layer
├── assets/
│   ├── css/admin-styles.css                # Admin UI styling
│   └── js/admin-scripts.js                 # Admin validation & interactions
├── templates/
│   └── settings-page.php                   # Settings form template
├── readme.txt                              # WordPress.org readme
├── CLAUDE.md                               # LLM documentation (root)
└── docs/
    └── for-llms/
        ├── README.md
        └── wordpress-plugin.md             # This file
```

## Component Architecture

### 1. Main Plugin File (`adpulse.php`)

**Purpose**: Plugin bootstrap and component initialization

**Key Responsibilities**:
- Define plugin constants (version, paths, URLs)
- Load all required class files
- Initialize all components (admin menu, settings, proxy, GTM manager)
- Handle plugin activation/deactivation
- Enqueue admin and frontend assets

**Key Methods**:
- `__construct()`: Register WordPress hooks
- `init_hooks()`: Set up all WordPress hooks
- `init()`: Initialize all plugin components
- `activate()`: Set default settings, flush rewrite rules
- `deactivate()`: Flush rewrite rules

**Constants**:
- `ADPULSE_VERSION`: Plugin version (1.0.0)
- `ADPULSE_PLUGIN_DIR`: Plugin directory path
- `ADPULSE_PLUGIN_URL`: Plugin URL
- `ADPULSE_PLUGIN_BASENAME`: Plugin basename

### 2. Admin Menu (`includes/class-adpulse-admin-menu.php`)

**Purpose**: Register admin menu and submenu items

**Key Responsibilities**:
- Register main "AdPulse" menu item
- Register "Settings" submenu
- Register "Documentation" submenu
- Render settings page
- Render documentation page

**Key Methods**:
- `register_menu()`: Add menu and submenu items
- `display_settings_page()`: Render settings page
- `display_docs_page()`: Render documentation page

**Menu Structure**:
- AdPulse (main menu)
  - Settings (default)
  - Documentation

### 3. Settings API (`includes/class-adpulse-settings.php`)

**Purpose**: Manage plugin settings using WordPress Settings API

**Key Responsibilities**:
- Register settings with sanitization
- Register settings sections
- Register settings fields
- Sanitize and validate user input
- Provide default settings

**Settings Structure**:
```php
$default_settings = [
    'enabled' => false,
    'sgtm' => [
        'container_id' => '',           // Numeric from NestJS backend
        'proxy_path' => '/c/',
        'proxy_timeout' => 15,
        'ip_consent_enabled' => true,
    ],
];
```

**Key Methods**:
- `get_defaults()`: Return default settings array
- `get_settings()`: Get current settings (with defaults applied)
- `register_settings()`: Register settings with WordPress
- `register_sections()`: Register settings sections
- `register_fields()`: Register settings fields
- `render_*_field()`: Render individual field HTML
- `sanitize_settings()`: Sanitize and validate settings

**Sanitization Rules**:
- `enabled`: Boolean
- `sgtm.container_id`: Numeric only (remove non-digits)
- `sgtm.proxy_path`: Ensure leading/trailing slashes
- `sgtm.proxy_timeout`: Between 1 and 60
- `sgtm.ip_consent_enabled`: Boolean

### 4. Settings Page (`includes/class-adpulse-settings-page.php`)

**Purpose**: Render the settings page UI

**Key Responsibilities**:
- Render settings form
- Display status indicators
- Show configuration status
- Provide quick links to documentation and dashboard

**Key Methods**:
- `render()`: Main render method
- Display success/error notices
- Show plugin status (active/inactive)
- Display container ID and GTM URL
- Render quick links

**UI Components**:
- Settings form with all fields
- Status table showing current configuration
- Quick links to documentation and dashboard
- Warning notices when misconfigured

### 5. Proxy Handler (`includes/class-adpulse-proxy.php`)

**Purpose**: Handle first-party proxy requests to sGTM server

**Key Responsibilities**:
- Add WordPress rewrite rules for proxy path
- Intercept requests to `/c/*`
- Forward requests to sGTM server
- Rewrite upstream cookies as first-party
- Return responses to browser

**Request Flow**:
1. Browser requests `https://yourdomain.com/c/gtm.js?id=12345`
2. WordPress rewrite rule intercepts `/c/*` requests
3. `handle_proxy_request()` processes request
4. Request forwarded to `https://gtm.adpulse.com.br/c12345/gtm.js?id=12345`
5. Response received from sGTM server
6. Cookies rewritten as first-party
7. Response forwarded to browser

**Key Methods**:
- `add_rewrite_rules()`: Add rewrite rules for proxy path
- `handle_proxy_request()`: Main proxy request handler
- `proxy_request()`: Forward request to sGTM server
- `prepare_request_headers()`: Prepare headers for upstream request
- `get_request_headers()`: Get request headers from $_SERVER
- `rewrite_cookies()`: Rewrite cookies as first-party
- `parse_cookie_string()`: Parse cookie string into parts
- `build_cookie_string()`: Build cookie string from parts
- `send_response()`: Send response to browser

**Cookie Rewriting Logic**:
```php
$cookie_parts['domain'] = $site_domain;      // WordPress domain
$cookie_parts['path'] = $site_path;          // WordPress path
$cookie_parts['samesite'] = 'Lax';           // First-party, not None
$cookie_parts['secure'] = is_ssl();          // HTTPS if SSL
```

### 6. GTM Manager (`includes/class-adpulse-gtm-manager.php`)

**Purpose**: Inject GTM scripts and populate data layer

**Key Responsibilities**:
- Inject GTM scripts in `<head>`
- Inject noscript in `<body>`
- Build data layer with page, user, and website data
- Detect user agent, platform, browser, device (PHP only)
- Include IP address only when consent granted

**Data Layer Structure**:
```php
$data_layer = [
    'page' => [
        'type' => 'single|page|home|category|tag|...',
        'title' => 'Page Title',
        'url' => 'https://example.com/page/',
        'language' => 'en_US',
        'template' => 'page-template-name.php',
        'id' => 123,                           // For singular posts
        'postType' => 'post',
        'published' => '2024-01-01T00:00:00+00:00',
        'modified' => '2024-01-01T00:00:00+00:00',
        'author' => 'Author Name',
        'categories' => ['Category 1', 'Category 2'],
        'tags' => ['Tag 1', 'Tag 2'],
    ],
    'user' => [
        'loggedIn' => true,
        'id' => 1,
        'username' => 'admin',
        'roles' => ['administrator'],
        'ip' => '192.168.1.1',                // Only with consent
    ],
    'website' => [
        'name' => 'Site Name',
        'description' => 'Site Description',
        'url' => 'https://example.com/',
        'adminEmail' => 'admin@example.com',
        'charset' => 'UTF-8',
        'isMultisite' => false,
    ],
];
```

**Key Methods**:
- `inject_gtm_scripts()`: Inject GTM scripts in head
- `inject_noscript()`: Inject noscript in body
- `build_data_layer()`: Build complete data layer
- `get_page_data()`: Get page-specific data
- `get_user_data()`: Get user-specific data (with consent check)
- `get_website_data()`: Get website metadata
- `get_page_type()`: Detect current page type
- `get_user_ip()`: Get user IP address
- `has_consent()`: Check if user has granted consent
- `get_user_agent_data()`: Parse user agent (public method)
- `detect_platform()`: Detect OS/platform
- `detect_browser()`: Detect browser
- `detect_device()`: Detect device type

**User Agent Detection (PHP Only)**:
- **Platform**: Windows, Mac OS X, Linux, Android, iOS
- **Browser**: Chrome, Firefox, Safari, Edge, Opera, Internet Explorer
- **Device**: Desktop, Mobile, Tablet

**IP Address Consent**:
- IP address only included when `ip_consent_enabled` is true
- Checks for common consent mechanisms:
  - `cookieconsent_status` cookie
  - `OptanonConsent` cookie (OneTrust)
  - `adpulse_consent` cookie

### 7. Admin Styles (`assets/css/admin-styles.css`)

**Purpose**: Style the admin settings page

**Key Components**:
- Two-column card layout (responsive)
- Settings form styling
- Status badge styling (active/inactive)
- Responsive design for mobile

**Key Classes**:
- `.adpulse-settings-page`: Main container
- `.adpulse-cards`: Card grid layout
- `.adpulse-card`: Individual card
- `.adpulse-status-badge`: Status indicator
- `.adpulse-status-active`: Green badge
- `.adpulse-status-inactive`: Red badge

### 8. Admin Scripts (`assets/js/admin-scripts.js`)

**Purpose**: Client-side validation and interactions

**Key Features**:
- Validate container ID (numeric only)
- Format proxy path (ensure slashes)
- Show success/error notices
- Auto-dismiss notices after 5 seconds

**Key Methods**:
- `init()`: Initialize and bind events
- `bindEvents()`: Bind event listeners
- `validateContainerId()`: Validate numeric input
- `formatContainerId()`: Trim whitespace
- `formatProxyPath()`: Ensure leading/trailing slashes
- `showNotice()`: Display WordPress-style notice
- `escapeHtml()`: Escape HTML for safety

### 9. Settings Template (`templates/settings-page.php`)

**Purpose**: Template file for settings page

**Note**: Currently not used directly - rendering handled in `class-adpulse-settings-page.php`. Exists for future customization and plugin extensibility.

## WordPress Hooks Used

### Actions
- `plugins_loaded`: Load text domain
- `init`: Initialize components, add rewrite rules
- `admin_init`: Register settings
- `admin_menu`: Register admin menu
- `admin_enqueue_scripts`: Enqueue admin assets
- `wp_enqueue_scripts`: Enqueue frontend assets
- `wp_head`: Inject GTM scripts (priority 1)
- `wp_footer`: Inject noscript
- `template_redirect`: Handle proxy requests
- `register_activation_hook`: Plugin activation
- `register_deactivation_hook`: Plugin deactivation

### Filters
- `option_adpulse_settings`: Settings sanitization
- `rewrite_rules_array`: Add proxy rewrite rules

### Query Variables
- `adpulse_proxy`: Flag for proxy requests (0 or 1)
- `adpulse_proxy_path`: Proxy request path

## GTM Script Injection

### Head Script
```html
<!-- AdPulse Data Layer -->
<script data-cfasync="false">
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({...data layer...});
</script>
<!-- Google Tag Manager (First-Party via AdPulse) -->
<script data-cfasync="false">(function(w,d,s,l,i){...})(window,document,'script','dataLayer','12345');</script>
```

### Body NoScript
```html
<!-- Google Tag Manager (NoScript) -->
<noscript>
  <iframe src="https://yourdomain.com/c/gtm.js?id=12345"
  height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>
```

**Important**: All script tags include `data-cfasync="false"` to prevent Cloudflare from interfering.

## Cookie Rewriting Process

### Input Cookie (from sGTM server)
```
Set-Cookie: _ga=GA1.2.1234567890.1234567890; Domain=.adpulse.com.br; Path=/; Expires=...
```

### Output Cookie (rewritten as first-party)
```
Set-Cookie: _ga=GA1.2.1234567890.1234567890; Domain=yourdomain.com; Path=/yourpath/; SameSite=Lax; Secure; Expires=...
```

**Changes Made**:
- Domain changed to WordPress site domain
- Path changed to WordPress site path
- SameSite set to Lax (first-party)
- Secure set based on SSL status

## User Agent Detection Examples

### Input
```php
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
```

### Output
```php
[
    'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36...',
    'platform' => 'Mac OS X',
    'browser' => 'Chrome',
    'device' => 'Desktop',
]
```

## Consent Mechanisms Supported

The plugin checks for these consent indicators:

1. **Cookie Consent**: `cookieconsent_status=allow`
2. **OneTrust**: `OptanonConsent` cookie exists
3. **Custom**: `adpulse_consent=granted`

## Security Considerations

1. **All input sanitized**: Settings sanitized via `sanitize_settings()`
2. **Output escaped**: All output uses `esc_html()`, `esc_url()`, `esc_attr()`, etc.
3. **Nonces used**: AJAX requests protected with nonces
4. **Capability checks**: Admin pages check `manage_options` capability
5. **SSL verification**: Proxy requests verify SSL certificates
6. **IP validation**: IP addresses validated before inclusion
7. **XSS protection**: Data layer output via `wp_json_encode()`

## Performance Considerations

1. **Lazy loading**: GTM scripts loaded asynchronously
2. **Minimal database queries**: Settings cached via `get_option()`
3. **Efficient regex**: User agent detection uses efficient patterns
4. **No external requests on frontend**: All GTM requests proxied
5. **Conditional loading**: Scripts only loaded when enabled
6. **Optimized assets**: Admin assets only loaded on settings page

## Extensibility Points

1. **Custom data layer properties**: Add to `build_data_layer()` method
2. **Custom settings fields**: Add to `register_fields()` method
3. **Custom proxy routes**: Add to `add_rewrite_rules()` method
4. **Custom consent mechanisms**: Modify `has_consent()` method
5. **Custom cookie attributes**: Modify `rewrite_cookies()` method
6. **Custom user agent detection**: Add to detect methods

## Testing Guidelines

### Unit Testing
- Test settings sanitization
- Test data layer building
- Test user agent detection
- Test cookie parsing and building
- Test consent detection

### Integration Testing
- Test plugin activation/deactivation
- Test settings save and retrieval
- Test proxy request handling
- Test cookie rewriting
- Test GTM script injection

### Manual Testing
- Install and activate plugin
- Configure container ID
- Verify GTM scripts load from correct domain
- Verify cookies are first-party
- Verify data layer contains correct data
- Test with various user agents
- Test consent mechanisms

## Troubleshooting

### GTM Scripts Not Loading
1. Check if plugin is enabled in settings
2. Verify container ID is set
3. Check browser console for errors
4. Verify rewrite rules are flushed
5. Check if caching plugin is interfering

### Cookies Not First-Party
1. Check `rewrite_cookies()` method is being called
2. Verify `$site_domain` is correct
3. Check SameSite attribute is set to Lax
4. Verify Secure attribute matches SSL status
5. Check if other plugins are modifying cookies

### Data Layer Empty
1. Check if GTM Manager is initialized
2. Verify `inject_gtm_scripts()` is running
3. Check `build_data_layer()` for errors
4. Verify settings are loaded correctly
5. Check PHP error logs

### Proxy Not Working
1. Verify rewrite rules are added
2. Check if `.htaccess` is writable
3. Flush permalinks (Settings > Permalinks > Save)
4. Check if sGTM server URL is correct
5. Verify timeout setting is sufficient

## Future Enhancements

1. **Custom consent manager integration**: Support for popular consent plugins
2. **Data layer event tracking**: Add custom event tracking
3. **Preview mode**: Support for GTM preview mode
4. **Debug mode**: Enhanced debugging and logging
5. **Performance monitoring**: Track GTM performance
6. **A/B testing**: Support for GTM experiments
7. **Custom dimensions**: Add WordPress-specific dimensions
8. **E-commerce tracking**: WooCommerce integration

## Related Documentation

- **Root Guide**: `CLAUDE.md` (plugin root)
- **LLM Hub**: `README.md` (this directory)
- **Implementation Plan**: `/Users/gurodrigues/.claude/plans/streamed-weaving-teapot.md`
- **WordPress.org**: https://wordpress.org/plugins/adpulse/
- **Full Docs**: https://docs.adpulse.com.br
