<?php

return [
    /**
     * Directory the daily SAP IH09 exports (EQUIPMENTS_yyyymmddHHMMSS.txt)
     * are dropped in. Override via EQUIPMENT_IMPORT_PATH in .env when the
     * files aren't kept in the local storage path.
     *
     * `?:` (not the env() default) so an EMPTY value in .env also falls
     * back to storage/imports instead of silently breaking the import.
     */
    'import_path' => env('EQUIPMENT_IMPORT_PATH') ?: storage_path('imports'),

    /**
     * Partial-export guard: if a new file contains fewer records than this
     * fraction of what is currently in the database, the export is assumed
     * to be incomplete and is ignored until the next file arrives.
     */
    'partial_threshold' => (float) (env('EQUIPMENT_PARTIAL_THRESHOLD') ?: 0.5),
];
