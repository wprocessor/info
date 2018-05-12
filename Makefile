# Read project name from .env file
$(shell cp -n \.env.default \.env)
$(shell cp -n \.\/docker\/docker-compose\.override\.yml\.default \.\/docker\/docker-compose\.override\.yml)
include .env

COMPOSE_NET_NAME := $(shell echo $(COMPOSE_PROJECT_NAME) | tr '[:upper:]' '[:lower:]'| sed -E 's/[^a-z0-9]+//g')_front

all: include net

up:down
	@echo "Updating composition for $(COMPOSE_PROJECT_NAME)"
	docker-compose up -d --remove-orphans
	@make -s iprange

down:
	@echo "Removing networks for $(COMPOSE_PROJECT_NAME)"
	docker-compose down -v --remove-orphans

front:
	@echo "Building front tasks..."
	docker run --rm -it -v $(shell pwd)/web/themes/custom/$(THEME_NAME):/work skilldlabs/frontend:zen; \
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
	docker-compose exec -T php /bin/sh -c "chown $(shell id -u):$(shell id -g) /var/www/html -R"

exec0:
	docker-compose exec php ash

exec0-nginx:
	docker-compose exec nginx ash

exec0-mysql:
	docker-compose exec mysql ash
