# Docker

Запуск NativeGallery в контейнерах: **приложение (nginx + PHP 8.3-FPM)** и **MariaDB 11**.

Production форка по-прежнему рекомендуется на **Ubuntu + Nginx** без Docker; образ — для dev, тестов и хостингов с контейнерами.

## Быстрый старт

```bash
git clone https://github.com/Lanmikeman/nativegallery.git
cd nativegallery
cp docker-compose.example.env .env   # опционально, отредактируйте пароли
docker compose up -d --build
```

Сайт: http://localhost:8080 (порт задаётся `NG_HTTP_PORT` в `.env`).

При первом старте entrypoint:
- ждёт готовности БД;
- генерирует `ngallery.yaml` из переменных окружения;
- импортирует миграции `sqlcore/*.sql` (маркер `storage/.schema_version`).

## Сборка образа

```bash
docker build -t nativegallery:latest .
docker run --rm -p 8080:8080 \
  -e NG_DB_HOST=host.docker.internal \
  -e NG_DB_NAME=ngallery \
  -e NG_DB_USER=ngallery \
  -e NG_DB_PASS=secret \
  -e NG_DOMAIN=localhost \
  nativegallery:latest
```

Публикация (пример):

```bash
docker tag nativegallery:latest ghcr.io/lanmikeman/nativegallery:latest
docker push ghcr.io/lanmikeman/nativegallery:latest
```

## docker-compose.yml

| Сервис | Описание |
|--------|----------|
| `app` | NativeGallery, порт **8080** |
| `db` | MariaDB 11, volume `ng_db` |

Volumes (данные сохраняются между перезапусками):

- `ng_uploads`, `ng_cdn`, `ng_storage`, `ng_logs`, `ng_db`

## Переменные окружения (app)

| Переменная | По умолчанию | Описание |
|------------|--------------|----------|
| `NG_DB_HOST` | `db` | Хост MySQL/MariaDB |
| `NG_DB_NAME` | `ngallery` | Имя БД |
| `NG_DB_USER` | `ngallery` | Пользователь |
| `NG_DB_PASS` | `ngallery_secret` | Пароль |
| `NG_DOMAIN` | `localhost` | `alloweddomains` |
| `NG_SITE_TITLE` | `NativeGallery` | Заголовок |
| `NG_ADMIN_EMAIL` | `admin@localhost` | Email в конфиге |
| `NG_TIMEZONE` | `Europe/Moscow` | Часовой пояс |
| `NG_DEBUG` | `false` | Tracy debug |
| `NG_AUTO_MIGRATE` | `1` | Импорт SQL при старте |
| `NG_FORCE_MIGRATE` | `0` | `1` — повторить миграции |
| `NG_REGENERATE_CONFIG` | `0` | `1` — перезаписать `ngallery.yaml` |
| `NG_ENCRYPTION_KEY` | (random) | Ключ шифрования |
| `NG_WEB_ROOT` | `/var/www/html` | Корень приложения |

## Обновление

```bash
git pull
docker compose build --no-cache app
docker compose up -d
```

Новые SQL-файлы: удалите маркер и перезапустите:

```bash
docker compose exec app rm -f storage/.schema_version
docker compose restart app
```

Или `NG_FORCE_MIGRATE=1` (осторожно на production с данными).

## Cron

Внутри контейнера `app` cron запускает `ExecContests.php` каждые 5 минут (supervisor + `/etc/cron.d/nativegallery`).

Ручной запуск:

```bash
docker compose exec app php app/Controllers/Exec/Tasks/ExecContests.php
```

## Файлы

| Путь | Назначение |
|------|------------|
| `Dockerfile` | Сборка образа |
| `docker-compose.yml` | Оркестрация |
| `docker/entrypoint.sh` | Конфиг, миграции, cron |
| `docker/nginx.conf` | Nginx :8080 |
| `docker/supervisord.conf` | nginx + php-fpm + cron |

## Ограничения

- Внешний reverse proxy (Traefik, Caddy) должен проксировать на порт **8080**.
- Для production с большим трафиком предпочтительнее bare-metal [manual-install.md](manual-install.md) или Ubuntu-скрипт.
- WebSocket-функции движка в Docker не выделены в отдельный сервис.