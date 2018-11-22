<?php

namespace Binaryoung\LaravelModelEventsGenerator\Core;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;

class ModelEventsMakeCommand extends GeneratorCommand
{
    /**
     * @var array
     */
    protected $events = [
        'retrieved',
        'creating',
        'created',
        'updating',
        'updated',
        'saving',
        'saved',
        'deleting',
        'deleted',
        'restoring',
        'restored',
    ];

    /**
     * @var string
     */
    protected $event;

    /**
     * @var array
     */
    protected $selectEvents = [];

    /**
     * @var string
     */
    protected $fullModelClass;

    /**
     * @var bool
     */
    protected $eventWithBroadcasting = true;

    /**
     * @var string
     */
    protected $description = 'Create model events';

    /**
     * @var string
     */
    protected $name = 'make:model-events';

    /*
     * @var string
     */

    protected $signature = 'make:model-events';

    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
    }

    public function handle(): void
    {
        $this->fullModelClass = $this->anticipate('Enter your model\'s full namespace (e.g. App\\Models\\User)', $this->guessModels());
        $this->selectEvents = $this->choice('Select model events you want to generate.',
                                            $events = array_merge(['all'], $this->events),
                                            implode(',', array_keys($events)),
                                            null,
                                            true);
        $this->eventWithBroadcasting = $this->confirm('Generate event with broadcasting?', true);

        $this->makeEvents()
             ->showSnippet();
    }

    protected function guessModels(): array
    {
        return (new ModelFinder())->models();
    }

    protected function makeEvents()
    {
        $this->formatSelectEvent();

        foreach ($this->selectEvents as $event) {
            $this->event = $event;
            $this->makeEvent($event);
        }

        return $this;
    }

    protected function formatSelectEvent()
    {
        if (count($this->selectEvents) === 1 && $this->selectEvents[0] === 'all') {
            $this->selectEvents = $this->events;
        }

        $this->selectEvents = array_filter($this->selectEvents, function ($event) {
            return $event !== 'all';
        });
    }

    protected function makeEvent($name)
    {
        if (!in_array($name, $this->events)) {
            echo $name;

            return;
        }

        $path = $this->getPath($name);

        $this->makeDirectory($path);
        $this->files->put($path, $this->buildClass($name));
    }

    protected function getPath($name)
    {
        $class = $this->getClass();
        $model = $this->getModelClass();

        return app_path("Events/{$model}/{$class}.php");
    }

    protected function getFullModelClass(): string
    {
        return $this->fullModelClass;
    }

    protected function getModelClass(): string
    {
        return class_basename($this->getFullModelClass());
    }

    protected function getModelVariable(): string
    {
        return camel_case($this->getModelClass());
    }

    protected function getEvent(): string
    {
        return $this->event;
    }

    protected function getClass(): string
    {
        return studly_case(
            $this->getModelClass()
            .' '.
            $this->getEvent()
        );
    }

    protected function getNamespace($name): string
    {
        return 'App\\Events\\'.$this->getModelClass();
    }

    protected function replacePlaceholders(&$stub): ModelEventsMakeCommand
    {
        $stub = str_replace(
            ['DummyFullModelClass', 'DummyModelClass', 'DummyModelVariable', 'DummyEventClass'],
            [$this->getFullModelClass(), $this->getModelClass(), $this->getModelVariable(), $this->getClass()],
            $stub
        );

        return $this;
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $this->replacePlaceholders($stub);

        return $stub;
    }

    protected function getStub(): string
    {
        $dir = __DIR__.'/../../stubs';

        return $this->eventWithBroadcasting ? $dir.'/event.stub' : $dir.'/eventWithoutBroadcasting.stub';
    }

    protected function showSnippet()
    {
        $eventsUse = array_map(function ($event) {
            $this->event = $event;

            return sprintf('use %s;', $this->getNamespace($event).'\\'.$this->getClass());
        }, $this->selectEvents);

        $eventsMap = array_map(function ($event) {
            return sprintf("        '%s' => %s::class,", $event, studly_case($this->getModelClass().' '.$event));
        }, $this->selectEvents);

        $snippet = <<<'SNIPPET'
    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
DummyEventsMap
    ];
SNIPPET;

        $snippet = str_replace(
                                'DummyEventsMap',
                                implode(PHP_EOL, $eventsMap),
                                $snippet
                              );

        $this->info('Copy following code to your model class to register model events.'.PHP_EOL);
        $this->info(implode(PHP_EOL, $eventsUse).PHP_EOL);
        $this->info($snippet);
    }
}
