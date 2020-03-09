SHELL := /bin/bash
tests:
	symfony console doctrine:fixtures:load -n
	symfony run bin/phpunit

docker-reload:
	docker-compose stop
	docker-compose up -d

.PHONY: tests
