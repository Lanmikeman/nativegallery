# Документация NativeGallery

Документация для форка [Lanmikeman/nativegallery](https://github.com/Lanmikeman/nativegallery) — ответвление от [claradex/nativegallery](https://github.com/claradex/nativegallery), ориентированное на развёртывание Ubuntu 24.04 + Nginx + PHP 8.3 + MariaDB и функциональный паритет с [transphoto.org](https://transphoto.org).

**Текущая версия форка:** **1.8** (2026-07-05) · ветка `main`

## Разделы

| Документ | Описание |
|----------|----------|
| [configuration.md](configuration.md) | Параметры `ngallery.yaml`, overlay `storage/*.json`, роли, OpenVK |
| [deployment.md](deployment.md) | Обновление, миграции, права, cron, legacy-заглушки Nginx |
| [paths.md](paths.md) | Пути по умолчанию, свой `NG_WEB_ROOT`, Windows, Docker |
| [manual-install.md](manual-install.md) | Ручная установка: все зависимости и пакеты по стекам |
| [windows-iis.md](windows-iis.md) | Windows IIS, Apache, Caddy, OpenLiteSpeed |
| [deployment-alternatives.md](deployment-alternatives.md) | Apache, Rocky/AlmaLinux/CentOS — автоскрипты |
| [docker.md](docker.md) | Docker-образ и docker compose |
| [pterodactyl.md](pterodactyl.md) | Egg для Pterodactyl Panel |
| [routes.md](routes.md) | Публичные URL, ЛК, конкурсы, OpenVK, API админки |
| [releases/1.8.md](releases/1.8.md) | **Актуально:** откат музыки и SPA, комментарии, очистка БД |
| [releases/1.7.md](releases/1.7.md) | Исторический релиз 1.7 (SPA, музыка — убраны в 1.8) |
| [releases/1.6.md](releases/1.6.md) | OpenVK, конкурсы, владелец сервера |
| [releases/1.4.md](releases/1.4.md) | Обновления, поиск, публичные разделы |

## Быстрые ссылки

- Установка: [README.md](../README.md#установка)
- История изменений: [CHANGELOG.md](../CHANGELOG.md)
- Пример конфигурации: [ngallery-example.yaml](../ngallery-example.yaml)
- Очистка БД после 1.7: [drop_legacy_tables.sql](../sqlcore/drop_legacy_tables.sql)
- Автоустановка (production): [deploy/install-ubuntu-24.04.sh](../deploy/install-ubuntu-24.04.sh)
- Debian 12/13: [deploy/install-debian-12.sh](../deploy/install-debian-12.sh), [install-debian-12-apache.sh](../deploy/install-debian-12-apache.sh)
- Ручная установка: [manual-install.md](manual-install.md)
- Альтернативы: [deployment-alternatives.md](deployment-alternatives.md)
- Docker: [docker-compose.yml](../docker-compose.yml) · Pterodactyl: [egg-nativegallery.json](../deploy/pterodactyl/egg-nativegallery.json)
- Nginx: [deploy/nginx/nativegallery.conf](../deploy/nginx/nativegallery.conf)
- Apache: [deploy/apache/nativegallery.conf](../deploy/apache/nativegallery.conf)
- Cron конкурсов: [deploy/setup-cron.sh](../deploy/setup-cron.sh)

## История версий форка

| Версия | Дата | Ключевые изменения |
|--------|------|-------------------|
| **1.8** | 2026-07-05 | Удалена музыка (`/music`, `audio_*`); SPA отключена; комментарии по схеме claradex; `drop_legacy_tables.sql` |
| 1.7 | 2026-07-02 | SPA, музыка (позже убрана), перелистывание фото, кэш `ng_asset()`, админ-панель |
| 1.6 | 2026-07-02 | OpenVK, фотоконкурсы (rating, sendpretend, pk.php), роль владельца (`admin=4`), ЛК (ticket/konkurs), `/help/` |
| 1.4 | 2026-07-02 | `/update`, поиск, `/news`, `/links`, фотоконкурсы (cron), deploy-скрипты, редактирование фото |
| 1.3 | 2025-05-26 | Базовый upstream claradex/nativegallery |

## Что нового в 1.8 (кратко)

- **Музыка удалена** — весь раздел, API, админка, миграции `sql_0010`/`sql_0011`; очистка: `drop_legacy_tables.sql`
- **SPA отключена** — полная перезагрузка страниц (как upstream)
- **Комментарии** — восстановлена отправка через inline jQuery в `Photo.php`
- **Миграции** — последний файл: `sql_0009.sql`

Подробнее: [releases/1.8.md](releases/1.8.md)