<?php

declare(strict_types=1);

use ElSchneider\Choices\Fieldtypes\Choices;
use Illuminate\Support\Facades\Storage;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Facades\AssetContainer;
use Statamic\Fields\Field;

function choicesFieldtype(array $config): Choices
{
    return (new Choices)->setField(new Field('choices', array_merge([
        'type' => 'choices',
    ], $config)));
}

function baseChoicesConfig(array $overrides = []): array
{
    return array_merge([
        'mode' => 'multiple',
        'variant' => 'content',
        'options' => [
            ['value' => 'basic', 'label' => 'Basic'],
            ['value' => 'pro', 'label' => 'Pro'],
            ['value' => 'enterprise', 'label' => 'Enterprise'],
        ],
    ], $overrides);
}

it('normalizes saved selected values to known option values in option order', function () {
    $multiple = choicesFieldtype(baseChoicesConfig());

    expect($multiple->preProcess(['unknown', 'enterprise', 'basic', 'basic', 'pro']))
        ->toBe(['basic', 'pro', 'enterprise'])
        ->and($multiple->process(['pro', 'missing', 'basic', 'pro']))
        ->toBe(['basic', 'pro']);

    $single = choicesFieldtype(baseChoicesConfig(['mode' => 'single']));

    expect($single->preProcess(['pro', 'basic']))
        ->toBe('pro')
        ->and($single->process('missing'))
        ->toBeNull()
        ->and($single->process(['missing', 'basic']))
        ->toBeNull();
});

it('only applies configured defaults during preprocessing', function () {
    $multiple = choicesFieldtype(baseChoicesConfig([
        'default' => ['enterprise', 'basic'],
    ]));

    expect($multiple->preProcess(null))
        ->toBe(['basic', 'enterprise'])
        ->and($multiple->preProcess([]))
        ->toBe([])
        ->and($multiple->process(null))
        ->toBe([]);

    $single = choicesFieldtype(baseChoicesConfig([
        'mode' => 'single',
        'default' => 'pro',
    ]));

    expect($single->preProcess(null))
        ->toBe('pro')
        ->and($single->process(null))
        ->toBeNull();
});

it('preloads the normalized publish option contract for content cards', function () {
    $fieldtype = choicesFieldtype(baseChoicesConfig([
        'options' => [
            ['value' => '', 'label' => 'Skipped'],
            ['value' => 'basic', 'label' => '', 'image' => ['basic.svg'], 'description' => 'Shown'],
            ['value' => 'basic', 'label' => 'Duplicate skipped'],
            [
                'value' => 'custom',
                'label' => 'Custom',
                'use_html' => true,
                'image' => ['custom.svg'],
                'description' => 'Hidden when custom HTML is enabled',
                'html' => ['mode' => 'htmlmixed', 'code' => '<strong>Trusted</strong>'],
            ],
        ],
    ]));

    expect($fieldtype->preload()['options'])->toMatchArray([
        [
            'value' => 'basic',
            'label' => 'basic',
            'use_html' => false,
            'image' => 'basic.svg',
            'description' => 'Shown',
            'html' => null,
            'image_url' => null,
            'image_alt' => 'basic',
        ],
        [
            'value' => 'custom',
            'label' => 'Custom',
            'use_html' => true,
            'image' => null,
            'description' => null,
            'html' => '<strong>Trusted</strong>',
            'image_url' => null,
            'image_alt' => 'Custom',
        ],
    ]);
});

it('preloads image cards with image values independent of custom html settings', function () {
    $fieldtype = choicesFieldtype(baseChoicesConfig([
        'variant' => 'image',
        'options' => [
            [
                'value' => 'visual',
                'label' => 'Visual',
                'use_html' => true,
                'image' => ['visual.svg'],
                'description' => 'Ignored for image cards',
                'html' => ['code' => '<strong>Ignored for image cards</strong>'],
            ],
        ],
    ]));

    expect($fieldtype->preload()['options'])->toMatchArray([
        [
            'value' => 'visual',
            'label' => 'Visual',
            'use_html' => true,
            'image' => 'visual.svg',
            'description' => null,
            'html' => null,
            'image_url' => null,
            'image_alt' => 'Visual',
        ],
    ]);
});

it('augments selected options in config order and resolves asset references', function () {
    Storage::fake('public');
    Storage::disk('public')->put('cards/basic.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

    $container = AssetContainer::make('assets');
    $container->disk('public');
    $container->save();

    $asset = $container->makeAsset('cards/basic.svg');
    $asset->data(['alt' => 'Basic illustration']);
    $asset->saveQuietly();

    $fieldtype = choicesFieldtype(baseChoicesConfig([
        'options' => [
            ['value' => 'basic', 'label' => 'Basic', 'image' => ['assets::cards/basic.svg']],
            ['value' => 'pro', 'label' => 'Pro'],
            ['value' => 'enterprise', 'label' => 'Enterprise'],
        ],
    ]));

    $augmented = $fieldtype->augment(['enterprise', 'unknown', 'basic', 'basic']);

    expect($augmented)->toHaveCount(2)
        ->and(array_column($augmented, 'value'))->toBe(['basic', 'enterprise'])
        ->and($augmented[0]['image'])->toBeInstanceOf(AssetContract::class)
        ->and($augmented[0]['image']->id())->toBe('assets::cards/basic.svg')
        ->and($augmented[1]['image'])->toBeNull();
});

it('exposes labels for index rendering and renderable field data', function () {
    $fieldtype = choicesFieldtype(baseChoicesConfig());

    expect($fieldtype->preProcessIndex(['enterprise', 'missing', 'basic']))
        ->toBe(['Basic', 'Enterprise'])
        ->and($fieldtype->extraRenderableFieldData())
        ->toBe([
            'options' => [
                'basic' => 'Basic',
                'pro' => 'Pro',
                'enterprise' => 'Enterprise',
            ],
        ]);
});
