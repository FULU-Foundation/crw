.DEFAULT_GOAL := run

init:
	sh scripts/db-init-dev.sh

update:
	sh scripts/update-dev.sh

run-jobs:
	sh scripts/run-jobs.sh
	
run:
	docker compose up --build

run-bg:
	docker compose up --build -d

build-deploy:
	docker build . -t registry.digitalocean.com/crw-wiki/wiki:latest
	docker push registry.digitalocean.com/crw-wiki/wiki:latest

bash:
	docker compose exec -i crw-local bash