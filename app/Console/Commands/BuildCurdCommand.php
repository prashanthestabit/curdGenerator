<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class BuildCurdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curd:build {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CRUDs for all Models that don\'t already have one.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(QuestionHelper $helper)
    {
        $validationRules = array();
        $table = Str::lower($this->argument('model'));
        if (!Schema::hasTable($table)) {
            Log::info("Migration for table '{$table}' does not exist.");
            $this->error("Migration for table '{$table}' does not exist.");
            return 1;
        }

        $columns = collect(Schema::getColumnListing($table))->diff(['created_at', 'id', 'updated_at'])->toArray();

        $model = Str::title(Str::singular($table));
        $modelClass = "App\\Models\\{$model}";

        if (!class_exists($modelClass)) {
            $this->createModel($modelClass, $model, $columns);
        }

        $controllerName = "{$model}Controller";
        $controllerPath = "App\\Http\\Controllers\\{$controllerName}";

        if (!class_exists($controllerPath)) {
            $this->createController($controllerPath, $controllerName, $table);
        }

        $this->registerRoute($table, $controllerName, $controllerPath);

        $servicesName = "{$model}Services";
        $servicePath = "App\\Services\\{$servicesName}";

        if (!class_exists($servicePath)) {
            $this->createService($servicePath, $servicesName, $table, $model, $columns);
        }

        $requestName = "Store{$model}Request";
        $path = "App\\Http\\Requests\\{$model}\\{$requestName}";
        $file = app_path("Http/Requests/{$model}/{$requestName}.php");
        $basePath = app_path("Http/Requests/{$model}");

        $requestPath = "App\\Http\\Requests\\{$model}";

        if (!class_exists($path)) {
            // Create Rule Validation Start
            $answer = $this->questionForValidationYesNo($helper, 'Do you want to create validations?');

            if (Str::lower($answer) == 'yes') {
                $validationRules = $this->createValidation($columns, $helper);
            }
            // Create Rule Validation End

            $this->createRequest($path, $requestName, $columns, $file, $basePath, $validationRules, $requestPath);
        }

        $requestUpdate = "Update{$model}Request";
        $path = "App\\Http\\Requests\\{$model}\\{$requestUpdate}";
        $file = app_path("Http/Requests/{$model}/{$requestUpdate}.php");
        $basePath = app_path("Http/Requests/{$model}");

        $requestPath = "App\\Http\\Requests\\{$model}";

        if (!class_exists($path)) {
            $this->createRequest($path, $requestUpdate, $columns, $file, $basePath, $validationRules, $requestPath);
        }

        return 0;
    }

    protected function createValidation($columns, $helper)
    {
        try {
            $selectedOptions = array();
            if ($columns) {
                foreach ($columns as $col) {

                    $answer = $this->questionForValidationYesNo($helper, 'Do you want to create validations for ' . Str::title($col) . '?');
                    if ($answer == 'yes') {
                        $selectedOptions[$col] = $helper->ask($this->input, $this->output, new Question("Create Rule For " . Str::title($col) . " 'required|string|max:255' "));
                    }
                }
            }
            return $selectedOptions;
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    protected function questionForValidationYesNo($helper, $question, $validAnswers = ['yes', 'no'])
    {
        try {
            $answer = '';

            while (!in_array(Str::lower($answer), $validAnswers)) {
                $answer = $helper->ask($this->input, $this->output, new Question("$question (Yes/No)"));
                if (!in_array(Str::lower($answer), $validAnswers)) {
                    $this->error("Invalid answer, please enter either 'Yes' or 'No'.");
                    continue;
                }
                break;
            }
            return $answer;

        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    protected function createRequest($path, $requestName, $columns, $file, $basePath, $validationRules, $requestPath = "App\Http\Requests")
    {
        try {
            $validationString = '';
            if (class_exists($path)) {
                $this->error("Request {$path} already exists.");
                return $path;
            }

            if (!file_exists($basePath)) {
                mkdir($basePath, 0775, true);
            }

            if ($validationRules) {
                $validationString = multiDArrayToString($validationRules, ',' . PHP_EOL . "\t\t\t");
            }

            $stubFile = base_path('curd/request.stub');
            $stub = file_get_contents($stubFile);
            $stub = str_replace('{{ class }}', $requestName, $stub);

            $stub = str_replace('{{ namespace }}', $requestPath, $stub);

            $stub = str_replace('{{ rules }}', $validationString, $stub);

            file_put_contents($file, $stub);
            $this->info("Request {$file} created successfully.");
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    protected function createService($path, $servicesName, $table, $model, $columns, $servicePath = "App\Services")
    {
        try {
            if (class_exists($path)) {
                $this->error("Service {$path} already exists.");
                return $path;
            }
            $basePath = app_path("Services");

            if (!file_exists($basePath)) {
                mkdir($basePath, 0775, true);
            }

            $stubFile = base_path('curd/service.stub');
            $stub = file_get_contents($stubFile);
            $stub = str_replace('{{ class }}', $servicesName, $stub);

            $stub = str_replace('{{ namespace }}', $servicePath, $stub);

            $stub = str_replace('{{ model }}', $model, $stub);

            $file = app_path("Services/{$servicesName}.php");

            file_put_contents($file, $stub);
            $this->info("Service {$file} created successfully.");

        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    protected function registerRoute($table, $controllerName, $controllerPath)
    {
        $routeFile = base_path('routes/web.php');
        $routeData = file_get_contents($routeFile);

        //Add Line after use in route File
        $file_lines = file($routeFile, FILE_IGNORE_NEW_LINES);
        $word_to_search = "use";

        $line_number = $last_line = 0;
        $word_found = false;
        foreach ($file_lines as $line) {
            $line_number++;
            if (strpos($line, $word_to_search) !== false) {
                $word_found = true;
                $last_line = $line_number;
            }
        }
        //End Add Line after use in route File

        $route = 'Route::resource("' . $table . '", ' . $controllerName . '::class' . ');';
        if (strpos($routeData, $route) === false) {
            //Add Use in Route File
            if ($word_found) {
                array_splice($file_lines, $last_line, 0, 'use ' . $controllerPath . ';');
            } else {
                array_splice($file_lines, 3, 0, 'use ' . $controllerPath . ';');
            }

            $file_lines[] = $route;

            file_put_contents($routeFile, implode(PHP_EOL, $file_lines));
        }

    }

    private function createModel(string $path, $modelName, $columns, $modelPath = "App\Models")
    {
        if (class_exists($path)) {
            $this->error("Model {$path} already exists.");
            return $path;
        }

        $columns = arrayToString($columns);

        $stubFile = base_path('curd/model.stub');

        $stub = file_get_contents($stubFile);

        $stub = str_replace('{{ class }}', $modelName, $stub);

        $stub = str_replace('{{ namespace }}', $modelPath, $stub);

        $stub = str_replace('{{ fields }}', $columns, $stub);

        $file = app_path("Models/{$modelName}.php");

        file_put_contents($file, $stub);

        $this->info("Model {$file} created successfully.");
        return $file;
    }

    protected function createController($controllerPath, $controllerName, $table, $baseControllerPath = "App\Http\Controllers")
    {
        try {
            if (class_exists($controllerPath)) {
                $this->error("Controller {$controllerPath} already exists.");
                return;
            }

            $model = Str::title(Str::singular($table));
            $modelVariable = Str::lower($model);
            $modelClassPath = "App\\Models\\{$model}";

            $stubFile = base_path('curd/controller.model.stub');
            $stub = file_get_contents($stubFile);

            $stub = str_replace('{{ class }}', $controllerName, $stub);
            $stub = str_replace('{{ model }}', $model, $stub);
            $stub = str_replace('{{ namespace }}', $baseControllerPath, $stub);
            $stub = str_replace('{{ namespacedModel }}', $modelClassPath, $stub);
            $stub = str_replace('{{ rootNamespace }}', "App\\", $stub);
            $stub = str_replace('{{ modelVariable }}', $modelVariable, $stub);

            $file = app_path("Http/Controllers/{$controllerName}.php");

            file_put_contents($file, $stub);
            $this->info("Controller {$file} created successfully.");
        } catch (Extension $e) {
            Log::error($e);
        }
    }

}
