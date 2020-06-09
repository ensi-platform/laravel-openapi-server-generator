<?php

return [

    /**
     * Path to the directory where index.yaml openapi file located
     */
    'apidoc_dir' => public_path('api-docs'),

    /**
     * Path to the directory where dto model files are generated
     * Matches the -o option in openapi generator
     */
    'output_dir' => './generated',

    /*
     * Path relative to the app directory where dto models will be located
     */
    'app_dir' => 'OpenApiGenerated',

    /**
     * Path to npm wrapper for openapi-generator binary
     */
    'openapi_generator_bin' => base_path('node_modules/.bin/openapi-generator')
];
