# VESPA Software Assignment

Imports an SAP equipment export into a database on a daily schedule, and shows the result on a searchable page.

## Requirements

- Docker and Docker Compose (recommended — no local PHP/Node needed)
- PHP 8.4 and Composer, if running locally instead
- Node 22 and npm, if building frontend assets locally instead

## Setup

1. `cp .env.example .env`
2. Fill in `DB_PASSWORD` and `DB_ROOT_PASSWORD` in `.env` (any values — they provision the MariaDB container; the app itself only ever connects as `DB_USERNAME`, never as root)
3. `docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm php php artisan key:generate`
4. `docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build`
5. `docker compose exec php php artisan migrate`
6. Visit [http://localhost:8000](http://localhost:8000)

### Frontend assets

Building assets is a one-shot build step, not a long-running service, so the `node` container isn't kept up by default — it runs its build and exits. It builds automatically on `docker compose up`, but to (re)build manually after a frontend change:

```bash
docker compose run --rm node
```

## Environment variables

All documented in `.env.example`. Notable ones:

- `DB_USERNAME` / `DB_PASSWORD` — credentials the app itself connects with
- `DB_ROOT_PASSWORD` — only provisions the MariaDB container; the app never uses it
- `EQUIPMENTS_IMPORT_PATH` — directory scanned for new export files, defaults to `storage/app/equipments/` (gitignored — imported files are never committed)

## Importing equipment files

- Drop a new `EQUIPMENTS_*.txt` file into the directory from `EQUIPMENTS_IMPORT_PATH`
- Run manually: `docker compose exec php php artisan equipments:import`
  - With no argument, picks the most recently *named* file (filenames embed a sortable timestamp)
  - Or pass an explicit path: `php artisan equipments:import /path/to/file.txt`
- Each import is a **full replace** in one transaction (delete + insert) — the file is treated as a complete daily snapshot, not a diff

### Scheduling

- Defined in `routes/console.php`: `Schedule::command('equipments:import')->daily();`
- **Docker**: the `scheduler` container already runs `php artisan schedule:work` — nothing else to set up
- **Without Docker**: add to crontab: `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1`

## Searching

- Search box on the equipments page filters by Equipment, Material, Description, and Room
- Validated with a Laravel Form Request

## Corrupt file detection

Before touching the database, the file is rejected (existing data untouched) if any of these checks fail:

- File is non-empty and every line is exactly 252 characters wide
- Header borders (lines 1 and 5) and the footer (last line) are present and correctly formatted
- Header column labels (`Material`, `Equipment`, `Room`, `Gross Weight`, `Plnt`, `Created On`, `Created By`, `Chngd On`, `Changed by`) are present, to catch a differently-structured file with matching borders
- Body is a non-zero multiple of 3 lines (each equipment record spans 3 lines)
- Each record's Equipment number can be located (9–11 digits), which also detects/corrects the few known-misaligned rows found in real exports
- `CreatedOn` / `ChangedOn` parse to real calendar dates (e.g. `31.02.2020` is rejected)

On failure, `equipments:import` prints the error and exits non-zero.

## Partial file detection

- Every import is a full replace, so a new file is expected to have at least as many rows as currently in the table
- If it has fewer, some equipment is missing from the export, the file is rejected and existing data is kept
- The very first import into an empty table is always allowed, since there's nothing to compare against

## Running tests

```bash
docker compose exec php ./vendor/bin/phpunit
```

- The `php`/`scheduler` images are built without dev dependencies, so PHPUnit isn't in them by default
- To install dev dependencies once into the shared `vendor` volume:

  ```bash
  docker build --target build --build-arg INSTALL_DEV=true -t asml-assignment-php-build -f .docker/php/Dockerfile .
  docker run --rm -v "$(pwd)":/usr/src/app -v asml-assignment_vendor-data:/usr/src/app/vendor -w /usr/src/app asml-assignment-php-build composer install
  ```

## Notable deviations

- `database/migrations/*_create_equipments_table.php` matches the provided `CREATE TABLE` statement exactly, including the `'0000-00-00'` defaults. Laravel's default strict mode adds `NO_ZERO_DATE`/`NO_ZERO_IN_DATE` to every connection, which would make those defaults unusable — `config/database.php` overrides the connection's `modes` to drop just those two.
