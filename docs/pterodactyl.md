# Pterodactyl Panel

Запуск NativeGallery как **сервера в Pterodactyl** (egg PTDL_v2).

## Что входит

| Файл | Описание |
|------|----------|
| [deploy/pterodactyl/egg-nativegallery.json](../deploy/pterodactyl/egg-nativegallery.json) | Egg для импорта в панель |
| [deploy/pterodactyl/install.sh](../deploy/pterodactyl/install.sh) | Скрипт установки (копия из egg) |
| Docker-образ | `ghcr.io/lanmikeman/nativegallery:latest` (или свой) |

Образ содержит: **nginx**, **PHP 8.3-FPM**, **supervisor**, **cron**. Код приложения устанавливается egg'ом в `/home/container`.

## Подготовка образа на ноде

На каждой ноде Pterodactyl, где будут серверы NativeGallery:

```bash
git clone https://github.com/Lanmikeman/nativegallery.git
cd nativegallery
docker build -t ghcr.io/lanmikeman/nativegallery:latest .
```

Либо укажите в nest свой тег: `nativegallery:latest`.

В **Admin → Nests → Import Egg** загрузите `deploy/pterodactyl/egg-nativegallery.json`.

При необходимости измените в egg поле `docker_images` на ваш тег образа.

## База данных

Egg **не** поднимает MySQL внутри игрового сервера. Варианты:

1. **Отдельный сервер** в Pterodactyl с MySQL/MariaDB egg (или общий DB-хост).
2. **Внешняя MariaDB** на хосте ноды (`172.17.0.1` / IP docker0).
3. **Управляемая БД** (отдельный VPS).

Создайте базу и пользователя вручную, затем укажите в переменных сервера:

- `NG_DB_HOST` — IP/hostname БД (не `127.0.0.1`, если БД снаружи контейнера)
- `NG_DB_NAME`, `NG_DB_USER`, `NG_DB_PASS`

## Создание сервера

1. **Nest:** NativeGallery  
2. **Allocation:** выделите порт (рекомендуется **8080** внутри контейнера; в панели можно назначить любой внешний порт)  
3. **Variables:** заполните DB и `NG_DOMAIN` (домен или IP для `alloweddomains`)  
4. **Install** — клонирует репозиторий, `composer install`  
5. **Start** — `start-pterodactyl.sh` → entrypoint → миграции → nginx на :8080  

### Переменные egg

| Переменная | Описание |
|------------|----------|
| `NG_WEB_ROOT` | `/home/container` (не менять) |
| `NG_DB_HOST` | Хост MariaDB |
| `NG_DB_NAME` | Имя БД |
| `NG_DB_USER` | Пользователь |
| `NG_DB_PASS` | Пароль |
| `NG_DOMAIN` | Домен/IP сайта |
| `NG_SITE_TITLE` | Название |
| `NG_ADMIN_EMAIL` | Email |
| `NG_TIMEZONE` | Часовой пояс |
| `NG_AUTO_MIGRATE` | `1` — SQL при старте |
| `NG_REPO` / `NG_BRANCH` | Репозиторий при установке |

## Reverse proxy

Pterodactyl отдаёт порт allocation. Для домена настройте на ноде или отдельном прокси:

```nginx
location / {
    proxy_pass http://IP_НОДЫ:ВЫДЕЛЕННЫЙ_ПОРТ;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    client_max_body_size 128M;
}
```

## После установки

1. `https://ваш-домен/register` — регистрация  
2. MySQL: `UPDATE users SET admin = 1 WHERE username = 'ник';`  
3. Миграции музыки уже в `NG_AUTO_MIGRATE=1` (`sql_0010`, `sql_0011`)

## Обновление

1. Остановите сервер  
2. В файловом менеджере или SFTP: `git pull` в `/home/container` **или** переустановите с `NG_BRANCH=release-1.7`  
3. `composer install --no-dev`  
4. Запустите сервер  

Для новых миграций удалите `storage/.schema_version` и перезапустите с `NG_AUTO_MIGRATE=1`.

## Ограничения

- Панель не заменяет production на Ubuntu+Nginx при очень высокой нагрузке.
- Загрузки хранятся в volume сервера Pterodactyl — следите за диском.
- SSL обычно терминируется на reverse proxy (Wings proxy или внешний nginx).