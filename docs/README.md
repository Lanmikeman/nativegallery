# Документация NativeGallery

Документация для форка [Lanmikeman/nativegallery](https://github.com/Lanmikeman/nativegallery) — ответвление от [claradex/nativegallery](https://github.com/claradex/nativegallery), ориентированное на развёртывание Ubuntu 24.04 + Nginx + PHP 8.3 + MariaDB и функциональный паритет с [transphoto.org](https://transphoto.org).

**Текущая версия форка:** 1.7 (2026-07-02) · тег [`release-1.7`](https://github.com/Lanmikeman/nativegallery/releases/tag/release-1.7)

## Разделы

| Документ | Описание |
|----------|----------|
| [configuration.md](configuration.md) | Параметры `ngallery.yaml`, overlay `storage/*.json`, роли, OpenVK |
| [deployment.md](deployment.md) | Обновление, миграции, права, cron, legacy-заглушки Nginx |
| [deployment-alternatives.md](deployment-alternatives.md) | Apache, Rocky/AlmaLinux/CentOS — альтернативные скрипты установки |
| [routes.md](routes.md) | Публичные URL, ЛК, конкурсы, OpenVK, API админки |
| [releases/1.7.md](releases/1.7.md) | Заметки к релизу 1.7 — музыка, SPA, общие радиостанции |
| [releases/1.6.md](releases/1.6.md) | Заметки к релизу 1.6 — OpenVK, конкурсы, владелец сервера |
| [releases/1.4.md](releases/1.4.md) | Заметки к релизу 1.4 — обновления, поиск, публичные разделы |

## Быстрые ссылки

- Установка: [README.md](../README.md#установка)
- История изменений: [CHANGELOG.md](../CHANGELOG.md)
- Пример конфигурации: [ngallery-example.yaml](../ngallery-example.yaml)
- Автоустановка (production): [deploy/install-ubuntu-24.04.sh](../deploy/install-ubuntu-24.04.sh)
- Альтернативы: [deployment-alternatives.md](deployment-alternatives.md) — Apache, Rocky 9
- Nginx: [deploy/nginx/nativegallery.conf](../deploy/nginx/nativegallery.conf)
- Apache: [deploy/apache/nativegallery.conf](../deploy/apache/nativegallery.conf)
- Cron конкурсов: [deploy/setup-cron.sh](../deploy/setup-cron.sh)

## История версий форка

| Версия | Дата | Ключевые изменения |
|--------|------|-------------------|
| **1.7** | 2026-07-02 | Музыка (`/music`), мини-плеер, SPA-навигация, общие радиостанции (`RadioStations`), ICY-метаданные, `sql_0010`/`sql_0011` |
| 1.6 | 2026-07-02 | OpenVK, фотоконкурсы (rating, sendpretend, pk.php), роль владельца (`admin=4`), ЛК (ticket/konkurs), `/help/`, overlay `server-settings.json` |
| 1.4 | 2026-07-02 | `/update`, поиск, `/news`, `/links`, фотоконкурсы (cron), deploy-скрипты, редактирование фото |
| 1.3 | 2025-05-26 | Базовый upstream claradex/nativegallery |

## Что нового в 1.7 (кратко)

- **Музыка** — `/music`, плеер в шапке, личные треки/потоки/плейлисты, M3U
- **Радио сайта** — `/admin?type=RadioStations`, станции для всех пользователей
- **SPA** — переходы по меню без перезагрузки; стрелки фото через `/api/photo/move`
- **Миграции** — `sql_0010.sql` (музыка), `sql_0011.sql` (общие станции)