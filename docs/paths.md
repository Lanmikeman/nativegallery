# Пути и каталоги по умолчанию

Скрипты и документация используют **стандартные пути**, если не задана переменная `NG_WEB_ROOT` (или аналог в Windows).

**Важно:** каталог вроде `/mnt/win/nativegallery` в примерах ниже — **только иллюстрация** (смонтированный диск, нестандартная разметка). Вы можете разместить проект **в любом** каталоге: `/var/www/...`, `/srv/gallery`, `/home/user/sites/...` — главное указать его в `NG_WEB_ROOT` и в `root` / `DocumentRoot` веб-сервера.

## Сводная таблица

| Назначение | По умолчанию (скрипты) | Пример своего пути* | Docker | Pterodactyl |
|------------|------------------------|---------------------|--------|-------------|
| **Корень проекта** | `/var/www/nativegallery` | `/mnt/win/nativegallery` | `/var/www/html` | `/home/container` |
| **Домен** | `example.com` | `gallery.example.com` | `localhost` | IP / домен из панели |
| **Конфиг** | `{корень}/ngallery.yaml` | то же | то же | то же |
| **Загрузки** | `{корень}/uploads/` | то же | volume `ng_uploads` | volume сервера |
| **CDN / превью** | `{корень}/cdn/` | то же | volume `ng_cdn` | то же |
| **Overlay / locks** | `{корень}/storage/` | то же | volume `ng_storage` | то же |
| **Логи** | `{корень}/logs/` | то же | volume `ng_logs` | то же |
| **SQL-миграции** | `{корень}/sqlcore/` | не менять | в образе | после `git clone` |
| **Cron-задачи** | `/etc/cron.d/nativegallery` | то же | в контейнере | cron в образе |
| **Nginx site** | `/etc/nginx/sites-available/nativegallery` | `root` → **ваш** `NG_WEB_ROOT` | `docker/nginx.conf` | — |
| **Apache vhost** | `/etc/apache2/sites-available/nativegallery.conf` | `DocumentRoot` → **ваш** путь | — | — |
| **PHP-FPM socket (Debian)** | `/run/php/php8.3-fpm.sock` | — | то же | то же |
| **PHP-FPM socket (RHEL)** | `/run/php-fpm/www.sock` | — | — | — |
| **Пользователь PHP/cron (Linux)** | `www-data` | то же | `www-data` | `www-data` |
| **Пользователь PHP (RHEL)** | `apache` | — | — | — |

\*Пример `/mnt/win/nativegallery` — один из вариантов, когда сайт лежит не в `/var/www`, а на отдельном смонтированном томе. **Не обязательный** путь.

## Windows (IIS / Apache)

| Назначение | По умолчанию | Примечание |
|------------|--------------|------------|
| **Корень проекта** | `C:\inetpub\nativegallery` | **любой** каталог с правами IIS |
| **Домен** | `localhost` | привязка сайта в IIS |
| **Конфиг IIS** | `{корень}\web.config` | в репозитории |
| **Права на запись** | `IIS_IUSRS`, `IUSR` | `uploads`, `cdn`, `logs`, `storage` |
| **Планировщик (cron)** | Task Scheduler | `deploy/windows/setup-task-scheduler.ps1` |

## Переопределение в скриптах (Linux)

Все `deploy/install-*.sh` и `deploy/setup-cron.sh` читают окружение:

```bash
# стандарт (если переменные не заданы)
NG_WEB_ROOT=/var/www/nativegallery
NG_DOMAIN=example.com

# свой каталог и домен (подставьте свои значения)
sudo NG_WEB_ROOT=/mnt/win/nativegallery \
     NG_DOMAIN=gallery.example.com \
     NG_DB_PASS='***' \
     bash deploy/install-ubuntu-24.04.sh

# cron / обновление — тот же NG_WEB_ROOT, что и у сайта
sudo NG_WEB_ROOT=/путь/к/вашему/проекту bash deploy/setup-cron.sh
sudo -u www-data php /путь/к/вашему/проекту/app/Controllers/Exec/Tasks/ExecContests.php
```

## Подкаталоги (относительно корня проекта)

Создаются установкой или вручную:

```
{NG_WEB_ROOT}/
├── ngallery.yaml
├── index.php
├── uploads/
├── cdn/
├── logs/
├── storage/
└── sqlcore/
```

## Обновление с git (любой путь)

```bash
cd /путь/к/вашему/проекту    # ваш NG_WEB_ROOT
git pull
composer install --no-dev
sudo NG_WEB_ROOT=/путь/к/вашему/проекту bash deploy/setup-cron.sh
```

См. [deployment.md](deployment.md), [manual-install.md](manual-install.md).