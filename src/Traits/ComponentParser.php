<?php

namespace ITUTUMedia\LaravelModulesLivewireTable\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ITUTUMedia\LaravelModulesLivewireTable\Support\Decomposer;

trait ComponentParser
{
    use CommandHelper;

    protected $component;

    protected $module;

    protected $model;

    protected $directories;

    protected function parser()
    {
        $checkDependencies = Decomposer::checkDependencies(
            $this->isCustomModule() ? ['livewire/livewire'] : null
        );

        if ($checkDependencies->type == 'error') {
            $this->line($checkDependencies->message);

            return false;
        }

        if (! $module = $this->getModule()) {
            return false;
        }

        $this->module = $module;

        $this->directories = collect(
            preg_split('/[.\/(\\\\)]+/', $this->argument('component'))
        )->map([Str::class, 'studly']);

        $this->component = $this->getComponent();

        $this->model = $this->getModel();

        return $this;
    }

    protected function getComponent()
    {
        $actionInfo = $this->getActionInfo();

        $requestInfo = $this->getRequestInfo();

        $classInfo = $this->getClassInfo();

        $viewInfo = $this->getViewInfo();

        $stubInfo = $this->getStubInfo();

        return (object) [
            'action' => $actionInfo,
            'request' => $requestInfo,
            'class' => $classInfo,
            'view' => $viewInfo,
            'stub' => $stubInfo,
        ];
    }

    protected function getActionInfo()
    {
        $modulePath = $this->getModulePath();

        $moduleLivewireNamespace = 'App\\Actions';

        $classDir = (string) Str::of($modulePath)
            ->append('/'.$moduleLivewireNamespace)
            ->replace(['\\'], '/');

        $classPath = $this->directories->implode('/');

        $namespace = $this->getNamespace($classPath, $moduleLivewireNamespace);

        $className = $this->directories->last();

        return (object) [
            'dir' => $classDir,
            'path' => $classPath,
            'file' => $classDir.'/'.$classPath.'.php',
            'namespace' => $namespace,
            'name' => $className,
        ];
    }

    protected function getRequestInfo()
    {
        $modulePath = $this->getModulePath();

        $moduleLivewireNamespace = 'App\\Http\\Requests';

        $classDir = (string) Str::of($modulePath)
            ->append('/'.$moduleLivewireNamespace)
            ->replace(['\\'], '/');

        $classPath = $this->directories->implode('/');

        $namespace = $this->getNamespace($classPath, $moduleLivewireNamespace);

        $className = $this->directories->last();

        return (object) [
            'dir' => $classDir,
            'path' => $classPath,
            'file' => $classDir.'/'.$classPath.'.php',
            'namespace' => $namespace,
            'name' => $className,
        ];
    }

    protected function getClassInfo()
    {
        $modulePath = $this->getModulePath();

        $moduleLivewireNamespace = $this->getModuleLivewireNamespace();

        $classDir = (string) Str::of($modulePath)
            ->append('/'.$moduleLivewireNamespace)
            ->replace(['\\'], '/');

        $classPath = $this->directories->implode('/');

        $namespace = $this->getNamespace($classPath);

        $className = $this->directories->last();

        $componentTag = $this->getComponentTag();

        return (object) [
            'dir' => $classDir,
            'path' => $classPath,
            'file' => $classDir.'/'.$classPath.'.php',
            'namespace' => $namespace,
            'name' => $className,
            'tag' => $componentTag,
        ];
    }

    protected function getViewInfo()
    {
        $moduleLivewireViewDir = $this->getModuleLivewireViewDir();

        $path = $this->directories
            ->map([Str::class, 'kebab'])
            ->implode('/');

        if ($this->option('view')) {
            $path = strtr($this->option('view'), ['.' => '/']);
        }

        return (object) [
            'dir' => $moduleLivewireViewDir,
            'path' => $path,
            'folder' => Str::after($moduleLivewireViewDir, 'views/'),
            'file' => $moduleLivewireViewDir.'/'.$path.'.blade.php',
            'name' => strtr($path, ['/' => '.']),
        ];
    }

    protected function getStubInfo()
    {
        $defaultStubDir = __DIR__.'/../../stubs/';

        $stubDir = File::isDirectory($publishedStubDir = base_path('stubs/modules-livewire-table/'))
            ? $publishedStubDir
            : $defaultStubDir;

        $classStub = File::exists($stubDir.'livewire.stub')
            ? $stubDir.'livewire.stub'
            : $defaultStubDir.'livewire.stub';

        $viewStub = File::exists($stubDir.'view.stub')
            ? $stubDir.'view.stub'
            : $defaultStubDir.'view.stub';

        $actionStub = File::exists($stubDir.'action.stub')
            ? $stubDir.'action.stub'
            : $defaultStubDir.'action.stub';

        $requestStub = File::exists($stubDir.'request.stub')
            ? $stubDir.'request.stub'
            : $defaultStubDir.'request.stub';

        return (object) [
            'dir' => $stubDir,
            'class' => $classStub,
            'view' => $viewStub,
            'action' => $actionStub,
            'request' => $requestStub,
        ];
    }

    protected function getActionContents()
    {
        return preg_replace(
            ['/\[namespace\]/', '/\[model_import\]/', '/\[class\]/', '/\[data\]/'.'/\[model\]/'],
            [$this->component->action->namespace, $this->getModelImport(), $this->component->action->name.'Action', '', Str::lower($this->getModelName())],
            file_get_contents($this->component->stub->action),
        );
    }

    protected function getRequestContents()
    {
        return preg_replace(
            ['/\[namespace\]/', '/\[model_import\]/', '/\[class\]/', '/\[data\]/'.'/\[model\]/'],
            [$this->component->request->namespace, $this->getModelImport(), $this->component->request->name.'Request', '', Str::lower($this->getModelName())],
            file_get_contents($this->component->stub->request),
        );
    }

    protected function getClassContents()
    {
        $template = file_get_contents($this->component->stub->class);

        return preg_replace(
            ['/\[namespace\]/', '/\[class\]/', '/\[model\]/', '/\[model_import\]/', '/\[model_low_case\]/', '/\[action_import\]/', '/\[request_import\]/', '/\[title\]/', '/\[fields\]/'],
            [$this->component->class->namespace, $this->component->class->name, $this->getModelName(), $this->getModelImport(), Str::lower($this->getModelName())],
            $template,
        );
    }

    protected function getViewContents()
    {
        return preg_replace(
            '/\[forms\]/',
            $this->getForms(),
            file_get_contents($this->component->stub->view),
        );
    }

    public function getActionImport(): string
    {
        if (File::exists(app_path('Models/'.$this->model.'.php'))) {
            return 'App\Models\\'.$this->model;
        }

        if (File::exists(app_path($this->model.'.php'))) {
            return 'App\\'.$this->model;
        }

        return str_replace('/', '\\', $this->model);
    }

    public function getModelImport(): string
    {
        if (File::exists(app_path('Models/'.$this->model.'.php'))) {
            return 'App\Models\\'.$this->model;
        }

        if (File::exists(app_path($this->model.'.php'))) {
            return 'App\\'.$this->model;
        }

        return str_replace('/', '\\', $this->model);
    }

    public function getModelName(): string
    {
        $explode = explode('\\', $this->getModelImport());

        return end($explode);
    }

    protected function getClassSourcePath()
    {
        return Str::after($this->component->class->file, $this->getBasePath().'/');
    }

    protected function getClassNamespace()
    {
        return $this->component->class->namespace;
    }

    protected function getClassName()
    {
        return $this->component->class->name;
    }

    protected function getComponentTag()
    {
        $directoryAsView = $this->directories
            ->map([Str::class, 'kebab'])
            ->implode('.');

        $tag = "<livewire:{$this->getModuleLowerName()}::{$directoryAsView} />";

        $tagWithOutIndex = Str::replaceLast('.index', '', $tag);

        return $tagWithOutIndex;
    }

    public function getForms()
    {
        $modelName = $this->getModelName();
        $model = new $modelName();

        if ($model instanceof Model === false) {
            throw new \Exception('Invalid model given.');
        }

        $getFillable = [
            ...$model->getFillable(),
        ];

        $forms = '';

        foreach ($getFillable as $field) {
            if (in_array($field, $model->getHidden())) {
                continue;
            }

            $forms .= '<x-input id="'.$field.'" label="'.Str::replace('_', ' ', Str::title($field)).'" placeholder="'.Str::replace('_', ' ', Str::title($field)).'" required :disabled="$disable" wire:model="'.Str::replace('_', ' ', Str::title($field)).'" />';
        }

        return $forms;
    }

    protected function getComponentQuote()
    {
        return "The <code>{$this->getClassName()}</code> livewire component is loaded<code>{$this->getModuleName()}</code> module.";
    }

    protected function getBasePath($path = null)
    {
        return strtr(base_path($path), ['\\' => '/']);
    }
}
