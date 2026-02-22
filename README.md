# SoftwareAssignment

## Introduction
This code is created with the Onion architecture, separate the layers with Application, Domain and infrastructure layer.

## Requirements
- PHP 8.4
- Composer
- MariaDB / MySQL

## Setup
1. Install dependencies:
```bash
composer install
```
2. Create environment file:
```bash
cp .env.example .env
```
3. Configure database and import folder in `.env`:
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `EQUIPMENT_FOLDER` (folder under `storage/app`, default: `equipments`)
4. Generate app key:
```bash
php artisan key:generate
```
5. Run migrations:
```bash
php artisan migrate
```

## Place Equipment Files
New equipment export files must be placed in:

`storage/app/<EQUIPMENT_FOLDER>/`

With default config:

`storage/app/equipments/`

The importer always selects the latest file by modification time.

## Run Project Locally
1. Start Laravel server:
```bash
php artisan serve
```

## Run Equipment Import Manually
```bash
php artisan import:equipment
```

## Run Tests
```bash
composer test:unit
composer test:happy
composer test:sad
```

## Corrupt Files Handling
If a new equipment file is corrupt, import is rejected and no equipment rows are inserted.

Checks performed before import:
- Corrupt files throws `InvalidFileException`.
- Header validation: all header label must exist (Header reference from the equipment file)
- 3 lines validation: total lines must be 3 per equipment row.
- Fixed-width validation: per line must have exactly 252 characters.
- Equipment value: each equipment row must contain a valid equipment value.
- When exception happen, the current data remains unchanged and if file has been fix, then just run the Equipment import again


## Partial Files Handling
- Partial export throws `IncompleteFileException`.
- Minimum export equipment: at least 50 equipments are required.
- When exception happen, the current data remains unchanged and it will only process the next file and ignore the current file


## Routes
Default route to see all the view table: '/'
