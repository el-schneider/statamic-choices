**Golden Rule: maintain AGENTS.md as you work.** Every pitfall you document saves future agents and humans from repeating the same mistake. Every stale entry left behind erodes trust in the file. Keep entries minimal and terse — only what can't be discovered by reading the code.

This file provides guidance to coding agents when working with this repository.

## Project Overview

**Choices** — Card-style choice fieldtype for Statamic

Package: `el-schneider/statamic-choices`

> ⚠️ **Pre-v1, active development.** Backwards compatibility is generally **not** a reason to hold back changes — breaking changes are acceptable and expected. **Remove this notice from AGENTS.md as soon as v1 is released.**

## Sandbox Environments

```
../statamic-choices/              # addon
../statamic-choices-test/         # Statamic v5 sandbox
../statamic-choices-test-v6/      # Statamic v6 sandbox
```

### Sandbox URLs

| Version | URL                                    |
| ------- | -------------------------------------- |
| v5      | `http://statamic-choices-test.test`    |
| v6      | `http://statamic-choices-test-v6.test` |

**Credentials:** `agent@agent.md` / `agent`

## Development Commands

```bash
npm install
npm run build
./vendor/bin/pint --test
```

## Off-Limits Files

- `vendor/` — Managed by Composer.
- `resources/dist/` — Built assets. Rebuild, don't hand-edit.
