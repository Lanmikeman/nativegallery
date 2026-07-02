# Альтернативные стеки развёртывания

Актуально для форка **v1.7**.

**Production** этого форка — **Ubuntu 24.04 + Nginx + PHP 8.3 + MySQL/MariaDB** (`deploy/install-ubuntu-24.04.sh`). Ниже — поддерживаемые альтернативы для dev, тестовых и сторонних серверов.

## Сводная таблица

| Стек | ОС | Веб-сервер | Скрипт | Конфиг |
|------|-----|------------|--------|--------|
| **Production (рекомендуется)** | Ubuntu 24.04 | Nginx | [install-ubuntu-24.04.sh](../deploy/install-ubuntu-24.04.sh) | [nginx/nativegallery.conf](../deploy/nginx/nativegallery.conf) |
| Debian + Nginx | Debian 12/13 (bookworm/trixie) | Nginx | [install-debian-12.sh](../deploy/install-debian-12.sh) | [nginx/nativegallery.conf](../deploy/nginx/nativegallery.conf) |
| Ubuntu + Apache | Ubuntu 24.04 | Apache 2.4 | [install-ubuntu-apache.sh](../deploy/install-ubuntu-apache.sh) | [apache/nativegallery.conf](../deploy/apache/nativegallery.conf) |
| Debian + Apache | Debian 12/13 | Apache 2.4 | [install-debian-12-apache.sh](../deploy/install-debian-12-apache.sh) | [apache/nativegallery.conf](../deploy/apache/nativegallery.conf) |
| RHEL-семейство + Nginx | Rocky 9, AlmaLinux 9, CentOS Stream 9 | Nginx | [install-rocky-9.sh](../deploy/install-rocky-9.sh) | [nginx/nativegallery-rocky.conf](../deploy/nginx/nativegallery-rocky.conf) |
| RHEL-семейство + Apache | Rocky 9, AlmaLinux 9, CentOS Stream 9 | httpd | [install-rocky-9-apache.sh](../deploy/install-rocky-9-apache.sh) | [apache/nativegallery-rocky.conf](../deploy/apache/nativegallery-rocky.conf) |
| Docker Compose | Любой хост с Docker | nginx в контейнере | [docker-compose.yml](../docker-compose.yml) | [docker/nginx.conf](../docker/nginx.conf) |
| Pterodactyl | Нода с Docker | nginx в egg-образе | [egg-nativegallery.json](../deploy/pterodactyl/egg-nativegallery.json) | переменные панели |

Общая логика (клон, Composer, миграции, `ngallery.yaml`, cron): [deploy/lib/install-common.sh](../deploy/lib/install-common.sh).  
PHP 8.3 на Debian: репозиторий [packages.sury.org](https://packages.sury.org/php/) — [deploy/lib/debian-php83.sh](../deploy/lib/debian-php83.sh).

## Переменные окружения (все скрипты)

| Переменная | По умолчанию | Описание |
|------------|--------------|----------|
| `NG_DOMAIN` | `example.com` | Домен сайта |
| `NG_DB_NAME` | `ngallery` | Имя БД |
| `NG_DB_USER` | `ngallery` | Пользователь БД |
| `NG_DB_PASS` | случайный | Пароль БД |
| `NG_WEB_ROOT` | `/var/www/nativegallery` | Корень проекта (production: `/mnt/win/nativegallery`) |
| `NG_WEB_USER` | `www-data` (Debian) / `apache` (RHEL) | Пользователь PHP-FPM и cron |

Пример:

```bash
sudo NG_DOMAIN=gallery.example.com NG_DB_PASS='secret' bash deploy/install-ubuntu-apache.sh
```

---

## Debian 12 / 13 (bookworm / trixie)

На Debian в стандартных репозиториях PHP 8.2; для NativeGallery нужен **PHP 8.3** — скрипты подключают репозиторий **Sury**. БД: **MariaDB** (`default-mysql-server`).

### Nginx

```bash
git clone https://github.com/Lanmikeman/nativegallery.git /tmp/nativegallery
cd /tmp/nativegallery
sudo NG_DOMAIN=example.com bash deploy/install-debian-12.sh
```

### Apache

```bash
sudo NG_DOMAIN=example.com bash deploy/install-debian-12-apache.sh
```

Конфиги веб-сервера те же, что на Ubuntu: `deploy/nginx/nativegallery.conf`, `deploy/apache/nativegallery.conf`. PHP-FPM socket: `/run/php/php8.3-fpm.sock`. Пользователь: `www-data`.

### Ручная установка PHP 8.3 на Debian

```bash
sudo apt install -y lsb-release ca-certificates curl apt-transport-https
sudo curl -fsSL https://packages.sury.org/php/apt.gpg -o /usr/share/keyrings/deb.sury.org-php.gpg
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
  | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd php8.3-curl \
  php8.3-mbstring php8.3-xml php8.3-zip php8.3-exif php8.3-intl php8.3-bcmath
```

Далее — как в [README.md](../README.md#ручная-установка) (миграции, `ngallery.yaml`, nginx/apache).

### SSL

```bash
sudo apt install certbot python3-certbot-nginx   # или python3-certbot-apache
sudo certbot --nginx -d example.com
```

---

## Ubuntu + Apache

Движок из коробки поддерживает Apache через `.htaccess` (`mod_rewrite`). Для production на Apache лучше использовать VirtualHost с `AllowOverride All` и PHP-FPM.

### Автоустановка

```bash
git clone https://github.com/Lanmikeman/nativegallery.git /tmp/nativegallery
cd /tmp/nativegallery
sudo NG_DOMAIN=example.com bash deploy/install-ubuntu-apache.sh
```

На **Debian** используйте `deploy/install-debian-12-apache.sh`.

Скрипт ставит `apache2`, `php8.3-fpm`, MySQL/MariaDB, включает `rewrite`, `proxy_fcgi`, разворачивает vhost из `deploy/apache/nativegallery.conf`.

### Ручная настройка Apache

```bash
sudo cp deploy/apache/nativegallery.conf /etc/apache2/sites-available/nativegallery.conf
# замените example.com и /var/www/nativegallery
sudo a2ensite nativegallery.conf
sudo a2dissite 000-default.conf
sudo a2enmod rewrite proxy proxy_fcgi setenvif
sudo apachectl configtest && sudo systemctl reload apache2 php8.3-fpm
```

Корневой `.htaccess` обрабатывает front controller (`index.php?q=…`). Vhost дополнительно закрывает `/vendor/`, `/app/`, `ngallery.yaml`.

**Legacy URL** (`pk.php`, `lk/ticket.php` и др.): на Apache физические `.php`-заглушки отдаются напрямую (в отличие от Nginx, где нужен `try_files` → `index.php`). Заглушки уже в репозитории.

### SSL

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d example.com
```

---

## Rocky Linux / AlmaLinux / CentOS Stream 9

На RHEL-семействе PHP 8.3 ставится из **Remi**. БД — **MariaDB**. Пользователь веб-сервера — **`apache`**.

### Nginx (альтернатива production-стеку)

```bash
sudo NG_DOMAIN=example.com bash deploy/install-rocky-9.sh
```

PHP-FPM socket: `/run/php-fpm/www.sock` (см. `deploy/nginx/nativegallery-rocky.conf`).

### Apache (httpd)

```bash
sudo NG_DOMAIN=example.com bash deploy/install-rocky-9-apache.sh
```

Конфиг: `deploy/apache/nativegallery-rocky.conf` → `/etc/httpd/conf.d/nativegallery.conf`.

### SELinux

Скрипты для Rocky включают:

```bash
setsebool -P httpd_can_network_connect 1
chcon -R -t httpd_sys_rw_content_t uploads cdn logs storage
```

Нужно для OpenVK, внешних радиопотоков и записи в `storage/`.

### Права и cron на RHEL

```bash
sudo chown -R apache:apache uploads cdn logs storage
sudo NG_WEB_ROOT=/var/www/nativegallery NG_WEB_USER=apache bash deploy/setup-cron.sh
sudo -u apache php app/Controllers/Exec/Tasks/ExecContests.php
```

### SSL

```bash
# Nginx
sudo dnf install certbot python3-certbot-nginx
sudo certbot --nginx -d example.com

# Apache
sudo dnf install certbot python3-certbot-apache
sudo certbot --apache -d example.com
```

---

## Пути (важно)

| Среда | `NG_WEB_ROOT` / корень по умолчанию |
|-------|-------------------------------------|
| Скрипты Linux | `/var/www/nativegallery` |
| **Production форка** | `/mnt/win/nativegallery` |
| Docker | `/var/www/html` |
| Pterodactyl | `/home/container` |
| Windows IIS | `C:\inetpub\nativegallery` |

Полная таблица: [paths.md](paths.md).

---

## Windows, Caddy, OpenLiteSpeed

| Платформа | Документация | Конфиг |
|-----------|--------------|--------|
| **Windows + IIS** | [windows-iis.md](windows-iis.md) | `web.config`, `deploy/windows/*.ps1` |
| **Apache на Windows** | [windows-iis.md](windows-iis.md) §4 | `.htaccess` |
| **Caddy 2** | [manual-install.md](manual-install.md) §9 | [deploy/caddy/Caddyfile](../deploy/caddy/Caddyfile) |
| **OpenLiteSpeed** | [manual-install.md](manual-install.md) §9 | [deploy/openlitespeed/vhost.conf](../deploy/openlitespeed/vhost.conf) |

---

## Прочие платформы

| Платформа | Поддержка |
|-----------|-----------|
| Shared-хостинг | Не поддерживается (см. README) |
| **Docker** | [docker.md](docker.md) |
| **Pterodactyl** | [pterodactyl.md](pterodactyl.md) |
| Ручная установка | [manual-install.md](manual-install.md) |
| Пути | [paths.md](paths.md) |

---

## Что одинаково на всех стеках

- Миграции: `sqlcore/base.sql` → `sql_0011.sql` (см. [deployment.md](deployment.md))
- Конфиг: `ngallery.yaml` + overlay в `storage/`
- Cron фотоконкурсов: `deploy/setup-cron.sh`
- Обновление: `git pull`, `composer install --no-dev`, новые SQL-файлы

После смены стека (Nginx ↔ Apache) достаточно переключить vhost и перезапустить PHP-FPM; код и БД не меняются.