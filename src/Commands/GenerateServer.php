<?php

namespace Greensight\LaravelOpenapiServerGenerator\Commands;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

use Illuminate\Console\Command;

class GenerateServer extends Command {
    /**
     * @var string
     */
    protected $signature = 'openapi:generate-server';

    /**
     * @var string
     */
    protected $description = 'Generate server from openapi spec files by OpenApi Generator';

    /**
     * @var string
     */
    private $apidocDir;

    public function __construct()
    {
        parent::__construct();

        $this->apidocDir = config('openapi-server-generator.apidoc_dir');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $apiDocs = $this->apiDocs();

        foreach ($apiDocs as $apiDoc) {
            $this->call('openapi:generate-server-version', $apiDoc);
        }
    }

    private function apiDocs() {
        $indexFiles = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->apidocDir,
                    FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
                )
            ),
            '/index\.yaml$/i',
            RegexIterator::MATCH
        );

        return collect($indexFiles)->map(function ($indexFile) {
            $version = basename(dirname($indexFile));
            $version = preg_match('/v\d/i', $version) ? $version : null;
            return [
                'file' => $indexFile,
                'version' => $version
            ];
        });
    }
}
