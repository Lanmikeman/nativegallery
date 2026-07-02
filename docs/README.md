# Документация NativeGallery

Документация для форка [Lanmikeman/nativegallery](https://github.com/Lanmikeman/nativegallery) — ответвление от [claradex/nativegallery](https://github.com/claradex/nativegallery), ориентированное на развёртывание Ubuntu 24.04 + Nginx + PHP 8.3 + MariaDB и функциональный паритет с [transphoto.org](https://transphoto.org).

**Текущая версия форка:** 1.5 (2026-07-02)

## Разделы

| Документ | Описание |
|----------|----------|
| [configuration.md](configuration.md) | Параметры `ngallery.yaml` — БД, хранилище, регистрация, фото, конкурсы, OpenVK |
| [deployment.md](deployment.md) | Обновление установки, миграции, права на каталоги, cron |
| [routes.md](routes.md) | Публичные URL, авторизация OpenVK, параметры `/update`, поиск, API админки |
| [releases/1.5.md](releases/1.5.md) | Заметки к релизу 1.5 — OpenVK, редактирование новостей, админка |
| [releases/1.4.md](releases/1.4.md) | Заметки к релизу 1.4 — обновления, поиск, публичные разделы |

## Быстрые ссылки

- Установка: [README.md](../README.md#установка)
- История изменений: [CHANGELOG.md](../CHANGELOG.md)
- Пример конфигурации: [ngallery-example.yaml](../ngallery-example.yaml)
- Автоустановка: [deploy/install-ubuntu-24.04.sh](../deploy/install-ubuntu-24.04.sh)

## История версий форка

| Версия | Дата | Ключевые изменения |
|--------|------|-------------------|
| 1.5 | 2026-07-02 | OpenVK (openvk.org + vepurovk.xyz), редактирование новостей, админка |
| 1.4 | 2026-07-02 | `/update`, поиск, `/news`, `/links`, фотоконкурсы, deploy-скрипты |
| 1.3 | 2025-05-26 | Базовый upstream claradex/nativegallery |