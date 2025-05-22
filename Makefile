init:
	docker-compose up -d --build
	docker-compose exec php composer install
	docker-compose exec php php artisan dusk:chrome-driver
	docker-compose exec php cp .env.example .env
	docker-compose exec php php artisan key:generate
	@make cache
	@make fresh

fresh:
	docker compose exec php php artisan migrate:fresh
	docker compose exec php php artisan db:seed

restart:
	@make down
	@make up

up:
	docker-compose up -d

down:
	docker compose down --remove-orphans

cache:
	docker-compose exec php php artisan cache:clear
	docker-compose exec php php artisan config:cache

stop:
	docker-compose stop

phpunit:
	docker-compose exec php php artisan key:generate --env=testing
	@make cache
	docker-compose exec php php artisan test

dusk:
	docker-compose exec -T php vendor/laravel/dusk/bin/chromedriver-linux --port=9515 &
	docker-compose exec -T php php artisan serve &
	docker-compose exec php php artisan dusk
	docker-compose exec php pkill -f 'vendor/laravel/dusk/bin/chromedriver-linux' || true
	docker-compose exec php pkill -f 'php artisan serve' || true
	docker-compose exec php bash -c 'pgrep -f "/var/www/server.php" | xargs kill' || true

