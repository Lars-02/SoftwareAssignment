# VESPA Software Assignment

## Requirements

- Docker and Docker Compose (recommended way to run this project)
- PHP 8.4 and Composer, if running locally instead
- Node 22 and npm, for building the frontend assets

## Setup

1. Copy the environment file and generate an app key:

   ```bash
   cp .env.example .env
   ```

2. Fill in `DB_PASSWORD` and `DB_ROOT_PASSWORD` in `.env` with values of your choice (they provision the MariaDB container; the app itself only ever connects as `DB_USERNAME`, never as root).

3. Start the stack:

   ```bash
   docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
   ```

4. Generate an app key and run migrations:

   ```bash
   docker compose exec php php artisan key:generate
   docker compose exec php php artisan migrate
   ```

5. Install and build the frontend assets (run on your host, not inside the container — there is no Node service):

   ```bash
   npm install
   npm run build
   ```

6. Visit [http://localhost:8000](http://localhost:8000).

## Importing equipment files

Drop a new `EQUIPMENTS_*.txt` export file into the directory configured by `EQUIPMENTS_IMPORT_PATH` (defaults to `storage/app/equipments/`, which is gitignored — imported files are never committed).

Run the import manually with:

```bash
docker compose exec php php artisan equipments:import {filePath}
```

With no argument, it picks the most recently named `EQUIPMENTS_*.txt` file in the import directory (filenames embed a sortable timestamp, e.g. `EQUIPMENTS_20260192150034.txt`). You can also pass an explicit path: `php artisan equipments:import /path/to/file.txt`.

Each import fully replaces the table's contents inside a single transaction (delete + insert) — the file is treated as a complete daily snapshot, not an incremental diff.

### Scheduling

The import is scheduled daily in `routes/console.php`:

```php
Schedule::command('equipments:import')->daily();
```

## Corrupt file detection

*(documented once implemented — see the extras below)*

## Partial file detection

*(documented once implemented — see the extras below)*
