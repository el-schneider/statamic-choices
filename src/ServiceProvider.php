<?php

declare(strict_types=1);

namespace ElSchneider\Choices;

use Inertia\Inertia;
use Statamic\Providers\AddonServiceProvider;

final class ServiceProvider extends AddonServiceProvider
{
    public function supportsInertia(): bool
    {
        return class_exists(Inertia::class);
    }

    protected function bootVite(): static
    {
        $this->registerVite([
            'input' => [
                $this->supportsInertia()
                    ? 'resources/js/v6/addon.ts'
                    : 'resources/js/v5/addon.ts',
            ],
            'publicDirectory' => 'resources/dist',
        ]);

        return $this;
    }
}
