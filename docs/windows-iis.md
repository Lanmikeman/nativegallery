# Windows — IIS и аналоги

Ручная установка NativeGallery на **Windows Server** / **Windows 10+** с **IIS**, а также кратко: Apache на Windows, Caddy, OpenLiteSpeed.

На Linux production часто используют **свой** `NG_WEB_ROOT` (в документации встречается пример `/mnt/win/nativegallery` — смонтированный диск, **не обязательный** путь). Windows-инструкция — для dev, тестов или IIS-хоста.

Пути по умолчанию: [paths.md](paths.md).

---

## Пути по умолчанию (Windows)

| Параметр | По умолчанию | Пример |
|----------|--------------|--------|
| Корень сайта | `C:\inetpub\nativegallery` | `D:\sites\nativegallery` (любой свой) |
| Домен | `localhost` | `gallery.local` |
| PHP CGI | `C:\php\php-cgi.exe` | из [windows.php.net](https://windows.php.net/download/) |
| MySQL | `127.0.0.1:3306` | MariaDB / MySQL 8 Windows |
| Конфиг | `C:\inetpub\nativegallery\ngallery.yaml` | — |
| URL rewrite | `{корень}\web.config` | уже в репозитории |

---

## 1. Зависимости (IIS)

### Обязательно

| Компонент | Версия | Установка |
|-----------|--------|-----------|
| **IIS** | 10+ | «Компоненты Windows» → IIS + CGI |
| **URL Rewrite** | 2.x | [IIS URL Rewrite](https://www.iis.net/downloads/microsoft/url-rewrite) |
| **PHP** | 8.3 NTS x64 | ZIP с [windows.php.net](https://windows.php.net/download/), FastCGI |
| **MySQL / MariaDB** | 8.0+ / 10.5+ | Установщик Windows |
| **Composer** | 2.x | [getcomposer.org](https://getcomposer.org/download/) |
| **Git** | 2.x | [git-scm.com](https://git-scm.com/download/win) |
| **ffmpeg** | 4.x+ | [gyan.dev/ffmpeg](https://www.gyan.dev/ffmpeg/builds/) → в `PATH` |

### Расширения PHP (`php.ini`)

Включите (раскомментируйте):

```ini
extension_dir = "ext"
extension=curl
extension=exif
extension=ffi
extension=fileinfo
extension=gd
extension=intl
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=zip

upload_max_filesize = 128M
post_max_size = 128M
memory_limit = 512M
max_execution_time = 300
date.timezone = UTC
```

В `php.ini` для IIS также:

```ini
cgi.fix_pathinfo = 0
```

---

## 2. Ручная установка (IIS)

### Шаг 1 — код

```powershell
# По умолчанию:
$WebRoot = "C:\inetpub\nativegallery"

# Или свой каталог:
# $WebRoot = "D:\sites\nativegallery"

git clone https://github.com/Lanmikeman/nativegallery.git $WebRoot
Set-Location $WebRoot
composer install --no-dev --optimize-autoloader
```

### Шаг 2 — каталоги и права

```powershell
$dirs = @("uploads", "cdn\temp", "cdn\previews", "cdn\image", "cdn\video", "logs", "storage\locks")
foreach ($d in $dirs) { New-Item -ItemType Directory -Force -Path (Join-Path $WebRoot $d) }

# Права на запись для IIS
icacls "$WebRoot\uploads" /grant "IIS_IUSRS:(OI)(CI)M" /T
icacls "$WebRoot\cdn" /grant "IIS_IUSRS:(OI)(CI)M" /T
icacls "$WebRoot\logs" /grant "IIS_IUSRS:(OI)(CI)M" /T
icacls "$WebRoot\storage" /grant "IIS_IUSRS:(OI)(CI)M" /T
```

### Шаг 3 — база данных

В MySQL Workbench или `mysql.exe`:

```sql
CREATE DATABASE ngallery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ngallery'@'localhost' IDENTIFIED BY 'ВАШ_ПАРОЛЬ';
GRANT ALL PRIVILEGES ON ngallery.* TO 'ngallery'@'localhost';
FLUSH PRIVILEGES;
```

Миграции (PowerShell):

```powershell
cd $WebRoot
$sqlFiles = @(
  "sqlcore\base.sql",
  "sqlcore\sql_0001.sql", "sqlcore\sql_0002.sql", "sqlcore\sql_0003.sql",
  "sqlcore\sql_0004.sql", "sqlcore\sql_0005.sql", "sqlcore\sql_0006.sql",
  "sqlcore\sql_0007.sql", "sqlcore\sql_0008.sql", "sqlcore\sql_0009.sql",
  "sqlcore\sql_0010.sql", "sqlcore\sql_0011.sql"
)
foreach ($f in $sqlFiles) {
  Get-Content $f -Raw | mysql -u ngallery -p ngallery
  Write-Host "OK: $f"
}
```

### Шаг 4 — ngallery.yaml

```powershell
Copy-Item ngallery-example.yaml ngallery.yaml
# Заполните db.host (127.0.0.1), db.name, db.login, db.password, encryptionkey, alloweddomains
```

### Шаг 5 — IIS: сайт и FastCGI

1. **IIS Manager** → Sites → Add Website  
   - **Site name:** `NativeGallery`  
   - **Physical path:** `C:\inetpub\nativegallery` (ваш `$WebRoot`)  
   - **Binding:** `http`, host `localhost` или ваш домен  

2. **Handler Mappings** → Add Module Mapping:  
   - Request path: `*.php`  
   - Module: `FastCgiModule`  
   - Executable: `C:\php\php-cgi.exe`  
   - Name: `PHP83`  

3. **FastCGI Settings** → `php-cgi.exe` → Environment variables (при необходимости):  
   - `PHP_FCGI_MAX_REQUESTS` = `10000`  

4. Файл **`web.config`** в корне сайта уже в репозитории (front controller + блокировка `/vendor/`, `ngallery.yaml`).

5. Перезапустите сайт: `iisreset` или Restart в IIS Manager.

### Шаг 6 — cron (фотоконкурсы)

На Windows — **Планировщик заданий**:

```powershell
# от имени администратора
.\deploy\windows\setup-task-scheduler.ps1 -WebRoot "C:\inetpub\nativegallery"
```

Или вручную: каждые 5 минут запускать:

```
C:\php\php.exe C:\inetpub\nativegallery\app\Controllers\Exec\Tasks\ExecContests.php
```

### Шаг 7 — первый админ

`http://localhost/register` → в MySQL:

```sql
UPDATE users SET admin = 1 WHERE username = 'ваш_ник';
```

---

## 3. Скрипт помощник (IIS)

```powershell
# Проверка зависимостей и подсказки (не заменяет полную ручную настройку FastCGI)
.\deploy\windows\setup-iis.ps1 -WebRoot "C:\inetpub\nativegallery" -SiteName "NativeGallery"
```

---

## 4. Apache на Windows

1. [Apache Lounge](https://www.apachelounge.com/download/) 2.4 x64  
2. PHP 8.3 как CGI или с mod_fcgid  
3. `DocumentRoot` → `C:\inetpub\nativegallery`  
4. Включите `mod_rewrite`; корневой **`.htaccess`** уже настроен  
5. Права и миграции — как в §2  

---

## 5. Caddy (Windows / Linux)

Конфиг: [deploy/caddy/Caddyfile](../deploy/caddy/Caddyfile)

```bash
# Linux: укажите root = ваш NG_WEB_ROOT
caddy run --config deploy/caddy/Caddyfile
```

На Windows: [Caddy Windows](https://caddyserver.com/docs/install#windows) — тот же `Caddyfile`, путь `root` в формате `C:\inetpub\nativegallery`.

---

## 6. OpenLiteSpeed

Шаблон vhost: [deploy/openlitespeed/vhost.conf](../deploy/openlitespeed/vhost.conf)  
`docRoot` → ваш каталог; `autoLoadHtaccess 1` подхватывает `.htaccess`.

---

## 7. Сравнение с Linux production

| | Linux production | Windows IIS |
|--|------------------|-------------|
| Корень | любой `NG_WEB_ROOT` (пример: `/mnt/win/nativegallery`) | `C:\inetpub\nativegallery` (или свой) |
| Веб-сервер | Nginx | IIS + `web.config` |
| PHP | php8.3-fpm socket | php-cgi.exe FastCGI |
| Cron | `/etc/cron.d/nativegallery` | Task Scheduler |
| Обновление | `git pull` в `NG_WEB_ROOT` | `git pull` в `$WebRoot` |

См. также: [manual-install.md](manual-install.md), [deployment-alternatives.md](deployment-alternatives.md).