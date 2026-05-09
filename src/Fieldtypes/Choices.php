<?php

namespace ElSchneider\Choices\Fieldtypes;

use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Fields\Fieldtype;

class Choices extends Fieldtype
{
    protected $categories = ['controls'];

    protected $selectableInForms = true;

    protected $indexComponent = 'tags';

    public function preload(): array
    {
        return [
            'options' => $this->normalizedOptionsForPublish(),
        ];
    }

    public function preProcess($value)
    {
        if ($value === null) {
            $value = $this->config('default');
        }

        return $this->isMultiple()
            ? $this->sortKnownValues($this->wrapValue($value))
            : $this->knownSingleValue($value);
    }

    public function process($value)
    {
        return $this->isMultiple()
            ? $this->sortKnownValues($this->wrapValue($value))
            : $this->knownSingleValue($value);
    }

    public function augment($value)
    {
        if ($this->isMultiple()) {
            $selected = $this->sortKnownValues($this->wrapValue($value));

            return collect($selected)
                ->map(fn ($selectedValue) => $this->augmentedOption($selectedValue))
                ->filter()
                ->values()
                ->all();
        }

        $selected = $this->knownSingleValue($value);

        return $selected === null ? null : $this->augmentedOption($selected);
    }

    public function preProcessIndex($value)
    {
        return $this->selectedLabels($value);
    }

    public function extraRenderableFieldData(): array
    {
        return [
            'options' => collect($this->normalizedOptions())
                ->mapWithKeys(fn ($option) => [$option['value'] => $option['label']])
                ->all(),
        ];
    }

    protected function configFieldItems(): array
    {
        return [
            [
                'display' => __('Choices'),
                'fields' => [
                    'options' => [
                        'display' => __('Options'),
                        'instructions' => __('Configure the cards editors may choose from.'),
                        'type' => 'grid',
                        'mode' => 'stacked',
                        'min_rows' => 1,
                        'fields' => [
                            [
                                'handle' => 'value',
                                'field' => [
                                    'display' => __('Value'),
                                    'type' => 'text',
                                    'validate' => ['required'],
                                ],
                            ],
                            [
                                'handle' => 'label',
                                'field' => [
                                    'display' => __('Label'),
                                    'type' => 'text',
                                    'validate' => ['required'],
                                ],
                            ],
                            [
                                'handle' => 'use_html',
                                'field' => [
                                    'display' => __('Use Custom HTML'),
                                    'type' => 'toggle',
                                    'default' => false,
                                    'instructions' => __('Content variant only. Replace the image and description with trusted custom HTML.'),
                                ],
                            ],
                            [
                                'handle' => 'image',
                                'field' => [
                                    'display' => __('Image'),
                                    'type' => 'assets',
                                    'max_files' => 1,
                                    'mode' => 'grid',
                                    'instructions' => __('Optional asset displayed in content cards or as the full image card.'),
                                    'unless' => [
                                        'use_html' => true,
                                    ],
                                ],
                            ],
                            [
                                'handle' => 'description',
                                'field' => [
                                    'display' => __('Description'),
                                    'type' => 'textarea',
                                    'instructions' => __('Content variant only. Optional supporting text.'),
                                    'unless' => [
                                        'use_html' => true,
                                    ],
                                ],
                            ],
                            [
                                'handle' => 'html',
                                'field' => [
                                    'display' => __('Custom HTML'),
                                    'type' => 'code',
                                    'mode' => 'htmlmixed',
                                    'mode_selectable' => false,
                                    'show_mode_label' => false,
                                    'instructions' => __('Content variant only. Trusted HTML rendered raw. Replaces the image and description; the label and selection indicator stay controlled by the fieldtype.'),
                                    'if' => [
                                        'use_html' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'display' => __('Appearance & Behavior'),
                'fields' => [
                    'mode' => [
                        'display' => __('Mode'),
                        'type' => 'select',
                        'default' => 'single',
                        'options' => [
                            'single' => __('Single choice'),
                            'multiple' => __('Multiple choices'),
                        ],
                        'validate' => ['required', 'in:single,multiple'],
                    ],
                    'variant' => [
                        'display' => __('Variant'),
                        'instructions' => __('Content cards show text and optional custom HTML. Image cards show a full-bleed image with the label available as a tooltip and accessible name.'),
                        'type' => 'button_group',
                        'default' => 'content',
                        'options' => [
                            'content' => __('Content'),
                            'image' => __('Image'),
                        ],
                        'validate' => ['required', 'in:content,image'],
                    ],
                    'card_width' => [
                        'display' => __('Card Width'),
                        'instructions' => __('Controls how much horizontal space each choice card uses. Cards wrap automatically.'),
                        'type' => 'button_group',
                        'default' => 100,
                        'options' => [
                            100 => '100%',
                            50 => '50%',
                            33 => '33%',
                            25 => '25%',
                            20 => '20%',
                        ],
                        'validate' => ['required', 'in:100,50,33,25,20'],
                    ],
                    'image_aspect' => [
                        'display' => __('Image Aspect'),
                        'instructions' => __('Image variant only. Controls the shape of full-bleed image cards.'),
                        'type' => 'button_group',
                        'default' => '1/1',
                        'options' => [
                            '1/1' => '1:1',
                            '4/3' => '4:3',
                            '16/9' => '16:9',
                        ],
                        'validate' => ['required', 'in:1/1,4/3,16/9'],
                        'if' => [
                            'variant' => 'image',
                        ],
                    ],
                    'default' => [
                        'display' => __('Default Value'),
                        'instructions' => __('Use a value for single mode, or a YAML array of values for multiple mode.'),
                        'type' => 'yaml',
                    ],
                ],
            ],
        ];
    }

    protected function isMultiple(): bool
    {
        return $this->config('mode') === 'multiple';
    }

    protected function isImageVariant(): bool
    {
        return $this->config('variant') === 'image';
    }

    /**
     * @return array<int, array{value: string, label: string, use_html: bool, image: string|null, description: string|null, html: string|null}>
     */
    private function normalizedOptions(): array
    {
        return collect($this->config('options') ?? [])
            ->filter(fn ($option) => is_array($option) && filled($option['value'] ?? null))
            ->map(function (array $option) {
                $value = (string) $option['value'];

                $useHtml = (bool) ($option['use_html'] ?? false);
                $isImageVariant = $this->isImageVariant();
                $useHtmlForContentCard = $useHtml && ! $isImageVariant;

                return [
                    'value' => $value,
                    'label' => filled($option['label'] ?? null) ? (string) $option['label'] : $value,
                    'use_html' => $useHtml,
                    'image' => $useHtmlForContentCard ? null : $this->normalizeImageValue($option['image'] ?? null),
                    'description' => ! $useHtmlForContentCard && ! $isImageVariant && filled($option['description'] ?? null) ? (string) $option['description'] : null,
                    'html' => $useHtmlForContentCard ? $this->normalizeHtmlValue($option['html'] ?? null) : null,
                ];
            })
            ->unique('value')
            ->values()
            ->all();
    }

    private function normalizedOptionsForPublish(): array
    {
        return collect($this->normalizedOptions())
            ->map(function (array $option) {
                $asset = $this->resolveAsset($option['image']);

                return array_merge($option, [
                    'image_url' => $asset ? $asset->url() : null,
                    'image_alt' => $asset ? ($asset->get('alt') ?: $option['label']) : $option['label'],
                ]);
            })
            ->all();
    }

    private function augmentedOption(string $value): ?array
    {
        $option = collect($this->normalizedOptions())->firstWhere('value', $value);

        if ($option === null) {
            return null;
        }

        $option['image'] = $this->resolveAsset($option['image']);

        return $option;
    }

    private function knownSingleValue($value): ?string
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (! filled($value)) {
            return null;
        }

        $value = (string) $value;

        return in_array($value, $this->knownValues(), true) ? $value : null;
    }

    private function sortKnownValues(array $values): array
    {
        $values = array_values(array_unique(array_map('strval', $values)));

        return collect($this->knownValues())
            ->filter(fn (string $knownValue) => in_array($knownValue, $values, true))
            ->values()
            ->all();
    }

    private function selectedLabels($value): array
    {
        $values = $this->isMultiple()
            ? $this->sortKnownValues($this->wrapValue($value))
            : array_filter([$this->knownSingleValue($value)]);

        return collect($values)
            ->map(fn ($selectedValue) => collect($this->normalizedOptions())->firstWhere('value', $selectedValue)['label'] ?? $selectedValue)
            ->values()
            ->all();
    }

    private function knownValues(): array
    {
        return collect($this->normalizedOptions())
            ->pluck('value')
            ->all();
    }

    private function wrapValue($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    private function normalizeImageValue($value): ?string
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return filled($value) ? (string) $value : null;
    }

    private function normalizeHtmlValue($value): ?string
    {
        if (is_array($value)) {
            $value = $value['code'] ?? null;
        }

        return filled($value) ? (string) $value : null;
    }

    private function resolveAsset(?string $value): ?AssetContract
    {
        if (! filled($value)) {
            return null;
        }

        if (str_contains($value, '::')) {
            return Asset::find($value);
        }

        $container = AssetContainer::all()->count() === 1 ? AssetContainer::all()->first() : null;

        return $container ? Asset::find($container->handle().'::'.$value) : null;
    }
}
