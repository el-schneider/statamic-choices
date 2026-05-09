<?php

declare(strict_types=1);

namespace ElSchneider\Choices;

use Inertia\Inertia;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

final class ServiceProvider extends AddonServiceProvider
{
    public function supportsInertia(): bool
    {
        return class_exists(Inertia::class);
    }

    protected function bootVite(): static
    {
        $input = [$this->supportsInertia()
            ? 'resources/js/v6/addon.ts'
            : 'resources/js/v5/addon.ts'];

        if (! $this->supportsInertia()) {
            $input[] = 'resources/js/v5/addon.css';
        }

        $this->registerVite([
            'input' => $input,
            'publicDirectory' => 'resources/dist',
        ]);

        return $this;
    }

    protected function bootPublishAfterInstall()
    {
        if (! $this->publishAfterInstall) {
            return $this;
        }

        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish', [
                '--tag' => $this->getAddon()->slug(),
                '--force' => true,
            ]);
        });

        return $this;
    }
}
