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
