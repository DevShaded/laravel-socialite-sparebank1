<?php

namespace SpareBank1;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class SpareBank1SocialiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $socialite = $this->app->make(Factory::class);

        $socialite->extend('sb1', function () use ($socialite) {
            $config = config('services.sb1');
            return $socialite->buildProvider(SpareBank1Provider::class, $config);
        });
    }
}
