<?php

namespace TCG\Voyager;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\AggregateServiceProvider;
use Intervention\Image\ImageServiceProvider;
use Larapack\DoctrineSupport\DoctrineSupportServiceProvider;
use TCG\Voyager\Providers\ConsoleCommandServiceProvider;
use TCG\Voyager\Providers\VoyagerDummyServiceProvider;
use TCG\Voyager\Providers\VoyagerEventServiceProvider;

class VoyagerSupportServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
    protected $providers = [
        ConsoleCommandServiceProvider::class,
        VoyagerEventServiceProvider::class,
        ImageServiceProvider::class,
        VoyagerDummyServiceProvider::class,
        DoctrineSupportServiceProvider::class,
    ];
}