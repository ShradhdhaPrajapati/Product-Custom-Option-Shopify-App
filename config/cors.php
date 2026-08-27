<?php

return [

    'paths' => ['api1/*','sanctum/csrf-cookie'], //web rout path

    'allowed_methods' => ['*'],

    'allowed_origins' => [''],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
