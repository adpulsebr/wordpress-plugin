# wordpress-plugin — Skills & Capabilities

## Role
**Backend Sub-Agent (Role B)** — WordPress plugin for first-party sGTM proxy.

## Primary Capabilities
- WordPress plugin (PHP) for first-party server-side GTM proxy
- Routes all GTM traffic through publisher's WordPress domain (`yourdomain.com/c/`)
- Backend-only user agent parsing (PHP, not JavaScript)
- Cookie rewriting to preserve publisher domain context
- Container configuration from NestJS backend

## LightRAG Collection
`wordpress-plugin_voyage_code_3`

## Agent Skills to Activate
*(none — WordPress/PHP development, no special skills required)*

## Key Constraints
- **Container IDs are numeric** (from NestJS), NOT GTM-XXXXX format
- **File naming**: `class-adpulse-{feature}.php`
- **User agent parsing MUST be server-side** (PHP), never in JavaScript
- Follow WordPress plugin conventions and coding standards
- Cookie rewriting must preserve publisher domain context

## References
- [CLAUDE.md](./CLAUDE.md) — Full architecture, patterns, constraints, common pitfalls
- [AGENTS.md](./AGENTS.md) — Quick reference and role assignment
