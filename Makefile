init:
	docker-compose up -d --build
	docker-compose exec php composer install
	docker-compose exec php cp .env.example .env
	docker-compose exec php php artisan key:generate
	docker-compose exec php php artisan storage:link
	docker-compose exec php chmod -R 777 storage bootstrap/cache
	@make wait-db
	@make fresh

fresh:
	docker compose exec php php artisan migrate:fresh --seed

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

wait-db:
	@echo "🔄 Waiting for MySQL to be ready..."
	@until docker-compose exec php bash -c "nc -z mysql 3306"; do \
		sleep 1; \
		echo '⏳ Waiting for mysql:3306...'; \
	done
	@echo "✅ MySQL is ready!"
