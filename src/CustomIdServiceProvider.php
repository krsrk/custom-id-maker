<?php


namespace Krsrk\CustomId;


use Illuminate\Support\ServiceProvider;


class CustomIdServiceProvider extends ServiceProvider
{
    public function boot()
    {

    }


    public function register()
    {
        $this->app->make('Krsrk\CustomId\CustomId');
    }
}
