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
                                    'instructions' => __('Replace the image and description with trusted custom HTML.'),
                                ],
                            ],
                            [
                                'handle' => 'image',
                                'field' => [
                                    'display' => __('Image'),
                                    'type' => 'assets',
                                    'max_files' => 1,
                                    'mode' => 'grid',
                                    'instructions' => __('Optional asset displayed at the top of the card.'),
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
                                    'instructions' => __('Optional supporting text.'),
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
                                    'instructions' => __('Trusted HTML rendered raw. Replaces the image and description; the label and selection indicator stay controlled by the fieldtype.'),
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
                    'layout' => [
                        'display' => __('Layout'),
                        'type' => 'select',
                        'default' => 'one_column',
                        'options' => [
                            'one_column' => __('One column'),
                            'two_columns' => __('Two columns'),
                        ],
                        'validate' => ['required', 'in:one_column,two_columns'],
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

                return [
                    'value' => $value,
                    'label' => filled($option['label'] ?? null) ? (string) $option['label'] : $value,
                    'use_html' => $useHtml,
                    'image' => $useHtml ? null : $this->normalizeImageValue($option['image'] ?? null),
                    'description' => ! $useHtml && filled($option['description'] ?? null) ? (string) $option['description'] : null,
                    'html' => $useHtml && filled($option['html'] ?? null) ? (string) $option['html'] : null,
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
