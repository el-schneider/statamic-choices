# Changelog

All notable changes to `statamic-choices` will be documented in this file.

## v0.1.1 - 2026-05-09

### What's fixed

- **Publish control panel assets after Composer install** — Fresh installs now automatically publish the packaged Vite build to `public/vendor/statamic-choices/build`, preventing `Vite manifest not found` errors in the Control Panel.

### Verification

- Verified automatic asset publishing during `composer require` in Statamic v5 and v6 sandboxes.
- CI passes for the release commit.

## v0.1.0 - 2026-05-09

### What's new

- **Card-style choice fieldtype** — Adds a `choices` fieldtype for Statamic with single-choice and multiple-choice modes.
- **Rich option cards** — Configure labels, asset images, descriptions, and trusted custom HTML per choice.
- **Content and image variants** — Use standard content cards or full-bleed image cards with configurable aspect ratios.
- **Flexible card widths** — Choose from responsive card widths from full-width down to compact grid layouts.
- **Statamic v5 and v6 support** — Ships version-specific Vue implementations for native-feeling control panel UI in both versions.
- **Frontend-friendly augmentation** — Selected values augment to full option data, including resolved Statamic assets.
