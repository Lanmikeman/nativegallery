# Маршруты и URL

Актуально для форка **v1.8**.

> Маршруты `/music` и API `/api/audio/*` **удалены** в 1.8. См. [releases/1.8.md](releases/1.8.md).

## Публичные разделы

| URL | Описание |
|-----|----------|
| `/` | Главная |
| `/photo/{id}` | Страница фотографии |
| `/author/{id}` | Профиль автора |
| `/vehicle/{id}` | Карточка транспортного средства |
| `/article/{id}` | Тематическая фотогалерея |
| `/news` | Новости и хронология |
| `/news2` | Новости сайта (с пометкой об исправлениях) |
| `/misc` | Список тематических галерей |
| `/links` | Внешние ссылки |
| `/help/` | Помощь по сайту (правила, конкурс, ссылки) |
| `/help` | То же (маршрут роутера) |
| `/voting` | Фотоконкурс (голосование) |
| `/voting/waiting` | Претенденты |
| `/voting/results` | Победители |
| `/voting/rating` | Рейтинг авторов-победителей |
| `/pk.php?pid={id}&type=d` | Отчёт по конкурсу для фото-победителя |
| `/voting/report` | Алиас отчёта конкурса |
| `/comments` | Лента комментариев |
| `/feed` | Лента обновлений |
| `/login` | Вход (логин/пароль + OpenVK) |
| `/register` | Регистрация (+ OpenVK) |
| `/about` | О сервере |
| `/tour` | Обзор возможностей |
| `/rules`, `/rules/pub`, `/rules/photo`, `/rules/video` | Правила сайта |

## Авторизация OpenVK

Доступна при `openvk.enabled: true` в `ngallery.yaml`. На production-сервере форка подключены два инстанса: **openvk.org** и **vepurovk.xyz** (второй узел OVK).

| URL | Метод | Описание |
|-----|-------|----------|
| `/auth/openvk/start?provider=openvk_org` | GET | Старт OAuth на openvk.org |
| `/auth/openvk/start?provider=vepurovk` | GET | Старт OAuth на vepurovk.xyz |
| `/auth/callback` | GET | Callback после авторизации |
| `/api/auth/openvk` | POST | Обмен токена на локальную сессию |
| `/lk/profile?type=OpenVK` | GET | Привязка OpenVK к существующему аккаунту |

Параметр `provider` соответствует ключу в `openvk.providers` (`openvk_org`, `vepurovk`).

## Обновления (`/update`)

| URL | Режим |
|-----|-------|
| `/update?time=24` | Новые фото за 24 часа |
| `/update?time=72` | Новые фото за 72 часа |
| `/update` | Архив по датам публикации |
| `/update?date=2026-07-02` | Фото за конкретный день |

### Параметры фильтрации (режимы `time` и `date`)

| Параметр | Описание |
|----------|----------|
| `time` | Часы назад от текущего момента (`24`, `72`, …) |
| `date` | Дата публикации `YYYY-MM-DD` |
| `cid` | Фильтр по городу (ID из GeoDB); `0` — без города |
| `aid` | Фильтр по автору (ID пользователя) |
| `t` | Фильтр по виду сущности (ID из `entities`); `0` — не указан |
| `st` | Смещение для пагинации (по 30 фото или 7 дней в архиве) |

Примеры:

```
/update?time=24&cid=3
/update?date=2026-07-01&t=1&aid=5
/update?st=30
```

Алиас: `/update.php` → тот же обработчик.

## Поиск (требуется авторизация)

| URL | Описание |
|-----|----------|
| `/search` | Поиск фотографий |
| `/vsearch` | Поиск ТС |
| `/csearch` | Поиск комментариев |
| `/authors` | Поиск авторов |
| `/search?id=USER_ID` | Быстрый поиск фото пользователя |

## Личный кабинет и заявки (требуется авторизация)

| URL | Описание |
|-----|----------|
| `/lk/` | Личный кабинет |
| `/lk/upload` | Загрузка медиа |
| `/lk/editimage?id=` | Редактирование фото |
| `/lk/profile?type=OpenVK` | Привязка OpenVK |
| `/lk/ticket.php` | Мои заявки на изменение БД (legacy URL) |
| `/lk/ticket` | То же (маршрут роутера) |
| `/lk/konkurs.php` | Фотоконкурс в личном кабинете (legacy URL) |
| `/lk/konkurs` | То же (маршрут роутера) |
| `/vehicle/dbedit` | Заявка на изменение БД сущностей |
| `/mapmedia` | Карта медиа |
| `/fav_authors` | Фото избранных авторов |
| `/voting/sendpretend` | Подать фото на конкурс |

### API фото

| URL | Метод | Описание |
|-----|-------|----------|
| `/api/photo/move` | GET | Следующее/предыдущее фото (`pid`, `next`) |

## Фотоконкурс — API (требуется авторизация)

| URL | Метод | Описание |
|-----|-------|----------|
| `/api/photo/contests/sendpretend` | POST | Подать фото на конкурс |
| `/api/photo/contests/rate` | GET | Голосование за претендента |
| `/api/contests/getinfo` | GET | Информация о текущем конкурсе |

## Администрирование (требуется `admin ≥ 1`)

| URL | Описание |
|-----|----------|
| `/admin` | Список пользователей |
| `/admin?type=Photo` | Модерация фотографий |
| `/admin?type=News` | Новости сайта (создание, редактирование, удаление) |
| `/admin?type=Chronology` | Хронология |
| `/admin?type=Links` | Ссылки |
| `/admin?type=Galleries` | Тематические галереи |
| `/admin?type=Entities` | Список сущностей |
| `/admin?type=EntityEdit` | Редактирование типов и моделей (`?mod=1`) |
| `/admin?type=Models` | Заявки на изменение БД |
| `/admin?type=Contests` | Фотоконкурсы |
| `/admin?type=GeoDB` | GeoDB |
| `/admin?type=AuthSettings` | Регистрация, OpenVK, управление инстансами |
| `/admin?type=UserEdit&user_id=` | Права пользователя |
| `/admin?type=Settings` | Менеджер задач (cron) |
| `/admin?type=ServerSettings` | **Только владелец (`admin = 4`)** — Debug и конфиг сервера |

### API админки — новости

| URL | Метод | Описание |
|-----|-------|----------|
| `/api/admin/news/create` | POST | Создать новость |
| `/api/admin/news/{id}` | GET | Получить новость для редактирования |
| `/api/admin/news/{id}/edit` | POST | Сохранить правки (фиксирует `edited_at`, `edited_by`) |
| `/api/admin/news/{id}/delete` | POST | Удалить новость |

### API админки — авторизация

| URL | Метод | Описание |
|-----|-------|----------|
| `/api/admin/settings/auth` | POST | Настройки регистрации |
| `/api/admin/settings/auth/providers` | POST | Добавить инстанс OpenVK |
| `/api/admin/settings/auth/providers/{id}` | POST | Изменить инстанс; при смене домена обновляет привязки |
| `/api/admin/settings/auth/providers/{id}/delete` | POST | Удалить инстанс (`replace_with` — перенос привязок) |

### API админки — пользователи и конкурсы

| URL | Метод | Описание |
|-----|-------|----------|
| `/api/admin/users/{id}/edit` | POST | Изменить права пользователя |
| `/api/admin/contests/create` | POST | Создать конкурс |
| `/api/admin/contests/forceclose` | POST | Принудительно завершить конкурс |
| `/api/admin/contests/cancel` | POST | Отменить конкурс |
| `/api/admin/settings/taskmanager` | ANY | Управление cron-задачами |

### API админки — только владелец (`admin = 4`)

| URL | Метод | Описание |
|-----|-------|----------|
| `/api/admin/settings/debug` | POST | Включить/выключить Tracy (`debug=1` / `debug=0`) |
| `/api/admin/settings/server` | POST | Сохранить параметры `root` в `storage/server-settings.json` |

## Обратная совместимость

Старые `.php` URL перенаправляются на новые маршруты:

| Legacy URL | Современный маршрут |
|------------|---------------------|
| `/news.php` | `/news` |
| `/news2.php` | `/news2` |
| `/links.php` | `/links` |
| `/search.php` | `/search` |
| `/vsearch.php` | `/vsearch` |
| `/csearch.php` | `/csearch` |
| `/authors.php` | `/authors` |
| `/update.php` | `/update` |
| `/pk.php` | `/voting/report` (через заглушку `pk.php`) |
| `/help/` | `/help` (через `help/index.php`) |
| `/lk/` | `/lk` (через `lk/index.php`) |
| `/lk/ticket.php` | `/lk/ticket` |
| `/lk/konkurs.php` | `/lk/konkurs` |

Физические заглушки в корне репозитория нужны для Nginx, когда запрос идёт напрямую к `.php`-файлу.