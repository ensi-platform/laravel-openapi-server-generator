<?php

namespace Greensight\LaravelOpenapiServerGenerator\Commands;

use Illuminate\Console\Command;

class GenerateServer extends Command {

    /**
     * @var string
     */
    protected $signature = 'openapi:generate-server';

    /**
     * @var string
     */
    protected $description = 'Generate server by from openapi spec files by OpenApi Generator';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $param = config('openapi-server-generator.test_param');
        shell_exec("echo 'Hello from OpenapiServerGenerator: param: $param'");
    }
}
