<?php

namespace ITUTUMedia\LaravelModulesLivewireForm\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ITUTUMedia\LaravelModulesLivewireForm\Support\Decomposer;

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

        $this->model = $this->getModel();

        $this->component = $this->getComponent();

        return $this;
    }

    protected function getComponent()
    {
        $modelInfo = $this->getModelInfo();

        $actionInfo = $this->getActionInfo();

        $requestInfo = $this->getRequestInfo();

        $classInfo = $this->getClassInfo();

        $viewInfo = $this->getViewInfo();

        $stubInfo = $this->getStubInfo();

        return (object) [
            'model' => $modelInfo,
            'action' => $actionInfo,
            'request' => $requestInfo,
            'class' => $classInfo,
            'view' => $viewInfo,
            'stub' => $stubInfo,
        ];
    }

    protected function getModelInfo()
    {
        $modelName = $this->getModelImport();
        $model = new $modelName();

        if ($model instanceof Model === false) {
            throw new \Exception('Invalid model given.');
        }

        $getFillable = [
            ...$model->getFillable(),
        ];

        return (object) [
            'name' => $this->getModelName(),
            'fillable' => $getFillable,
            'hidden' => $model->getHidden(),
            'table' => $model->getTable(),
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

        $namespace = Str::replace('\\'.$this->directories->first(), '', $namespace);

        $className = $this->directories->first().'Action';

        return (object) [
            'dir' => $classDir,
            'path' => $classPath,
            'file' => $classDir.'/'.$className.'.php',
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

        $namespace = Str::replace('\\'.$this->directories->first(), '', $namespace);

        $className = $this->directories->first().'Request';

        return (object) [
            'dir' => $classDir,
            'path' => $classPath,
            'file' => $classDir.'/'.$className.'.php',
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
        $getFillable = [
            ...$this->component->model->fillable,
        ];

        $data = [];

        foreach ($getFillable as $field) {
            if (in_array($field, $this->component->model->hidden)) {
                continue;
            }

            $data[] = '$this->data['."'".$field."'".'] = $newData['."'".$field."'".'];';
        }

        $data = implode("\n\t\t", $data);

        return preg_replace(
            ['/\[namespace\]/', '/\[model_import\]/', '/\[class\]/', '/\[data\]/', '/\[model\]/'],
            [$this->component->action->namespace, $this->getModelImport(), $this->component->action->name, $data, Str::lower($this->getModelName())],
            file_get_contents($this->component->stub->action),
        );
    }

    protected function getRequestContents()
    {
        $getFillable = [
            ...$this->component->model->fillable,
        ];

        $rules = [];

        foreach ($getFillable as $field) {
            if (in_array($field, $this->component->model->hidden)) {
                continue;
            }

            $rules[] = "'".$field."'".' => '."'".'required'."'";
        }

        $rules = implode(",\n\t\t\t", $rules);

        return preg_replace(
            ['/\[namespace\]/', '/\[model_import\]/', '/\[class\]/', '/\[data\]/', '/\[model\]/', '/\[rules\]/'],
            [$this->component->request->namespace, $this->getModelImport(), $this->component->request->name, '', Str::lower($this->getModelName()), $rules],
            file_get_contents($this->component->stub->request),
        );
    }

    protected function getClassContents()
    {
        $template = file_get_contents($this->component->stub->class);

        $getFillable = [
            ...$this->component->model->fillable,
        ];

        $fields = [];
        $resetFields = [];
        $setData = [];

        foreach ($getFillable as $field) {
            if (in_array($field, $this->component->model->hidden)) {
                continue;
            }

            $fields[] = '$'.$field;
            $resetFields[] = "'$field'";
            $setData[] = '$this->'.$field.' = $this->state->'.$field.';';
        }

        $fields = implode(',', $fields);
        $resetFields = implode(',', $resetFields);
        $setData = implode("\n\t\t", $setData);

        return preg_replace(
            ['/\[namespace\]/', '/\[class\]/', '/\[module\]/', '/\[model\]/', '/\[model_import\]/', '/\[model_low_case\]/', '/\[action_import\]/', '/\[request_import\]/', '/\[title\]/', '/\[fields\]/', '/\[resetFields\]/', '/\[setData\]/', '/\[action\]/', '/\[request\]/'],
            [$this->component->class->namespace, $this->component->class->name, $this->getModuleLowerName(), $this->getModelName(), $this->getModelImport(), Str::lower($this->getModelName()), $this->getActionImport(), $this->getRequestImport(), Str::title($this->component->class->name.' Form'), $fields, $resetFields, $setData, $this->component->action->name, $this->component->request->name],
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
        return $this->component->action->namespace.'\\'.$this->component->action->name;
    }

    public function getRequestImport(): string
    {
        return $this->component->request->namespace.'\\'.$this->component->request->name;
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
        $getFillable = [
            ...$this->component->model->fillable,
        ];

        $forms = [];

        foreach ($getFillable as $field) {
            if (in_array($field, $this->component->model->hidden)) {
                continue;
            }

            $forms[] = '<x-input id="'.$field.'" label="'.Str::replace('_', ' ', Str::title($field)).'" placeholder="'.Str::replace('_', ' ', Str::title($field)).'" required :disabled="$disable" wire:model="'.Str::replace('_', ' ', Str::title($field)).'" />';
        }

        return implode("\n\t\t", $forms);
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
