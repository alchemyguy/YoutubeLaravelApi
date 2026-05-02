# Developer experience wrapper around Docker.
# Every PHP / Composer / dev tool invocation goes through `composer:2` image,
# which bundles PHP 8.x with the extensions this package needs.

DOCKER ?= docker
IMAGE  ?= composer:2
RUN    := $(DOCKER) run --rm -v "$(PWD)":/app -w /app $(IMAGE)

.PHONY: help install update test test-unit test-integration test-coverage \
        analyse fix lint rector php composer

help:
	@echo "Available targets:"
	@echo "  install            composer install"
	@echo "  update             composer update"
	@echo "  test               pest (full suite)"
	@echo "  test-unit          pest --testsuite=Unit"
	@echo "  test-integration   pest --group=integration"
	@echo "  test-coverage      pest --coverage --min=95"
	@echo "  analyse            phpstan analyse"
	@echo "  fix                pint"
	@echo "  lint               pint --test"
	@echo "  rector             rector process --dry-run"
	@echo "  php ARGS=...       run arbitrary php inside container"
	@echo "  composer ARGS=...  run arbitrary composer command"

install:
	$(RUN) composer install --no-interaction --prefer-dist

update:
	$(RUN) composer update --no-interaction --prefer-dist

test:
	$(RUN) ./vendor/bin/pest

test-unit:
	$(RUN) ./vendor/bin/pest --testsuite=Unit

test-integration:
	$(RUN) ./vendor/bin/pest --group=integration

test-coverage:
	$(RUN) ./vendor/bin/pest --coverage --min=95

analyse:
	$(RUN) ./vendor/bin/phpstan analyse

fix:
	$(RUN) ./vendor/bin/pint

lint:
	$(RUN) ./vendor/bin/pint --test

rector:
	$(RUN) ./vendor/bin/rector process --dry-run

php:
	$(RUN) php $(ARGS)

composer:
	$(RUN) composer $(ARGS)
