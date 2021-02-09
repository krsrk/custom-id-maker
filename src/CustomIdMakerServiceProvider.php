<?php


namespace Krsrk\CustomId;


use Illuminate\Support\ServiceProvider;


class CustomIdMakerServiceProvider extends ServiceProvider
{
    public function boot()
    {

    }

    public function register()
    {
        $this->app->make('Krsrk\CustomId\CustomIdMaker');
    }
}
