# Развёртывание и обслуживание

Актуально для форка **v1.6**.

## Обновление между версиями форка

| С версии | Действия |
|----------|----------|
| 1.4 → 1.6 | `sql_0008.sql` (если не применяли), права на `storage/`, `deploy/setup-cron.sh`, назначение владельца (`admin = 4`) при необходимости |
| 1.3 → 1.4 | `sql_0005.sql` … `sql_0007.sql`, `timezone`, cron |
| Чистая установка | `deploy/install-ubuntu-24.04.sh` (все миграции автоматически) |

Подробные заметки: [releases/1.6.md](releases/1.6.md), [releases/1.4.md](releases/1.4.md).

## Обновление с GitHub

```bash
cd /var/www/nativegallery   # или /mnt/win/nativegallery
git fetch --tags
git pull origin main        # или git checkout release-1.6
composer install --no-dev --optimize-autoloader
```

## Миграции БД

Применяйте **по порядку**, только те файлы, которых ещё не было на сервере:

```bash
mysql -u USER -p DATABASE < sqlcore/sql_0005.sql   # chronology, site_links
mysql -u USER -p DATABASE < sqlcore/sql_0006.sql   # closure_meta у конкурсов
mysql -u USER -p DATABASE < sqlcore/sql_0007.sql   # photo_id в entities_requests
mysql -u USER -p DATABASE < sqlcore/sql_0008.sql   # edited_at/edited_by у news
```

Полный список при чистой установке: `base.sql` → `sql_0001.sql` … → `sql_0008.sql`.

Роль владельца сервера (`admin = 4`) **не требует** отдельной миграции.

## Каталоги и права

PHP-FPM (обычно `www-data`) должен иметь запись в:

```bash
mkdir -p uploads cdn/temp cdn/previews cdn/image cdn/video logs storage/locks
chown -R www-data:www-data uploads cdn logs storage
chmod -R 775 uploads cdn logs storage
```

| Каталог / файл | Назначение |
|----------------|------------|
| `uploads/` | Загруженные фото и видео (`storage.type: server`) |
| `cdn/` | Превью, временные файлы обработки |
| `storage/locks/` | Блокировки при генерации превью |
| `storage/auth-settings.json` | Настройки регистрации и OpenVK из админки |
| `storage/server-settings.json` | Overlay конфига и Debug (владелец сервера) |
| `storage/cron-tasks.json` | Состояние cron-задач (создаётся автоматически) |
| `logs/` | Логи Tracy (при `debug: true`) |

## Владелец сервера

После обновления до 1.6 назначьте владельца (один раз):

```sql
UPDATE users SET admin = 4 WHERE username = 'ваш_ник';
```

В админке появится раздел **Сервер** (`/admin?type=ServerSettings`):
- переключатель Debug (Tracy);
- форма редактирования параметров `root` (сохраняется в `storage/server-settings.json`).

Убедитесь, что `storage/` доступен для записи от `www-data`.

## Legacy-заглушки для Nginx

На Nginx запросы к физическим `.php`-файлам обрабатываются напрямую. В репозитории должны присутствовать заглушки, перенаправляющие на front controller (`index.php`):

| Файл | URL |
|------|-----|
| `pk.php` | `/pk.php?pid=…&type=d` |
| `help/index.php` | `/help/` |
| `lk/ticket.php` | `/lk/ticket.php` |
| `lk/konkurs.php` | `/lk/konkurs.php` |

При `git pull` эти файлы обновляются вместе с кодом. Если сайт отдаёт 404 на перечисленные URL — проверьте наличие файлов и права на каталоги.

## Cron фотоконкурсов

```bash
sudo NG_WEB_ROOT=/var/www/nativegallery bash deploy/setup-cron.sh
sudo -u www-data php app/Controllers/Exec/Tasks/ExecContests.php
```

Статус задач отображается в **Админка → Настройки** (`/admin?type=Settings`).

## Nginx и PHP-FPM

```bash
sudo nginx -t
sudo systemctl reload nginx php8.3-fpm
```

Готовый конфиг: `deploy/nginx/nativegallery.conf` (front controller, защита yaml/git, `client_max_body_size 128M`).

Логи: `/var/log/nginx/nativegallery.error.log`, каталог из `logslocation` в yaml.

## Проверка версии

В подвале сайта отображается короткий hash коммита из `.git/refs/heads/main`. После `git pull` обновите страницу — hash должен совпадать с `git rev-parse --short HEAD`.

Проверка тега релиза:

```bash
git describe --tags --always
# ожидается release-1.6 или коммит поверх него
```