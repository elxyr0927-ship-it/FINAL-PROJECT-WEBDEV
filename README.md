# YOCOR Express Admin

This Symfony application contains the migrated Admin logistics workspace and the Staff shipment-record workflow. It uses the Symfony dependencies and Docker Compose configuration already present in this project.

## Setup

1. Copy the template variables from `.env` into `.env.local` and set your database credentials there (`.env.local` is git-ignored, so real credentials are never committed).
2. Start the project's Compose services with `docker compose up -d`.
3. Download the PHP dependencies with `composer install` and install the frontend assets with `php bin/console importmap:install`.
4. If the configured database is empty, apply the schema:

   ```powershell
   php bin/console doctrine:migrations:migrate
   ```

   The initial migration creates the project tables. Do not run it against a database containing data you need to preserve.
5. Create the first administrator interactively:

   ```powershell
   php bin/console app:create-admin
   ```

6. Serve the app with `symfony serve` (or `php -S 127.0.0.1:8000 -t public`) and open `http://127.0.0.1:8000`.

The initial migration has already been applied and the schema is in sync with the mapping files. Run `doctrine:migrations:migrate` only for new migrations. `app:create-admin` fails if an active administrator already exists; use `app:reset-admin-password` to reset an existing administrator password.

## Checks

```powershell
php bin/console lint:container
php bin/console lint:twig templates
php bin/phpunit
```
