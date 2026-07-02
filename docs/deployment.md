# Развёртывание и обслуживание

## Обновление между версиями форка

| С версии | Действия |
|----------|----------|
| 1.4 → 1.6 | `sql_0008.sql`, настройка `openvk`, `deploy/setup-cron.sh`, права на `storage/` |
| 1.3 → 1.4 | `sql_0005.sql` … `sql_0007.sql`, `timezone`, cron |
| Чистая установка | `deploy/install-ubuntu-24.04.sh` (все миграции автоматически) |

Подробные заметки: [releases/1.6.md](releases/1.6.md), [releases/1.4.md](releases/1.4.md).

## Обновление с GitHub

```bash
cd /var/www/nativegallery   # или /mnt/win/nativegallery
git pull
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

## Каталоги и права

PHP-FPM (обычно `www-data`) должен иметь запись в:

```bash
mkdir -p uploads cdn/temp cdn/previews cdn/image cdn/video logs storage/locks
chown -R www-data:www-data uploads cdn logs storage
chmod -R 775 uploads cdn logs storage
```

| Каталог | Назначение |
|---------|------------|
| `uploads/` | Загруженные фото и видео (`storage.type: server`) |
| `cdn/` | Превью, временные файлы обработки |
| `storage/locks/` | Блокировки при генерации превью |
| `storage/auth-settings.json` | Переключатели регистрации и OpenVK из админки |
| `logs/` | Логи Tracy (при `debug: true`) |

## Cron фотоконкурсов

```bash
sudo NG_WEB_ROOT=/var/www/nativegallery bash deploy/setup-cron.sh
sudo -u www-data php app/Controllers/Exec/Tasks/ExecContests.php
```

## Nginx и PHP-FPM

```bash
sudo nginx -t
sudo systemctl reload nginx php8.3-fpm
```

Логи: `/var/log/nginx/nativegallery.error.log`, каталог из `logslocation` в yaml.

## Проверка версии

В подвале сайта отображается короткий hash коммита из `.git/refs/heads/main`. После `git pull` обновите страницу — hash должен совпадать с `git rev-parse --short HEAD`.