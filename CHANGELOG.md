# Changelog

Все значимые изменения в этом форке документируются в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.1.0/).

## О форке

Форк [Lanmikeman/nativegallery](https://github.com/Lanmikeman/nativegallery) основан на upstream-проекте [claradex/nativegallery](https://github.com/claradex/nativegallery). Точка ответвления — коммит `7975610` (merge PR #17, 2026-03-06). Собственные изменения форка — 55 коммитов (2026-07-02), ориентированы на production-развёртывание Ubuntu 24.04 + Nginx + PHP 8.3 + MariaDB и функциональный паритет с [transphoto.org](https://transphoto.org).

## [Unreleased]

## [1.7] — 2026-07-02

Тег `release-1.7`. SPA-навигация, перелистывание фото, улучшения админ-панели.

### Добавлено

- **SPA-навигация** (`static/js/routing.js`) — переходы по меню без полной перезагрузки страницы.
- **API перелистывания фото** — `GET /api/photo/move` (стрелки на странице фото).
- **Кэш статики** — `ng_asset()` добавляет git-hash к URL CSS/JS.
- Админка: сгруппированное боковое меню; полный список фото с пагинацией и фильтрами; редактор страниц через API.

### Изменено

- Страница фото: inline-навигация `#prev` / `#next` и контекст `pid`/`vid`/`gid` для SPA и старого кэша.
- Админ-панель: компактные таблицы, тема сайта, улучшенная вёрстка разделов.

### Исправлено

- Перелистывание фото после SPA-переходов.
- Главная: кликабельные случайные фото, корректные URL `/photo/{id}`.
- SPA на `/photo/`: без дублирующего подвала, корректное размещение `#pmain`.
- Заголовок на `/page/{id}`, предупреждения PHP на странице комментариев и в профиле.
- Редактор страниц в админке (загрузка контента через API).

## [1.6] — 2026-07-02

Тег `release-1.6`. Заменяет промежуточный `release-1.5`.

### Добавлено

- **Вход через OpenVK** (openvk.org, vepurovk.xyz и произвольные инстансы):
  - Кнопки на `/login` и `/register`, привязка в `/lk/profile?type=OpenVK`
  - Overlay `storage/auth-settings.json` — CRUD инстансов OpenVK в админке (`/admin?type=AuthSettings`)
  - API: `POST /api/admin/settings/auth`, `POST /api/admin/settings/auth/providers`, `POST /api/admin/settings/auth/providers/{id}`, `POST /api/admin/settings/auth/providers/{id}/delete`
- **Редактирование новостей** в админке (`/admin?type=News`), миграция `sql_0008.sql` (поля `edited_at`, `edited_by`)
- **Роль владельца сервера** (`users.admin = 4`):
  - Раздел **Админка → Сервер** (`/admin?type=ServerSettings`) — только для владельца
  - Переключатель Debug (Tracy) через `POST /api/admin/settings/debug`
  - Редактирование параметров `root` через overlay `storage/server-settings.json` (`POST /api/admin/settings/server`)
  - Базовый `ngallery.yaml` на диске не изменяется
- **Редактор прав пользователей** (`/admin?type=UserEdit&user_id=`), API `POST /api/admin/users/{id}/edit`
- **Фотоконкурс** (полный цикл на production):
  - `/voting`, `/voting/waiting`, `/voting/results`, `/voting/rating` — голосование, претенденты, победители, рейтинг авторов
  - `/voting/sendpretend` — подача фото на конкурс (требуется вход)
  - `/pk.php?pid={id}&type=d` и `/voting/report` — отчёт по конкурсу для фото-победителя
  - Навигация `ContestNav`, API `POST /api/photo/contests/sendpretend`, `GET /api/photo/contests/rate`
- **Личный кабинет** (legacy URL как на transphoto.org):
  - `/lk/ticket.php`, `/lk/ticket` — мои заявки на изменение БД
  - `/lk/konkurs.php`, `/lk/konkurs` — участие в фотоконкурсе
- **Страница помощи** — `/help/` и `/help` (правила, конкурс, ссылки на ЛК)
- Страницы админки: `Galleries`, `EntityEdit`
- Legacy-заглушки для Nginx: `pk.php`, `help/index.php`, `lk/ticket.php`, `lk/konkurs.php`

### Изменено

- Подвал сайта читает `footerslogan` из конфига (вместо захардкоженного «Aloha, Hawaii!»)
- `GalleryConfig`: overlay `storage/server-settings.json` поверх `ngallery.yaml` (применяется в `index.php`)
- Детекция cron для фотоконкурсов (`TaskScheduler`, `setup-cron.sh`, `storage/cron-tasks.json`)
- Создание конкурса в админке: именованные колонки SQL, автодата закрытия, ошибки в модалке
- Ссылка на отчёт конкурса на странице фото — динамический `pid` вместо захардкоженного ID

### Исправлено

- ExecContests: детекция cron, обратная связь в менеджере задач
- Голосование на конкурсе: предупреждения PHP в `VotingSendPretend`, пустая вкладка при превью (`prev('img')`), безопасный JSON в API rate (`Rate.php`)
- `VotingResults` — корректное пустое состояние
- 404 на legacy URL: `ticket.php`, `konkurs.php`, `pk.php`, `/help/`
- OpenVK: проверка токена, обновление ссылок при смене домена инстанса
- Пустая галерея `/article/{id}`, предупреждения PHP 8.3 в админке и сессии
- Кнопка редактирования новости в админке

## [1.4] — 2026-07-02

### Добавлено

- Страница обновлений как на [transphoto.org/update.php](https://transphoto.org/update.php):
  - `/update?time=24` — новые фотографии за 24 часа с фильтрами по городам, авторам и видам сущностей
  - `/update?time=72` — за 72 часа
  - `/update` — архив обновлений по датам (сводка по городам, галереям и типам ТС)
  - `/update?date=YYYY-MM-DD` — фотографии, опубликованные в конкретный день
  - Пагинация (30 фото на страницу), карточки с превью, просмотрами, сущностью, маршрутом и автором
  - Сервис `App\Services\UpdateQuery`, компонент `views/components/UpdatePhotoList.php`
  - Алиас маршрута `/update.php`
- Необязательная привязка фото к сущностям:
  - Форма заявки на изменение БД (`/vehicle/dbedit`) — опциональное поле ID фотографии
  - Редактирование фото (`/lk/editimage`) — поиск и привязка/снятие модели сущности
  - Модерация заявок: превью привязанного фото; при принятии заявки фото связывается с новой записью
  - Миграция `sqlcore/sql_0007.sql` — поле `photo_id` в `entities_requests`
- Полноценный поиск (как на transphoto.org, только для авторизованных):
  - `/search` — поиск фотографий с фильтрами (автор, город, место, ТС, маршрут, галерея, даты, EXIF)
  - `/vsearch` — поиск транспортных средств в базе
  - `/csearch` — поиск комментариев
  - `/authors` — поиск авторов
  - Обратная совместимость: `/search.php`, `/vsearch.php`, `/csearch.php`, `/authors.php`
  - Сервисы `App\Services\Search\{PhotoSearch, VehicleSearch, CommentSearch, AuthorSearch}`
- Публичные страницы:
  - `/news` — новости и хронология (события транспорта, фильтры, пагинация)
  - `/news2` — новости сайта (записи из таблицы `news`)
  - `/misc` — список открытых фотогалерей
  - `/links` — внешние ссылки
- Обратная совместимость со старыми URL: `/news.php`, `/news2.php`, `/links.php`
- Админ-разделы:
  - `/admin?type=Chronology` — управление хронологией
  - `/admin?type=Links` — управление ссылками
- Таблицы БД `chronology` и `site_links` (миграция `sqlcore/sql_0005.sql`)
- Сервис `App\Services\ChronologyQuery` для фильтрации и пагинации хронологии
- Развёртывание под Ubuntu 24.04 + Nginx + PHP 8.3 + MySQL 8:
  - `deploy/nginx/nativegallery.conf` — конфигурация Nginx
  - `deploy/install-ubuntu-24.04.sh` — скрипт автоматической установки (включая `sql_0006` и `sql_0007`)
  - `deploy/setup-cron.sh` — установка cron для автоматизации фотоконкурсов
- Документация в каталоге `docs/`:
  - `configuration.md` — параметры `ngallery.yaml`
  - `deployment.md` — обновление, миграции, права
  - `routes.md` — публичные URL и параметры страницы обновлений

### Изменено

- Меню «Обновления»: «Новые фотографии» → `/update?time=24`, «Архив по датам» → `/update`
- Ссылка «Недавно добавленные фотографии» на главной ведёт на `/update?time=24`
- Загрузка фото: при неверном ID сущности публикация не прерывается — фото публикуется без привязки
- Ссылки в меню «Дополнительно» и «Поиск» обновлены на рабочие маршруты
- Поиск фотографий по `?id=` расширен до полноценной формы с критериями
- В `ngallery-example.yaml` тип хранилища по умолчанию изменён на `server` (локальные загрузки вместо S3)
- Путь к `ffmpeg` при загрузке видео на Linux использует системный бинарник
- Tracy при `debug: true` пишет логи в каталог из `logslocation`
- Скрипт установки создаёт `storage/locks/` для блокировок обработки изображений

### Исправлено

- Ошибка 500 на `/update` и `/update?time=24`:
  - SQL `GROUP BY` для MariaDB `ONLY_FULL_GROUP_BY`
  - Безопасная обработка пустого поля `place` в фильтрах городов (PHP 8.3)
  - `ParseError` в `views/pages/Update.php` (лишний `<?php` перед `else`)
- SQL-ошибка `Column count doesn't match value count` на `/vehicle/dbedit` при отправке заявки на изменение БД
- Форма `/vehicle/dbedit`: сохранение GET-параметров при POST, корректный поиск по ID/названию, безопасная проверка лимита 24 часа
- Ошибка 500 на `/lk/editimage` (несуществующий метод контроллера и пустая страница редактирования)
- Редактирование загруженных фото: форма и API `/api/photo/edit` (метаданные, замена файла, повторная отправка на модерацию)
- Часовой пояс сайта (`root.timezone` в `ngallery.yaml`, по умолчанию `Europe/Moscow`) для дат конкурсов и админ-форм
- Принудительное завершение и отмена конкурсов в админке с указанием причины
- Миграция `sqlcore/sql_0006.sql` — поле `closure_meta` у конкурсов
- Динамические списки годов (`Date::yearSelectOptions`) вместо захардкоженного списка до 2024
- `Date::zmdate()` корректно показывает будущие даты конкурсов
- CLI-задачи (`ExecContests.php`): инициализация БД из `ngallery.yaml` через `cli-bootstrap.php`
- Фотоконкурсы: статус ожидания голосования, страница `/voting`, автопереход этапов
- Ошибка `contest_id cannot be null` при голосовании «Красиво, на конкурс!» на странице фото
- Пустые/битые страницы по ссылкам «Новости сайта» и «Новости и хронология» из меню
- Предупреждения PHP в `Navbar.php` (`$nonrw`, `$nonr`, необязательный `navbar`)
- Предупреждения PHP в `Router.php` на корневом маршруте (неверный `Content-Length`)
- Предупреждения PHP на странице фото (`Photo`, `Comment` — отсутствующие ключи JSON)

---

## [1.3] — 2025-05-26

Базовая версия upstream-проекта [claradex/nativegallery](https://github.com/claradex/nativegallery) на момент форка.

Основной функционал: авторизация, профили, публикация фото, GeoDB, галереи, комментарии, рейтинги, модерация, фотоконкурсы (beta), админ-панель.