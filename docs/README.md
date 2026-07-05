# Документация NativeGallery

Документация для форка [Lanmikeman/nativegallery](https://github.com/Lanmikeman/nativegallery) — ответвление от [claradex/nativegallery](https://github.com/claradex/nativegallery), ориентированное на развёртывание Ubuntu 24.04 + Nginx + PHP 8.3 + MariaDB и функциональный паритет с [transphoto.org](https://transphoto.org).

**Текущая версия форка:** 1.7 (2026-07-02) · тег [`release-1.7`](https://github.com/Lanmikeman/nativegallery/releases/tag/release-1.7)

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
| [releases/1.7.md](releases/1.7.md) | Заметки к релизу 1.7 — SPA-навигация, перелистывание фото |
| [releases/1.6.md](releases/1.6.md) | Заметки к релизу 1.6 — OpenVK, конкурсы, владелец сервера |
| [releases/1.4.md](releases/1.4.md) | Заметки к релизу 1.4 — обновления, поиск, публичные разделы |

## Быстрые ссылки

- Установка: [README.md](../README.md#установка)
- История изменений: [CHANGELOG.md](../CHANGELOG.md)
- Пример конфигурации: [ngallery-example.yaml](../ngallery-example.yaml)
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
| **1.7** | 2026-07-02 | SPA-навигация, перелистывание фото (`/api/photo/move`), кэш статики, улучшения админ-панели |
| 1.6 | 2026-07-02 | OpenVK, фотоконкурсы (rating, sendpretend, pk.php), роль владельца (`admin=4`), ЛК (ticket/konkurs), `/help/`, overlay `server-settings.json` |
| 1.4 | 2026-07-02 | `/update`, поиск, `/news`, `/links`, фотоконкурсы (cron), deploy-скрипты, редактирование фото |
| 1.3 | 2025-05-26 | Базовый upstream claradex/nativegallery |

## Что нового в 1.7 (кратко)

- **SPA** — переходы по меню без перезагрузки; стрелки фото через `/api/photo/move`
- **Кэш статики** — `ng_asset()` добавляет git-hash к URL CSS/JS
- **Админ-панель** — сгруппированное меню, пагинация фото, редактор страниц через API