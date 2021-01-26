<?php

return [

    /**
     * Path to the directory where index.yaml openapi file located
     */
    'apidoc_dir' => public_path('api-docs'),

    /**
     * Path to the directory where DTO files are temporary generated.
     * Matches the -o option in openapi generator
     * Old name: `output_dir`
     */
    'temp_dir' => base_path('generated'),

    /**
     * Path relative to the app/ directory where DTO files will be located.
     * Old name: `app_dir`
     */
    'destination_dir' => 'Http/Api{version}/OpenApiGenerated'
];
