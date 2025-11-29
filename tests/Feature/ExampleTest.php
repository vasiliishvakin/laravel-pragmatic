<?php

it('can load service provider', function () {
    expect(config('pragmatic'))->toBeArray();
});

it('has registered service provider', function () {
    $providers = app()->getLoadedProviders();

    expect($providers)->toHaveKey('Pragmatic\Providers\PragmaticServiceProvider');
});
