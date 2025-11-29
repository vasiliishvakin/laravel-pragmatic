<?php

it('can load service provider', function () {
    expect(config('laravel-pragmatic'))->toBeArray();
});

it('has registered service provider', function () {
    $providers = app()->getLoadedProviders();

    expect($providers)->toHaveKey('Shvakin\Pragmatic\PragmaticServiceProvider');
});
