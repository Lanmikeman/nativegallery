# Ручная установка NativeGallery

Пошаговая установка **без автоскриптов**: все зависимости, пакеты и настройка веб-сервера для каждого поддерживаемого стека.

**Production форка:** Ubuntu 24.04 + Nginx. Корень проекта — **любой каталог** (`NG_WEB_ROOT`); в скриптах по умолчанию `/var/www/nativegallery`. В примерах иногда фигурирует `/mnt/win/nativegallery` — **только иллюстрация** смонтированного диска. См. [paths.md](paths.md).

См. также: [deployment.md](deployment.md), [deployment-alternatives.md](deployment-alternatives.md), [windows-iis.md](windows-iis.md), [docker.md](docker.md), [pterodactyl.md](pterodactyl.md).

---

## 1. Общие требования (все стеки)

### Программное обеспечение

| Компонент | Версия | Назначение |
|-----------|--------|------------|
| **PHP** | 8.3+ | Движок |
| **MySQL** или **MariaDB** | MySQL 8.0+ / MariaDB 10.5+ | База данных |
| **Composer** | 2.x | Зависимости PHP |
| **ffmpeg** | 4.x+ | Обработка видео |
| **Git** | 2.x | Клонирование, обновления |

### Расширения PHP (обязательные)

| Расширение | Пакет Debian/Ubuntu | Пакет RHEL (Remi) |
|------------|---------------------|-------------------|
| PDO MySQL | `php8.3-mysql` | `php-mysqlnd` |
| GD | `php8.3-gd` | `php-gd` |
| cURL | `php8.3-curl` | `php-curl` |
| mbstring | `php8.3-mbstring` | `php-mbstring` |
| XML | `php8.3-xml` | `php-xml` |
| ZIP | `php8.3-zip` | `php-zip` |
| EXIF | `php8.3-exif` | `php-exif` |
| intl | `php8.3-intl` | `php-intl` |
| bcmath | `php8.3-bcmath` | `php-bcmath` |
| OPcache | `php8.3-opcache` | `php-opcache` |

### Рекомендуемые лимиты PHP (`php.ini` / FPM)

```ini
upload_max_filesize = 128M
post_max_size = 128M
memory_limit = 512M
max_execution_time = 300
date.timezone = UTC
```

### Каталоги и права (после клона)

```bash
# По умолчанию в документации и скриптах:
cd /var/www/nativegallery

# Свой путь (пример — смонтированный диск, не обязателен):
# cd /mnt/win/nativegallery
# cd /srv/gallery

mkdir -p uploads cdn/temp cdn/previews cdn/image cdn/video logs storage/locks
chown -R www-data:www-data uploads cdn logs storage    # apache на Rocky
chmod -R 775 uploads cdn logs storage
```

### Код и зависимости

```bash
git clone https://github.com/Lanmikeman/nativegallery.git /var/www/nativegallery
cd /var/www/nativegallery
composer install --no-dev --optimize-autoloader
```

### База данных

```bash
mysql -u root -p <<'SQL'
CREATE DATABASE ngallery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ngallery'@'localhost' IDENTIFIED BY 'ВАШ_ПАРОЛЬ';
GRANT ALL PRIVILEGES ON ngallery.* TO 'ngallery'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### SQL-миграции (строго по порядку)

```bash
cd /var/www/nativegallery
for f in sqlcore/base.sql sqlcore/sql_0001.sql sqlcore/sql_0002.sql sqlcore/sql_0003.sql \
         sqlcore/sql_0004.sql sqlcore/sql_0005.sql sqlcore/sql_0006.sql sqlcore/sql_0007.sql \
         sqlcore/sql_0008.sql sqlcore/sql_0009.sql; do
  mysql -u ngallery -p ngallery < "$f"
  echo "OK: $f"
done
```

### Конфигурация

```bash
cp ngallery-example.yaml ngallery.yaml
# Заполните: db.host, db.name, db.login, db.password, encryptionkey, alloweddomains, timezone
chmod 640 ngallery.yaml
chown www-data:www-data ngallery.yaml
```

### Cron фотоконкурсов

```bash
sudo NG_WEB_ROOT=/var/www/nativegallery NG_WEB_USER=www-data bash deploy/setup-cron.sh
sudo -u www-data php app/Controllers/Exec/Tasks/ExecContests.php
```

### Первый администратор

1. Откройте `http://ВАШ_ДОМЕН/register`
2. В MySQL: `UPDATE users SET admin = 1 WHERE username = 'ваш_ник';`

---

## 2. Ubuntu 24.04 + Nginx (production)

### Пакеты

```bash
sudo apt update
sudo apt install -y \
  nginx mysql-server \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd php8.3-curl \
  php8.3-mbstring php8.3-xml php8.3-zip php8.3-exif php8.3-intl \
  php8.3-bcmath php8.3-opcache \
  composer ffmpeg unzip git curl
```

### PHP

```bash
sudo sed -i 's/^upload_max_filesize.*/upload_max_filesize = 128M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/^post_max_size.*/post_max_size = 128M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/^memory_limit.*/memory_limit = 512M/' /etc/php/8.3/fpm/php.ini
sudo systemctl reload php8.3-fpm
```

### Nginx

```bash
sudo cp deploy/nginx/nativegallery.conf /etc/nginx/sites-available/nativegallery
# Отредактируйте server_name и root
sudo ln -sf /etc/nginx/sites-available/nativegallery /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

Конфиг: front controller, PHP socket `unix:/run/php/php8.3-fpm.sock`, `client_max_body_size 128M`.

### SSL

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d example.com
```

**Автоскрипт:** `sudo bash deploy/install-ubuntu-24.04.sh`

---

## 3. Debian 12 / 13 + Nginx

В репозиториях Debian — PHP 8.2; для NativeGallery нужен **PHP 8.3** из [packages.sury.org](https://packages.sury.org/php/).

### Репозиторий Sury

```bash
sudo apt install -y lsb-release ca-certificates curl apt-transport-https gnupg2
sudo curl -fsSL https://packages.sury.org/php/apt.gpg -o /usr/share/keyrings/deb.sury.org-php.gpg
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
  | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update
```

### Пакеты

```bash
sudo apt install -y \
  nginx default-mysql-server mariadb-client \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd php8.3-curl \
  php8.3-mbstring php8.3-xml php8.3-zip php8.3-exif php8.3-intl \
  php8.3-bcmath php8.3-opcache \
  composer ffmpeg unzip git curl
```

Далее — общие шаги (миграции, `ngallery.yaml`) и Nginx как в §2.

**Автоскрипт:** `sudo bash deploy/install-debian-12.sh`

---

## 4. Ubuntu 24.04 + Apache

### Пакеты

```bash
sudo apt install -y \
  apache2 libapache2-mod-fcgid mysql-server \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd php8.3-curl \
  php8.3-mbstring php8.3-xml php8.3-zip php8.3-exif php8.3-intl \
  php8.3-bcmath php8.3-opcache \
  composer ffmpeg unzip git curl
```

### Apache

```bash
sudo cp deploy/apache/nativegallery.conf /etc/apache2/sites-available/nativegallery.conf
# Замените example.com и DocumentRoot
sudo a2dissite 000-default.conf
sudo a2ensite nativegallery.conf
sudo a2enmod rewrite proxy proxy_fcgi setenvif
sudo apachectl configtest
sudo systemctl reload apache2 php8.3-fpm
```

Корневой `.htaccess` уже содержит `mod_rewrite` → `index.php`. Vhost задаёт `AllowOverride All` и PHP-FPM через `SetHandler`.

**Автоскрипт:** `sudo bash deploy/install-ubuntu-apache.sh`

---

## 5. Debian 12 / 13 + Apache

Как §3 (Sury PHP 8.3) + §4 (Apache), пакеты:

```bash
sudo apt install -y apache2 libapache2-mod-fcgid default-mysql-server mariadb-client \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd php8.3-curl php8.3-mbstring \
  php8.3-xml php8.3-zip php8.3-exif php8.3-intl php8.3-bcmath php8.3-opcache \
  composer ffmpeg unzip git curl
```

**Автоскрипт:** `sudo bash deploy/install-debian-12-apache.sh`

---

## 6. Rocky / AlmaLinux / CentOS Stream 9 + Nginx

### Репозитории

```bash
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.3 -y
```

### Пакеты

```bash
sudo dnf install -y \
  nginx mariadb-server \
  php-cli php-fpm php-mysqlnd php-gd php-curl php-mbstring \
  php-xml php-zip php-exif php-intl php-bcmath php-opcache \
  composer ffmpeg unzip git curl policycoreutils-python-utils
```

### PHP-FPM

Пользователь: `apache`. Socket: `/run/php-fpm/www.sock`.

```bash
sudo sed -i 's/^user = .*/user = apache/' /etc/php-fpm.d/www.conf
sudo sed -i 's/^group = .*/group = apache/' /etc/php-fpm.d/www.conf
```

### Nginx

```bash
sudo cp deploy/nginx/nativegallery-rocky.conf /etc/nginx/conf.d/nativegallery.conf
sudo nginx -t && sudo systemctl enable --now nginx php-fpm mariadb
```

### SELinux

```bash
sudo setsebool -P httpd_can_network_connect 1
sudo chcon -R -t httpd_sys_rw_content_t /var/www/nativegallery/uploads \
  /var/www/nativegallery/cdn /var/www/nativegallery/logs /var/www/nativegallery/storage
```

### Права

```bash
sudo chown -R apache:apache uploads cdn logs storage
sudo NG_WEB_USER=apache NG_WEB_ROOT=/var/www/nativegallery bash deploy/setup-cron.sh
```

**Автоскрипт:** `sudo bash deploy/install-rocky-9.sh`

---

## 7. Rocky / AlmaLinux / CentOS Stream 9 + Apache (httpd)

Пакеты как в §6, вместо `nginx` — `httpd mod_proxy_fcgi`:

```bash
sudo dnf install -y httpd mod_proxy_fcgi mariadb-server php-cli php-fpm ...
sudo cp deploy/apache/nativegallery-rocky.conf /etc/httpd/conf.d/nativegallery.conf
sudo apachectl configtest && sudo systemctl enable --now httpd php-fpm mariadb
```

**Автоскрипт:** `sudo bash deploy/install-rocky-9-apache.sh`

---

## 8. Windows — IIS

| Параметр | По умолчанию |
|----------|--------------|
| Корень | `C:\inetpub\nativegallery` |
| Конфиг URL | `web.config` (в корне репозитория) |
| Cron | Task Scheduler — `deploy\windows\setup-task-scheduler.ps1` |

Полная инструкция: [windows-iis.md](windows-iis.md).

---

## 9. Caddy / OpenLiteSpeed / Apache (Windows)

| Сервер | Конфиг |
|--------|--------|
| **Caddy 2** | [deploy/caddy/Caddyfile](../deploy/caddy/Caddyfile) — `root` → ваш `NG_WEB_ROOT` |
| **OpenLiteSpeed** | [deploy/openlitespeed/vhost.conf](../deploy/openlitespeed/vhost.conf) |
| **Apache (Windows)** | `.htaccess` + `DocumentRoot` → каталог проекта |

---

## 10. Docker

Образ включает nginx, PHP 8.3-FPM, supervisor, cron. БД — отдельный контейнер MariaDB. Корень в контейнере: **`/var/www/html`**.

```bash
cp docker-compose.example.env .env   # опционально
docker compose up -d --build
# http://localhost:8080
```

Подробно: [docker.md](docker.md).

---

## 11. Pterodactyl Panel

Egg для панели: `deploy/pterodactyl/egg-nativegallery.json`. Требуется внешняя MySQL/MariaDB.

Подробно: [pterodactyl.md](pterodactyl.md).

---

## 12. Проверка установки

| Проверка | Команда / действие |
|----------|-------------------|
| PHP версия | `php -v` → 8.3.x |
| Расширения | `php -m \| grep -E 'pdo_mysql|gd|curl|mbstring|zip|exif'` |
| ffmpeg | `ffmpeg -version` |
| БД | `mysql -u ngallery -p -e "SHOW TABLES" ngallery` |
| Веб | Главная открывается, `/register` работает |
| Cron | `cat /etc/cron.d/nativegallery` |
| Версия кода | hash в подвале = `git rev-parse --short HEAD` |