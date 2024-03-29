.PHONY: help
.DEFAULT_GOAL := help


## Show help
help:
	@echo "Usage:"
	@echo "  make <target>"
	@echo ""
	@echo "Targets:"
	@awk '/^[a-zA-Z\-\_0-9]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")-1); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf "  %-20s %s\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)
	@echo ""
	@echo ""


## initialize project
install:
	composer update

## fix php code style
ecs:
	vendor/bin/ecs --fix

## check code with phpstan
phpstan:
	vendor/bin/phpstan --memory-limit=2G

## check code with phpstan
styles:
	make ecs
	make phpstan


## PHP Unit
phpunit:
	vendor/bin/simple-phpunit


