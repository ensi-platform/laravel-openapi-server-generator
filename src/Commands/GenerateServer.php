<?php

namespace Ensi\LaravelOpenApiServerGenerator\Commands;

use cebe\openapi\Reader;
use cebe\openapi\SpecObjectInterface;
use Ensi\LaravelOpenApiServerGenerator\Exceptions\EnumsNamespaceMissingException;
use Illuminate\Console\Command;
use LogicException;

class GenerateServer extends Command
{
    public const SUPPORTED_ENTITIES = [
        'controllers',
        'enums',
        'requests',
        'routes',
        'pest_tests',
        'resources',
        'policies',
    ];

    /** var @string */
    protected $signature = 'openapi:generate-server {--e|entities=}';

    /** var @string */
    protected $description = 'Generate application files from openapi specification files';

    private array $config = [];

    private array $enabledEntities = [];

    public function __construct()
    {
        parent::__construct();

        $this->config = config('openapi-server-generator', []);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $inputEntities = $this->option('entities') ? explode(',', $this->option('entities')) : [];
        $this->enabledEntities = $inputEntities ?: $this->config['default_entities_to_generate'];

        if (!$this->validateEntities()) {
            return self::FAILURE;
        }

        foreach ($this->config['api_docs_mappings'] as $sourcePath => $optionsPerEntity) {
            $this->info("Generating [" . implode(', ', $this->enabledEntities) . "] for specification file \"$sourcePath\"");
            if (self::FAILURE === $this->handleMapping($sourcePath, $optionsPerEntity)) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    public function handleMapping(string $sourcePath, array $optionsPerEntity)
    {
        $specObject = $this->parseSpec($sourcePath);

        foreach (static::SUPPORTED_ENTITIES as $entity) {
            $generatorClass = $this->config['supported_entities'][$entity] ?? null;
            if (!isset($generatorClass)) {
                continue;
            }

            if (!$this->shouldEntityBeGenerated($entity)) {
                continue;
            }

            if (!isset($optionsPerEntity[$entity])) {
                $this->error("Options for entity \"$entity\" are not set in \"api_docs_mappings\" config for source \"$sourcePath\"");

                return self::FAILURE;
            }

            $this->infoIfVerbose("Generating files for entity \"$entity\" using generator \"$generatorClass\"");

            try {
                resolve($generatorClass)->setOptions($optionsPerEntity)->generate($specObject);
            } catch (EnumsNamespaceMissingException) {
                $this->error("Option \"enums_namespace\" for entity \"$entity\" are not set in \"api_docs_mappings\" config for source \"$sourcePath\"");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function validateEntities(): bool
    {
        $supportedEntities = array_keys($this->config['supported_entities'] ?? []);
        foreach ($this->enabledEntities as $entity) {
            if (!in_array($entity, $supportedEntities)) {
                $this->error("Invalid entity \"$entity\", supported entities: [" . implode(', ', $supportedEntities) ."]");

                return false;
            }
        }

        return true;
    }

    private function shouldEntityBeGenerated(string $entity): bool
    {
        return in_array($entity, $this->enabledEntities);
    }

    private function parseSpec(string $sourcePath): SpecObjectInterface
    {
        return match (substr($sourcePath, -5)) {
            '.yaml' => Reader::readFromYamlFile(realpath($sourcePath)),
            '.json' => Reader::readFromJsonFile(realpath($sourcePath)),
            default => throw new LogicException("You should specify .yaml or .json file as a source. \"$sourcePath\" was given instead"),
        };
    }

    protected function infoIfVerbose(string $message): void
    {
        $this->info($message, 'v');
    }
}
