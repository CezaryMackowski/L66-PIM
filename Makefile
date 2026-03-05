PHP_SERVICE := fpm
DOCKER_COMPOSE_CMD ?= docker compose
EXEC_COMMAND ?= $(DOCKER_COMPOSE_CMD) exec $(PHP_SERVICE)

.PHONY: help

help: # Show help for each of the Makefile recipes.
	@grep -E '^[a-zA-Z0-9 -]+:.*#'  Makefile | while read -r l; do printf "\033[1;32m$$(echo $$l | cut -f 1 -d':')\033[00m:$$(echo $$l | cut -f 2- -d'#')\n"; done

#========== Project ==========#

up: # Start project
	${DOCKER_COMPOSE_CMD} up -d

down: # Stop project
	${DOCKER_COMPOSE_CMD} down

bash: # Open a bash terminal in the PHP container
	${EXEC_COMMAND} bash

phpunit: # Run the PHPUnit tests
	${EXEC_COMMAND} bin/phpunit tests

phpcsfixer: # Check code style with PHP CS Fixer
	${EXEC_COMMAND} php -dmemory_limit=-1 vendor/bin/php-cs-fixer --no-interaction --allow-risky=yes --dry-run --diff fix

phpcsfixer-fix: # Fix code style issues with PHP CS Fixer
	${EXEC_COMMAND} php -dmemory_limit=-1 vendor/bin/php-cs-fixer --no-interaction --allow-risky=yes --ansi fix

phpstan: # Run static analysis with PHPStan
	${EXEC_COMMAND} php -dmemory_limit=-1 vendor/bin/phpstan --configuration=phpstan.dist.neon analyse src

test: # Run Phpunit
	${EXEC_COMMAND} vendor/bin/phpunit tests

composer: # Run composer install
	${EXEC_COMMAND} composer install -o

build: # Build the Docker images
	${DOCKER_COMPOSE_CMD} build

cache-clear: # Clear Symfony cache
	${EXEC_COMMAND} bin/console cache:pool:clear --all

prepare-pr: phpcsfixer-fix phpstan phpunit # Prepare the code for a pull request by running code style fix, static analysis, and tests

#========== Project ==========#

#========== Database ==========#

create-db: # Execute of all migrations on the main database
	${EXEC_COMMAND} bin/console doctrine:database:create --if-not-exists
	${EXEC_COMMAND} bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

create-db-test: # Create the test database and execute all migrations
	${EXEC_COMMAND} bin/console doctrine:database:create --env=test --if-not-exists
	${EXEC_COMMAND} bin/console doctrine:migrations:migrate --env=test  --no-interaction --allow-no-migration

recreate-db: # Drop and recreate the main database, then execute all migrations and load fixtures
	${EXEC_COMMAND} bin/console doctrine:database:drop --force --if-exists
	${EXEC_COMMAND} bin/console doctrine:database:create --if-not-exists
	${EXEC_COMMAND} bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	${EXEC_COMMAND} bin/console doctrine:fixtures:load --no-interaction

recreate-db-test: # Drop and recreate the test database, then execute all migrations and load fixtures
	${EXEC_COMMAND} bin/console doctrine:database:drop --force --env=test --if-exists
	${EXEC_COMMAND} bin/console doctrine:database:create --env=test --if-not-exists
	${EXEC_COMMAND} bin/console doctrine:migrations:migrate --env=test --no-interaction --allow-no-migration

migration-all: migration migration-test # Execute all migrations for both databases

migration: # Execute all migrations for database
	${EXEC_COMMAND} bin/console doctrine:migrations:migrate --allow-no-migration

migration-test: # Execute all migrations for test database
	${EXEC_COMMAND} bin/console doctrine:migrations:migrate --env=test --no-interaction --allow-no-migration

fixtures: # Load tasks to database using fixtures
	${EXEC_COMMAND} php bin/console doctrine:fixtures:load --no-interaction
#========== Database ==========#

#========== Swagger ==========#
dump-config-json: # Dump documentation in OpenAPI format to json
	${EXEC_COMMAND} bin/console nelmio:apidoc:dump --format=json

dump-config-yaml: # Dump documentation in OpenAPI format to yaml
	${EXEC_COMMAND} bin/console nelmio:apidoc:dump --format=yaml
#========== Swagger ==========#
