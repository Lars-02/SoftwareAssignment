<?php

return [
    /** in .env.example (which will be copied over to .env) you can add
     * EQUIPMENT_IMPORT_PATH=/var/www/html/storage/imports (as an example)
     * if you know the exact path to the import folder or if the files arnt kept in the local storage path.
    **/
    'import_path' => env('EQUIPMENT_IMPORT_PATH', storage_path('imports')),
];
