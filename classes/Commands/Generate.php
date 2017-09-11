<?php

namespace Bkwld\Decoy\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Bkwld\Decoy\Exceptions\Exception;
use Symfony\Component\Console\Input\InputArgument;

class Generate extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'decoy:generate
        {model : The class name of a model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate controller/model/view given a model class name';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Figure out the stub directory
        $this->stubs = __DIR__.'/../../stubs/generate';
        if (!is_dir($this->stubs)) {
            throw new Exception('Could not find stubs dir');
        }

        // Generate
        $this->generateModel();
        $this->generateView();
        $this->generateController();
    }

    /**
     * Create the model file
     */
    private function generateModel()
    {
        // Figure out the naming
        $model = ucfirst($this->argument('model'));
        $path = 'app/'.$model.'.php';
        $file = base_path().'/'.$path;

        // Copy the stub over
        if (file_exists($file)) {
            return $this->comment('Model already exists: '.$path);
        }
        file_put_contents($file, str_replace('{{model}}', $model, file_get_contents($this->stubs.'/model.stub')));
        $this->info('Model created: '.$path);
    }

    /**
     * Create the view file
     */
    private function generateView()
    {
        // Figure out the naming
        $path = 'resources/views/admin/'
            . Str::plural(Str::snake($this->argument('model')))
            . '/edit.haml';
        $file = base_path().'/'.$path;

        // Copy the stub over
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0744, true);
        }
        if (file_exists($file)) {
            return $this->comment('View already exists: '.$path);
        }
        copy($this->stubs.'/view.stub', $file);
        $this->info('View created: '.$path);
    }

    /**
     * Create the controller file
     */
    private function generateController()
    {
        // Figure out the naming
        $controller = Str::plural(ucfirst($this->argument('model')));
        $path = 'app/Http/Controllers/Admin/'.$controller.'.php';
        $file = base_path().'/'.$path;

        // Copy the stub over
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0744, true);
        }
        if (file_exists($file)) {
            return $this->comment('Controller already exists: '.$path);
        }
        file_put_contents($file, str_replace('{{controller}}', $controller, file_get_contents($this->stubs.'/controller.stub')));
        $this->info('Controller created: '.$path);
    }
}
