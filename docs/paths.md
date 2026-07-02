# Пути и каталоги по умолчанию

Скрипты и документация используют **стандартные пути**, если не задана переменная `NG_WEB_ROOT` (или аналог в Windows). На production путь часто другой — из‑за разметки дисков, монтирования NTFS и т.п.

## Сводная таблица

| Назначение | Значение по умолчанию | Пример production (форк) | Docker | Pterodactyl |
|------------|----------------------|--------------------------|--------|-------------|
| **Корень проекта** | `/var/www/nativegallery` | `/mnt/win/nativegallery` | `/var/www/html` | `/home/container` |
| **Домен** | `example.com` | `cttc.fetbuk.ru` | `localhost` | IP / домен из панели |
| **Конфиг** | `{корень}/ngallery.yaml` | то же | то же | то же |
| **Загрузки** | `{корень}/uploads/` | то же | volume `ng_uploads` | volume сервера |
| **CDN / превью** | `{корень}/cdn/` | то же | volume `ng_cdn` | то же |
| **Overlay / locks** | `{корень}/storage/` | `auth-settings.json`, `server-settings.json` | volume `ng_storage` | то же |
| **Логи** | `{корень}/logs/` | Tracy, `cron.log` | volume `ng_logs` | то же |
| **SQL-миграции** | `{корень}/sqlcore/` | не менять | в образе | после `git clone` |
| **Cron-задачи** | `/etc/cron.d/nativegallery` | то же | `/etc/cron.d/nativegallery` в контейнере | cron в образе |
| **Nginx site** | `/etc/nginx/sites-available/nativegallery` | root → ваш `NG_WEB_ROOT` | `docker/nginx.conf` | — |
| **Apache vhost** | `/etc/apache2/sites-available/nativegallery.conf` | DocumentRoot → ваш путь | — | — |
| **PHP-FPM socket (Debian)** | `/run/php/php8.3-fpm.sock` | — | то же | то же |
| **PHP-FPM socket (RHEL)** | `/run/php-fpm/www.sock` | — | — | — |
| **Nginx access log** | `/var/log/nginx/nativegallery.access.log` | — | stdout контейнера | — |
| **Пользователь PHP/cron (Linux)** | `www-data` | то же | `www-data` | `www-data` |
| **Пользователь PHP (RHEL)** | `apache` | — | — | — |

## Windows (IIS / Apache)

| Назначение | По умолчанию | Примечание |
|------------|--------------|------------|
| **Корень проекта** | `C:\inetpub\nativegallery` | любой каталог с правами IIS |
| **Домен** | `localhost` | привязка сайта в IIS |
| **Конфиг IIS** | `{корень}\web.config` | в репозитории |
| **Права на запись** | `IIS_IUSRS`, `IUSR` | `uploads`, `cdn`, `logs`, `storage` |
| **Планировщик (cron)** | Task Scheduler | см. `deploy/windows/setup-task-scheduler.ps1` |
| **PHP** | `C:\php\php-cgi.exe` | PHP 8.3 NTS + IIS FastCGI |

## Переопределение в скриптах (Linux)

Все `deploy/install-*.sh` и `deploy/setup-cron.sh` читают окружение:

```bash
# стандарт (скрипты без переменных)
NG_WEB_ROOT=/var/www/nativegallery
NG_DOMAIN=example.com

# production на смонтированном диске
sudo NG_WEB_ROOT=/mnt/win/nativegallery \
     NG_DOMAIN=cttc.fetbuk.ru \
     NG_DB_PASS='***' \
     bash deploy/install-ubuntu-24.04.sh

# только cron / обновление
sudo NG_WEB_ROOT=/mnt/win/nativegallery bash deploy/setup-cron.sh
sudo -u www-data php /mnt/win/nativegallery/app/Controllers/Exec/Tasks/ExecContests.php
```

## Подкаталоги (относительно корня проекта)

Создаются установкой или вручную:

```
{NG_WEB_ROOT}/
├── ngallery.yaml          # конфиг (chmod 640)
├── index.php              # front controller
├── uploads/               # фото/видео (server storage)
├── cdn/
│   ├── temp/
│   ├── previews/
│   ├── image/
│   └── video/
├── logs/                  # Tracy, cron.log
├── storage/
│   ├── locks/
│   ├── auth-settings.json # overlay OpenVK (из админки)
│   ├── server-settings.json
│   └── cron-tasks.json
└── sqlcore/               # миграции (не отдавать веб-сервером)
```

## Обновление с git (любой путь)

```bash
cd /mnt/win/nativegallery          # ваш NG_WEB_ROOT
git pull
composer install --no-dev
# миграции при необходимости
sudo NG_WEB_ROOT=/mnt/win/nativegallery bash deploy/setup-cron.sh
```

См. [deployment.md](deployment.md), [manual-install.md](manual-install.md).