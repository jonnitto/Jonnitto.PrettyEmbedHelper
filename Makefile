.PHONY: help build install prettier production watch dev upgrade

.DEFAULT_GOAL := build

## Install dependencies and build production version
build: install prettier production

## Install dependencies
install:
	@pnpm install --silent

## Prettier files
prettier:
	@pnpm prettier --write --no-error-on-unmatched-pattern '**/*.{js,php,yaml,scss,mjs,md}'

## Build production version
production:
	@echo '   ${GREEN}Remove Public files${RESET}'
	@rm -rf Resources/Public/Modules
	@rm -rf Resources/Public/Scripts
	@rm -rf Resources/Public/Styles
	@echo '   ${GREEN}Build CSS and Javascript${RESET}'
	@echo ''
	@pnpm build


## Watch files
dev:
	@pnpm dev

## Watch files
watch:
	@pnpm watch

## Check for upgrades
upgrade:
	@pnpm up --latest --interactive


# Define colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

# define indention for descriptions
TARGET_MAX_CHAR_NUM=15

## Show help
help:
	@echo ''
	@echo '${GREEN}CLI command list:${RESET}'
	@echo ''
	@echo 'Usage:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk '/^[a-zA-Z\-\_0-9]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")-1); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf "  ${YELLOW}%-$(TARGET_MAX_CHAR_NUM)s${RESET} ${GREEN}%s${RESET}\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)
	@echo ''
