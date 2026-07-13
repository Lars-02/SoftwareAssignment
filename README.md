# VESPA — Software Assignment

A small recreation of ASML's VESPA tool: imports a daily SAP IH09 equipment
export into a MariaDB database and displays it in a searchable table.

## Stack

- PHP 8.4
- Laravel 12
- MariaDB
- Tailwind CSS (no SPA framework — search is done with vanilla JS + `fetch`)
- Node 22 / npm (for building front-end assets)

## Requirements

Install locally (via WAMP, XAMPP, your Linux distro's packages, or however
you prefer to run a PHP stack):

- PHP 8.4, with the `pdo_mysql`, `mbstring`, and `zip` extensions enabled
- Composer 2
- Node 22 and npm
- MariaDB (11.x recommended)

## Getting started

1. Clone the repository and install dependencies:

   ```bash
   composer install
   npm install
   ```

2. Copy the environment file and generate an app key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Create a database matching your `.env` settings. By default:

   ```env
   DB_CONNECTION=mariadb
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=softwareassignment
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   Adjust these to match your local MariaDB setup (e.g. the credentials WAMP
   or your package manager configured), then create the database itself,
   for example:

   ```sql
   CREATE DATABASE softwareassignment;
   ```

4. Run the migrations:

   ```bash
   php artisan migrate
   ```

5. Build the front-end assets:

   ```bash
   npm run build
   ```

6. Serve the application. Either point your webserver's document root at the
   `public/` folder (the usual approach with WAMP/Apache/Nginx), or, for
   local development, use the built-in server:

   ```bash
   php artisan serve
   ```

7. Visit **http://localhost:8000/equipments** (or your configured vhost URL)
   to view the table.

## Importing equipment files

Place new export files (named `EQUIPMENTS_<yyyymmddHHMMSS>.txt`, matching
SAP's IH09 export format) in the folder configured by the
`EQUIPMENT_IMPORT_PATH` environment variable. If unset, this defaults to
`storage/imports` inside the project.

To use a different folder, add this to your `.env`:

```env
EQUIPMENT_IMPORT_PATH=/path/to/your/import/folder
```

Leaving it blank (as in `.env.example`) falls back to `storage/imports`.

### Running the import manually

```bash
php artisan equipments:import
```

By default this picks the **newest** file in the import folder (based on the
timestamp in the filename). You can also import a specific file:

```bash
php artisan equipments:import storage/imports/EQUIPMENTS_20260212150034.txt
```

Each valid import **replaces** the table contents inside a single database
transaction. A daily export is a complete snapshot of SAP, so equipment
that no longer appears in the export is removed here too. The swap only
happens after the file has passed the corruption and partial-export checks,
and re-running the import with the same file is idempotent.

### Scheduled import

The import is registered in `routes/console.php` to run **daily**:

```php
Schedule::command('equipments:import')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/equipments-import.log'));
```

Laravel's scheduler needs a single cron entry (or Windows Task Scheduler
equivalent) running every minute, which is what actually checks whether any
scheduled task — including the daily import — is due to run.

**Linux/macOS**, add to your crontab (`crontab -e`):

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

**Windows (WAMP)**, create a Task Scheduler task that runs every minute:

```
Program:    C:\wamp64\bin\php\php8.4.x\php.exe
Arguments:  artisan schedule:run
Start in:   C:\path\to\project
```

Output/errors from each import run are appended to
`storage/logs/equipments-import.log`.

### Corrupt files

If an export file doesn't match the expected SAP IH09 structure, the import
throws an `EquipmentFileCorruptException` and **aborts without touching
existing data**. The following checks are performed before any row is
written to the database:

- The file exists and is readable.
- The file isn't empty.
- The expected 3-line SAP header block is present.
- The number of data lines is a multiple of 3 (each equipment record spans
  exactly 3 physical lines in the export) — a truncated file fails this
  check.
- Each record block contains a recognisable `<equipment number> <user
  status> <system status>` anchor to parse from — a structurally malformed
  block fails this check.

The command logs the error and exits with a non-zero status rather than
partially importing a bad file.

### Partial (incomplete) exports

A file can be structurally valid but still represent a failed/partial SAP
export — many tools missing even though nothing looks corrupt. To guard
against this, the import compares the new file's record count against what's
currently in the database:

- If the new file has fewer records than `EQUIPMENT_PARTIAL_THRESHOLD`
  (default `0.5`, i.e. 50%) of the current row count, it's treated as
  partial: the import is skipped, existing data is left untouched, and the
  command exits with a warning, waiting for the next day's file.
- The very first import (empty table) always proceeds, since there's
  nothing yet to compare against.
- The threshold is configurable via `.env`:

  ```env
  EQUIPMENT_PARTIAL_THRESHOLD=0.5
  ```

- To bypass the check (e.g. when deliberately importing a smaller test
  file), pass `--force`:

  ```bash
  php artisan equipments:import --force
  ```

## Viewing & searching equipments

The table view at `/equipments` shows all imported equipment records
(paginated, 25 per page) and handles the empty-database case with a
"No equipment found" message.

The site root (`/`) redirects straight to `/equipments`.

A single search box matches against **Equipment, Material, Description, and
Room** (partial, case-insensitive match on any of the four). Searching is
done via `fetch()` against the same route, so the table updates without a
full page reload — no SPA framework is used, just vanilla JS with a small
debounce on keystrokes. Pagination links are intercepted the same way, so
paging through results also stays AJAX-driven. The URL updates alongside
the search and pagination (via `history.pushState`) so the current state
survives a refresh or can be shared as a link; browser back/forward
correctly restores the page at that URL.

Input is validated server-side via `SearchEquipmentRequest` (max length
191, matching the shortest of the four searched columns) and mirrored
client-side with a `maxlength` attribute on the input.

## Project structure (relevant parts)

```
app/
  Console/Commands/ImportEquipments.php   # artisan command, scheduled daily
  DataTransferObjects/EquipmentRecord.php # immutable parsed record
  Exceptions/EquipmentFileCorruptException.php
  Services/EquipmentFileParser.php        # fixed-width SAP file parser
  Http/Controllers/EquipmentController.php
  Http/Requests/SearchEquipmentRequest.php
  Models/Equipment.php
config/equipments.php                     # import_path setting
database/migrations/..._create_equipments_table.php
resources/views/equipments/
  index.blade.php                         # search form + results container
  partials/table.blade.php                # table + pagination (AJAX target)
routes/console.php                        # schedule definition
routes/web.php
```

## Notes / assumptions

- The import path is kept configurable through `EQUIPMENT_IMPORT_PATH`,
  defaulting to `storage_path('imports')` if unset **or blank** — the
  config uses `env('EQUIPMENT_IMPORT_PATH') ?: storage_path('imports')`
  rather than `env()`'s own default, so an empty value in `.env` (as
  shipped in `.env.example`) still falls back safely instead of resolving
  to an empty path. The assignment only asks for a single configurable
  location, so no more elaborate file-discovery mechanism was built.
- Database columns without a direct source in the export file (cleaning
  counters, certification/calibration intervals and dates, etc.) are left
  at their schema defaults, since the source file doesn't contain that
  data.
- No manual file upload UI is included — the import is designed to run
  purely on a schedule against a fixed folder, per the assignment.
- Local development was done against a containerised PHP/MariaDB stack, but
  the project itself has no dependency on that — these instructions assume
  a standard native PHP setup (WAMP, XAMPP, or a Linux LAMP-style stack).

## Running tests

```bash
php artisan test
```

`tests/Unit/EquipmentFileParserTest.php` covers the parser directly against
a synthetic fixture (built with real column alignment but no real data):
the four searchable fields, dimension-vs-description disambiguation,
European number formatting, deduplication (last occurrence wins), and both
corrupt-file paths (empty file, truncated record block).
