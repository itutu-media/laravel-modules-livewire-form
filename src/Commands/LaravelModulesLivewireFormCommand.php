<?php

namespace ITUTUMedia\LaravelModulesLivewireForm\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ITUTUMedia\LaravelModulesLivewireTable\Traits\ComponentParser;

class LaravelModulesLivewireFormCommand extends Command
{
    use ComponentParser;

    public $signature = 'module:make-form {component} {model} {module} {--force} {--custom} {--view}';

    public $description = 'Create a new Livewire table component for a module';

    public function handle(): int
    {
        if (! $this->parser()) {
            return false;
        }

        if (! $this->checkClassNameValid()) {
            return false;
        }

        if (! $this->checkReservedClassName()) {
            return false;
        }

        $action = $this->createAction();
        $request = $this->createRequest();
        $class = $this->createClass();
        $view = $this->createView();

        if ($action || $request || $class || $view) {
            $this->line("<options=bold,reverse;fg=green> COMPONENT CREATED </> 🤙\n");

            $action && $this->line("<options=bold;fg=green>ACTION:</> {$this->getActionSourcePath()}");
            $request && $this->line("<options=bold;fg=green>REQUEST:</> {$this->getRequestSourcePath()}");
            $class && $this->line("<options=bold;fg=green>CLASS:</> {$this->getClassSourcePath()}");
            $view && $this->line("<options=bold;fg=green>VIEW:</> {$this->getViewSourcePath()}");
        }

        return false;
    }

    protected function getActionSourcePath()
    {
        return Str::after($this->component->action->file, $this->getBasePath().'/');
    }

    protected function getRequestSourcePath()
    {
        return Str::after($this->component->request->file, $this->getBasePath().'/');
    }

    protected function getClassSourcePath()
    {
        return Str::after($this->component->class->file, $this->getBasePath().'/');
    }

    protected function getViewSourcePath()
    {
        return Str::after($this->component->view->file, $this->getBasePath().'/');
    }

    protected function createAction()
    {
        $actionFile = $this->component->action->file;

        if (File::exists($actionFile) && ! $this->isForce()) {
            $this->line("<options=bold,reverse;fg=red> WHOOPS-IE-TOOTLES </> 😳 \n");
            $this->line("<fg=red;options=bold>Action already exists:</> {$this->getActionSourcePath()}");

            return false;
        }

        $this->ensureDirectoryExists($actionFile);

        File::put($actionFile, $this->getActionContents());

        return $this->component->action;
    }

    protected function createRequest()
    {
        $requestFile = $this->component->request->file;

        if (File::exists($requestFile) && ! $this->isForce()) {
            $this->line("<options=bold,reverse;fg=red> WHOOPS-IE-TOOTLES </> 😳 \n");
            $this->line("<fg=red;options=bold>Request already exists:</> {$this->getRequestSourcePath()}");

            return false;
        }

        $this->ensureDirectoryExists($requestFile);

        File::put($requestFile, $this->getRequestContents());

        return $this->component->request;
    }

    protected function createClass()
    {
        $classFile = $this->component->class->file;

        if (File::exists($classFile) && ! $this->isForce()) {
            $this->line("<options=bold,reverse;fg=red> WHOOPS-IE-TOOTLES </> 😳 \n");
            $this->line("<fg=red;options=bold>Class already exists:</> {$this->getClassSourcePath()}");

            return false;
        }

        $this->ensureDirectoryExists($classFile);

        File::put($classFile, $this->getClassContents());

        return $this->component->class;
    }

    protected function createView()
    {
        $viewFile = $this->component->view->file;

        if (File::exists($viewFile) && ! $this->isForce()) {
            $this->line("<options=bold,reverse;fg=red> WHOOPS-IE-TOOTLES </> 😳 \n");
            $this->line("<fg=red;options=bold>View already exists:</> {$this->getViewSourcePath()}");

            return false;
        }

        $this->ensureDirectoryExists($viewFile);

        File::put($viewFile, $this->getViewContents());

        return $this->component->view;
    }
}
