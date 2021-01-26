<?php

return [

    /**
     * Path to the directory where v{N}/index.yaml openapi files are located.
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
    'destination_dir' => 'Http/Api{version}/OpenApiGenerated',

    /**
     * Directory where you can place templates to override default ones. . Used in -t
     */
    'template_dir' => '',

    /*
     * Preserve only enums - *Enum.php
     */
    'only_enums_mode' => true,
];
