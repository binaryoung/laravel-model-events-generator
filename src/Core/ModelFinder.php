<?php

namespace Binaryoung\LaravelModelEventsGenerator\Core;

use Exception;
use SplFileInfo;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Illuminate\Database\Eloquent\Model;

class ModelFinder
{
    protected $finder;

    protected $paths = [];

    public function __construct(array $paths = null)
    {
        $this->paths = $paths ?? [
            app_path(),
            app_path('Models'),
        ];

        $this->finder = $this->makeFinder();
    }

    protected function makeFinder(): Finder
    {
        $finder = (new Finder())
                    ->ignoreUnreadableDirs()
                    ->files()
                    ->depth('== 0')
                    ->name('*.php');

        array_walk($this->paths, function ($path) use ($finder) {
            $finder->in($path);
        });

        return $finder;
    }

    public function models(): array
    {
        try {
            return collect($this->finder)
                ->map(function (SplFileInfo $file) {
                    return new ReflectionFile($file->getRealPath());
                })
                ->filter(function (ReflectionClass $reflation) {
                    return $reflation->isSubclassOf(Model::class);
                })
                ->map(function (ReflectionClass $reflation) {
                    return $reflation->getName();
                })
                ->filter()
                ->values()
                ->toArray();
        } catch (Exception $e) {
            return [];
        }
    }
}
