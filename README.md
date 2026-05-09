![Statamic Choices](images/ch_banner.png)

# Statamic Choices

> Card-style radio and checkbox fields for Statamic v5 and v6.

Statamic Choices adds a `choices` fieldtype for presenting selectable options as cards. Use it for plans, feature bundles, visual presets, product options, onboarding choices, or any field where a plain radio group or checkbox list is too thin.

## Features

- **Single or multiple selection**: Store values like radios (`single`) or checkboxes (`multiple`)
- **Rich choice cards**: Add labels, asset images, descriptions, or trusted custom HTML per option
- **Content and image variants**: Use text/content cards or full-bleed image cards
- **Flexible card widths**: Choose 100%, 50%, 33%, 25%, or 20% card widths
- **Native Statamic styling**: Version-specific control panel UI for Statamic v5 and v6, including dark mode
- **Frontend-friendly augmentation**: Selected choices augment to full option objects, including resolved assets
- **Statamic v5 + v6**: One addon package with version-specific Vue implementations

## Requirements

- Statamic 5 or 6
- PHP 8.3+

## Installation

```bash
composer require el-schneider/statamic-choices
```

## Usage

Add a **Choices** field to your blueprint and configure the available options.

### Single choice

```yaml
plan:
  type: choices
  display: Plan
  mode: single
  card_width: 50
  variant: content
  options:
    - value: basic
      label: Basic
      image: assets::cards/basic.svg
      description: Simple publishing workflow for small teams.
    - value: pro
      label: Pro
      image: assets::cards/pro.svg
      description: More automation and editorial flexibility.
      use_html: true
      html:
        code: '<span class="badge-sm">Popular</span>'
    - value: enterprise
      label: Enterprise
      image: assets::cards/enterprise.svg
      description: Advanced controls for large organizations.
```

Saved value:

```yaml
plan: pro
```

### Multiple choices

```yaml
addons:
  type: choices
  display: Add-ons
  mode: multiple
  card_width: 33
  variant: content
  options:
    - value: analytics
      label: Analytics
      image: assets::cards/analytics.svg
      description: Include dashboard and conversion reporting.
    - value: support
      label: Priority support
      image: assets::cards/support.svg
      description: Faster response times from the support team.
```

Saved value:

```yaml
addons:
  - analytics
  - support
```

## Configuration Reference

### Field settings

| Setting        | Values                        | Default   | Description                                                        |
| :------------- | :---------------------------- | :-------- | :----------------------------------------------------------------- |
| `mode`         | `single`, `multiple`          | `single`  | Whether the field behaves like radios or checkboxes.               |
| `variant`      | `content`, `image`            | `content` | Content cards show labels/text. Image cards use full-bleed images. |
| `card_width`   | `100`, `50`, `33`, `25`, `20` | `100`     | Approximate card width before wrapping.                            |
| `image_aspect` | `1/1`, `4/3`, `16/9`          | `1/1`     | Image card aspect ratio. Only used by `variant: image`.            |
| `default`      | scalar or YAML array          | —         | Default selected value(s).                                         |

### Option settings

| Setting       | Description                                                                                       |
| :------------ | :------------------------------------------------------------------------------------------------ |
| `value`       | Stored value. Must be unique.                                                                     |
| `label`       | Human-readable label shown in the control panel and augmented output. Falls back to `value`.      |
| `image`       | Optional Statamic asset. Use normal asset IDs like `assets::cards/basic.svg`.                     |
| `description` | Optional supporting text for content cards.                                                       |
| `use_html`    | Enables trusted custom HTML for content cards. When enabled, HTML replaces image and description. |
| `html`        | Trusted HTML rendered inside the card. No Antlers rendering.                                      |

## Content Cards

Content cards show the selection control, optional image, label, and either a description or custom HTML.

```yaml
variant: content
options:
  - value: pro
    label: Pro
    image: assets::cards/pro.svg
    description: More automation and editorial flexibility.
```

### Custom HTML

Custom HTML is intended for trusted blueprint configuration, not editor-entered content.

```yaml
options:
  - value: pro
    label: Pro
    use_html: true
    html:
      code: '<span class="badge-sm">Popular</span>'
```

When `use_html` is enabled on a content card, `image` and `description` are ignored for that option. Labels and selection controls remain controlled by the fieldtype.

## Image Cards

Image cards are useful for visual presets, colorways, layouts, or other choices where the image is the interface.

```yaml
layout:
  type: choices
  mode: single
  variant: image
  card_width: 25
  image_aspect: 4/3
  options:
    - value: editorial
      label: Editorial
      image: assets::layouts/editorial.jpg
    - value: portfolio
      label: Portfolio
      image: assets::layouts/portfolio.jpg
```

In image mode, labels are used for tooltips and accessible names. Descriptions and custom HTML are ignored.

## Frontend Usage

The field stores only selected values. When augmented, it returns the selected option data.

### Single mode

```antlers
{{ plan }}
  <h2>{{ label }}</h2>
  {{ if image }}<img src="{{ image:url }}" alt="{{ image:alt }}">{{ /if }}
  {{ if description }}<p>{{ description }}</p>{{ /if }}
{{ /plan }}
```

Augmented shape:

```php
[
    'value' => 'pro',
    'label' => 'Pro',
    'use_html' => false,
    'image' => Statamic\Contracts\Assets\Asset::class,
    'description' => 'More automation and editorial flexibility.',
    'html' => null,
]
```

### Multiple mode

```antlers
{{ addons }}
  <div class="addon-card">
    <h3>{{ label }}</h3>
    {{ if image }}<img src="{{ image:url }}" alt="{{ image:alt }}">{{ /if }}
    {{ if description }}<p>{{ description }}</p>{{ /if }}
  </div>
{{ /addons }}
```

Multiple selections are returned in the option order from the blueprint.

## Notes

- Unknown saved values are ignored during processing and augmentation.
- Unprefixed image paths are resolved against the only configured asset container when exactly one exists. Prefer explicit asset IDs like `assets::path/to/image.svg`.
- Custom HTML is rendered raw in the control panel. Only use trusted markup in blueprint configuration.
- Custom HTML does not render Antlers.

## Development

```bash
npm run build
./vendor/bin/pest
./vendor/bin/pint --test
npm run format:check
```

## License

MIT
