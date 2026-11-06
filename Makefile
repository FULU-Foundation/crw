.DEFAULT_GOAL := run

init:
	sh scripts/db-init-dev.sh

update:
	sh scripts/update-dev.sh

run-jobs:
	sh scripts/run-jobs.sh

cirrus-config:
	sh scripts/cirrus-config.sh

cirrus-reindex:
	sh scripts/cirrus-reindex.sh

meta:
	sh scripts/wiki-meta.sh

sitemap:
	sh scripts/generate-sitemap.sh

content-build:
	sh scripts/content-build.sh

content-diff:
	sh scripts/content-diff.sh

content-publish:
	sh scripts/content-publish.sh

batch-antispoof:
	sh scripts/batch-antispoof.sh

run:
	docker compose up --build

run-bg:
	docker compose up --build -d

bash:
	docker compose exec -i crw-local bash
