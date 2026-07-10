<?php

return [
    // env()'s own default only applies when the key is entirely absent, not when
    // it's declared but empty (as `.env.example` does), so fall back with `?:`.
    'equipments_path' => env('EQUIPMENTS_IMPORT_PATH') ?: storage_path('app/equipments'),
];
