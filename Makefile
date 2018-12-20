# Read project name from .env file
$(shell cp -n \.env.default \.env)
$(shell cp -n \.\/docker\/docker-compose\.override\.yml\.default \.\/docker\/docker-compose\.override\.yml)
include .env

# Get local values only once.
LOCAL_UID := $(shell id -u)
LOCAL_GID := $(shell id -g)

# Evaluate recursively.
CUID ?= $(LOCAL_UID)
CGID ?= $(LOCAL_GID)

COMPOSE_NET_NAME := $(shell echo $(COMPOSE_PROJECT_NAME) | tr '[:upper:]' '[:lower:]'| sed -E 's/[^a-z0-9]+//g')_front

IMAGE_FRONT ?= skilldlabs/frontend:zen
NODE_ENV ?= testing
front = docker run --rm --init -u $(CUID):$(CGID) -v $(shell pwd)/web/themes/custom/$(THEME_NAME):/work -e NODE_ENV=$(NODE_ENV) $(IMAGE_FRONT) ${1}

php = docker-compose exec -T --user $(CUID):$(CGID) php time ${1}
php-0 = docker-compose exec -T php time ${1}

all: | include net up info

up:down
	@echo "Updating images..."
	docker-compose pull --parallel
	@echo "Build and run containers for project $(COMPOSE_PROJECT_NAME)"
	docker-compose up -d --remove-orphans
	$(call php, composer global require -o --update-no-dev --no-suggest "hirak/prestissimo:^0.3")
ifeq ($(PROJECT_INSTALL), sql-manual)
	sleep 30
endif
	make -s reinstall

reinstall:
	$(call php, composer install --prefer-dist -o --no-dev --no-suggest)
	$(call php, composer drupal-scaffold)
	make -s front
	make -s si

si:
	$(call php, chmod +w web/sites/default)
ifneq ("$(wildcard web/sites/default/settings.php)","")
	$(call php-0, rm -f web/sites/default/settings.php)
endif
ifeq ($(PROJECT_INSTALL), sql-manual)
	sleep 30
endif
	@echo "Installing from: $(PROJECT_INSTALL)"
ifeq ($(PROJECT_INSTALL), config)
	$(call php, drush si config_installer --db-url=mysql://$(MYSQL_USER):$(MYSQL_PASSWORD)@mysql/$(MYSQL_DATABASE) --account-pass=admin -y config_installer_sync_configure_form.sync_directory=../config/sync)
	$(call php, drush en $(MODULES) -y)
	$(call php, drush pmu $(MODULES) -y)
else
	$(call php, cp site_settings/settings.php web/sites/default/settings.php)
	$(call php, drush cr)
	$(call php, drush updb -y --entity-updates)
endif

down: info
	@echo "Removing networks for $(COMPOSE_PROJECT_NAME)"
	docker-compose down -v --remove-orphans

clean: down

front:
	@echo "Building front tasks..."
	docker pull $(IMAGE_FRONT)
	$(call front, bower install)
	$(call front)
	$(call php-0, rm -rf web/themes/$(THEME_NAME)/node_modules)
	make -s chown

db-dump-up:
	docker-compose exec php mysql -s -u${MYSQL_ROOT_USER} -p${MYSQL_ROOT_PASSWORD} --host=mysql ${MYSQL_DATABASE} < ./db/marbin-wh.sql

# Utils
include:
ifeq ($(strip $(COMPOSE_PROJECT_NAME)),projectname)
#todo: ask user to make a project name and mv folders.
$(error Project name can not be default, please edit ".env" and set COMPOSE_PROJECT_NAME variable.)
endif

net:
ifeq ($(shell docker network ls -q -f Name=$(COMPOSE_NET_NAME)_front),)
	docker network create $(COMPOSE_PROJECT_NAME)_front
endif
	@make -s iprange

iprange:
	$(shell grep -q -F 'IPRANGE=' .env || echo "\nIPRANGE=$(shell docker network inspect $(COMPOSE_NET_NAME) --format '{{(index .IPAM.Config 0).Subnet}}')" >> .env)

info:
	$(info Containers for "$(COMPOSE_PROJECT_NAME)" info:)
	$(eval CONTAINERS := $(shell docker ps -f name=$(COMPOSE_PROJECT_NAME) --format "{{ .ID }}"))
	$(foreach CONTAINER, $(CONTAINERS),$(info $(shell docker inspect --format='{{.NetworkSettings.Networks.$(COMPOSE_PROJECT_NAME)_front.IPAddress}}{{range $$p, $$conf := .NetworkSettings.Ports}} {{$$p}} {{end}}: {{.Name}}' $(CONTAINER)) ))

chown:
# Use this goal to set permissions in docker container
	$(call php-0, /bin/sh -c "chown $(CUID):$(CGID) /var/www/html/web -R")
# Need this to fix files folder
	$(call php-0, /bin/sh -c "chown www-data: /var/www/html/web/sites/default/files -R")


exec0:
	docker-compose exec php ash

exec0-nginx:
	docker-compose exec nginx ash

exec0-mysql:
	docker-compose exec mysql ash

dev:
	@echo "Dev tasks..."
	$(call php, composer install --prefer-dist -o)
	$(call php-0, chmod +w web/sites/default)
	$(call php, cp web/sites/default/default.services.yml web/sites/default/services.yml)
	$(call php, sed -i -e 's/debug: false/debug: true/g' web/sites/default/services.yml)
	$(call php, cp web/sites/example.settings.local.php web/sites/default/settings.local.php)
	$(call php, drush -y config-set system.performance css.preprocess 0)
	$(call php, drush -y config-set system.performance js.preprocess 0)
	$(call php, drush en devel devel_generate webform_devel kint -y)
	$(call php, drush pm-uninstall dynamic_page_cache page_cache -y)
	$(call php, drush cr)
