<?php

namespace Binaryoung\LaravelModelEventsGenerator;

use Illuminate\Support\ServiceProvider;
use Binaryoung\LaravelModelEventsGenerator\Core\ModelEventsMakeCommand;

class GeneratorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(ModelEventsMakeCommand::class);
        }
    }
}
