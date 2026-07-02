# Документация NativeGallery

Документация для форка [Lanmikeman/nativegallery](https://github.com/Lanmikeman/nativegallery) — ответвление от [claradex/nativegallery](https://github.com/claradex/nativegallery), ориентированное на развёртывание Ubuntu 24.04 + Nginx + PHP 8.3 + MariaDB и функциональный паритет с [transphoto.org](https://transphoto.org).

**Текущая версия форка:** 1.6 (2026-07-02) · тег [`release-1.6`](https://github.com/Lanmikeman/nativegallery/releases/tag/release-1.6)

## Разделы

| Документ | Описание |
|----------|----------|
| [configuration.md](configuration.md) | Параметры `ngallery.yaml`, overlay `storage/*.json`, роли, OpenVK |
| [deployment.md](deployment.md) | Обновление, миграции, права, cron, legacy-заглушки Nginx |
| [routes.md](routes.md) | Публичные URL, ЛК, конкурсы, OpenVK, API админки |
| [releases/1.6.md](releases/1.6.md) | Заметки к релизу 1.6 — актуальное состояние main |
| [releases/1.4.md](releases/1.4.md) | Заметки к релизу 1.4 — обновления, поиск, публичные разделы |

## Быстрые ссылки

- Установка: [README.md](../README.md#установка)
- История изменений: [CHANGELOG.md](../CHANGELOG.md)
- Пример конфигурации: [ngallery-example.yaml](../ngallery-example.yaml)
- Автоустановка: [deploy/install-ubuntu-24.04.sh](../deploy/install-ubuntu-24.04.sh)
- Nginx: [deploy/nginx/nativegallery.conf](../deploy/nginx/nativegallery.conf)
- Cron конкурсов: [deploy/setup-cron.sh](../deploy/setup-cron.sh)

## История версий форка

| Версия | Дата | Ключевые изменения |
|--------|------|-------------------|
| **1.6** | 2026-07-02 | OpenVK, фотоконкурсы (rating, sendpretend, pk.php), роль владельца (`admin=4`), ЛК (ticket/konkurs), `/help/`, overlay `server-settings.json`, редактирование новостей |
| 1.4 | 2026-07-02 | `/update`, поиск, `/news`, `/links`, фотоконкурсы (cron), deploy-скрипты, редактирование фото |
| 1.3 | 2025-05-26 | Базовый upstream claradex/nativegallery |

## Что нового в 1.6 (кратко)

- **Владелец сервера** — `UPDATE users SET admin = 4`; раздел «Сервер» в админке
- **Помощь** — `/help/` в меню «Помощь»
- **Конкурс** — `/voting/rating`, `/voting/sendpretend`, отчёт `/pk.php?pid={id}&type=d`
- **ЛК** — `/lk/ticket.php`, `/lk/konkurs.php` (как на transphoto.org)
- **Конфиг** — `footerslogan` в подвале; правки через админку без SSH