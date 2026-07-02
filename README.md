# NativeGallery
![](https://raw.githubusercontent.com/claradex/nativegallery/main/static/img/banner.png)
NativeGallery - это реверсивный open-source движок популярного сайта transphoto.org (СТТС), RailGallery, Fotobus и ему подобных.

> **Форк [Lanmikeman/nativegallery](https://github.com/Lanmikeman/nativegallery)** — добавлена поддержка Ubuntu 24.04 + Nginx, публичные страницы новостей/хронологии и скрипт автоматической установки. См. [CHANGELOG.md](CHANGELOG.md).

# Почему я должен использовать ваш движок?
* **Свобода**: СТТС не предоставляет всем свой исходный код для создания отдельных подобных ему сайтов. С NativeGallery вы сможете обойти это предпятствие!
* **Гибкость**: Настраивайте сайт по вашим предпочтениям: управляйте приватностью разделов, настраивайте дизайн сайта, назначайте администраторов, фотомодераторов и многое другое!
* **Скорость**: Движок оптимизирован под последнюю версию PHP 8.3 и MariaDB 10!

# Системные требования
Мы настоятельно рекомендуем устанавливать движок на VPS/VDS/выделенный сервер. Поддержка на Shared-хостингах не осуществляется!

**Операционная система**: Ubuntu 20.04 и выше (рекомендуется Ubuntu 24.04)\
**Веб-сервер**: Apache (`.htaccess`) или Nginx + PHP-FPM\
**PHP:** 8.3 и выше (расширения: gd, curl, mbstring, xml, zip, exif, pdo_mysql)\
**База данных**: MySQL 8.0 и выше\
**Дополнительно**: Composer, ffmpeg (для загрузки видео)

# Статус функционала
### Обязательные к выполнению функции
- [x] Авторизация, Регистрация
- [x] Просмотр профилей
- [ ] Публикация фото:
  - [x] Привязка сущности (Транспортное Средство, Поезд, Самокат, Камень и прочее)
  - [x] Загрузка фото
  - [x] GeoDB
  - [x] Геометка
  - [ ] Направление съёмки
  - [ ] Состояние фотографии
    - [x] Временная публикация
    - [x] Условная публикация
    - [x] Техническая публикация
    - [ ] Требует исправления
    - [ ] Возможность создания своих статусов
  - [x] Галереи
  - [x] Вид сущности (Трамвай, Метрополитен, Троллейбус и т.д)
- [x] GeoDB
- [ ] Фотоконкурс
- [x] Поиск (требуется авторизация)
  - [x] Поиск фотографий (`/search`) — автор, город, место, ТС, маршрут, галерея, даты, EXIF
  - [x] Поиск ТС (`/vsearch`)
  - [x] Поиск комментариев (`/csearch`)
  - [x] Поиск авторов (`/authors`)
- [ ] Сущности
  - [x] Страница сущности
  - [x] Статус (Эксплуатируется, списан и прочее)
  - [ ] Привязка к номеру
  - [x] Редактирование базы данных
- [ ] Фотографии:
  - [x] Просмотр
  - [x] Рейтинг
  - [x] Комментирование
  - [x] Рейтинг комментариев
  - [x] Избранные фотографии
  - [x] Полноценный EXIF
  - [x] Модерация
  - [ ] Редактирование
- [ ] Обновления:
  - [x] Новые фотографии
  - [x] Новые фотографии из подписок
  - [ ] Новые фотографии по городам (требуется GeoDB)
- [ ] Комментарии:
  - [x] Публикация
  - [ ] Модерация
  - [x] Рейтинг
  - [ ] BB-коды
  - [ ] Форматирование
  - [x] Удаление
  - [x] Редактирование
- [x] Публичные разделы:
  - [x] Новости сайта (`/news2`)
  - [x] Новости и хронология (`/news`)
  - [x] Разные фотогалереи (`/misc`)
  - [x] Ссылки (`/links`)
### Необязательные, но будет неплохо их сделать тоже
  - [ ] Авторизация
    - [ ] Через Telegram
    - [ ] Через ВКонтакте
    - [ ] Через Google
    - [ ] Через Яндекс
    - [ ] Через Twitter
    - [ ] Через Facebook
    - [ ] Через Discord
    - [ ] Через Steam (?!)
    - [ ] Сторонняя авторизация через API
  - [ ] Автоматическое обновление движка через Админ-панель
  - [ ] СТТС.Клуб (Native Clubs)
    - [ ] Отметки людей на фотографиях
    - [ ] Прямой эфир (https://sttsclub.ru/live/)
  - [ ] СТТС.Форум (NativeGallery Forum)
  - [ ] Экспорт всех фотографий и данных с аккаунта

# Установка

## Быстрая установка (Ubuntu 24.04 + Nginx + PHP + MySQL)

```bash
git clone https://github.com/Lanmikeman/nativegallery.git /tmp/nativegallery
cd /tmp/nativegallery
sudo NG_DOMAIN=example.com NG_DB_PASS=your_password bash deploy/install-ubuntu-24.04.sh
```

Скрипт автоматически:
- устанавливает nginx, PHP 8.3, MySQL 8, Composer, ffmpeg;
- создаёт базу данных и пользователя;
- клонирует проект в `/var/www/nativegallery`;
- импортирует все SQL-миграции из `sqlcore/`;
- генерирует `ngallery.yaml` с локальным хранилищем (`storage: server`);
- настраивает Nginx и cron для фотоконкурсов.

Переменные окружения скрипта:

| Переменная | По умолчанию | Описание |
|------------|--------------|----------|
| `NG_DOMAIN` | `example.com` | Домен сайта |
| `NG_DB_NAME` | `ngallery` | Имя базы данных |
| `NG_DB_USER` | `ngallery` | Пользователь MySQL |
| `NG_DB_PASS` | случайный | Пароль MySQL |
| `NG_WEB_ROOT` | `/var/www/nativegallery` | Путь к проекту |

## Ручная установка

1. Убедитесь, что установлены PHP 8.3, MySQL 8.0+, Composer и ffmpeg.
2. Склонируйте репозиторий и установите зависимости:
   ```bash
   git clone https://github.com/Lanmikeman/nativegallery.git /var/www/nativegallery
   cd /var/www/nativegallery
   composer install --no-dev
   ```
3. Импортируйте SQL-миграции **в указанном порядке**:
   ```bash
   mysql ngallery < sqlcore/base.sql
   mysql ngallery < sqlcore/sql_0001.sql
   mysql ngallery < sqlcore/sql_0002.sql
   mysql ngallery < sqlcore/sql_0003.sql
   mysql ngallery < sqlcore/sql_0004.sql
   mysql ngallery < sqlcore/sql_0005.sql
   ```
4. Создайте конфигурацию:
   ```bash
   cp ngallery-example.yaml ngallery.yaml
   # заполните db.host, db.name, db.login, db.password
   ```
5. Создайте директории и выдайте права:
   ```bash
   mkdir -p uploads cdn/temp cdn/previews cdn/image cdn/video logs lock
   chown -R www-data:www-data uploads cdn logs lock
   chmod -R 775 uploads cdn logs lock
   ```
6. Настройте веб-сервер (см. раздел ниже).
7. Зарегистрируйте первого пользователя на `/register` и выдайте ему права администратора (см. раздел «Администрирование»).

Если всё сделано правильно, вы увидите пустую главную страницу галереи.

## Nginx

Готовый конфиг: `deploy/nginx/nativegallery.conf`

```bash
sudo cp deploy/nginx/nativegallery.conf /etc/nginx/sites-available/nativegallery
# замените example.com и /var/www/nativegallery в конфиге
sudo ln -sf /etc/nginx/sites-available/nativegallery /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

Конфиг включает:
- front controller (`try_files` → `index.php`);
- PHP 8.3-FPM через unix-socket;
- защиту конфигурационных файлов (`.yaml`, `.git`, `/app/`, `/vendor/` и др.);
- `client_max_body_size 128M` для загрузки фото и видео.

## Apache

Движок из коробки поддерживает Apache с `mod_rewrite`. Файл `.htaccess` в корне проекта уже настроен.

# Администрирование

## Выдача прав администратора

После регистрации первого пользователя выполните в MySQL:

```sql
UPDATE users SET admin = 1 WHERE username = 'ваш_ник';
```

Уровни доступа (`поле admin`):

| Значение | Роль |
|----------|------|
| `0` | Пользователь |
| `1` | Администратор (полный доступ) |
| `2` | Фотомодератор |
| `3` | Модератор |

Панель администратора: `/admin`

## Управление контентом

| Раздел | URL | Описание |
|--------|-----|----------|
| Новости сайта | `/admin?type=News` | Объявления и техработы (отображаются на `/news2` и главной) |
| Хронология | `/admin?type=Chronology` | События транспорта (отображаются на `/news`) |
| Ссылки | `/admin?type=Links` | Внешние ссылки (отображаются на `/links`) |
| Галереи | `/admin?type=Galleries` | Тематические галереи (отображаются на `/misc`) |

# Публичные разделы

| URL | Описание |
|-----|----------|
| `/news` | Новости и хронология — события в мире транспорта с фильтрами по городу, типу транспорта, дате и тексту |
| `/news2` | Новости сайта — официальные объявления администрации |
| `/misc` | Список открытых фотогалерей |
| `/links` | Внешние ссылки |
| `/voting` | Фотоконкурс |
| `/comments` | Лента комментариев |
| `/update` | Новые фотографии за 72 часа |
| `/search` | Поиск фотографий (требуется вход) |
| `/vsearch` | Поиск транспортных средств |
| `/csearch` | Поиск комментариев |
| `/authors` | Поиск авторов |

Старые URL (`/news.php`, `/news2.php`, `/links.php`, `/search.php` и др.) поддерживаются.

## Поиск

Как на transphoto.org, поиск доступен **только авторизованным пользователям**.

| Раздел | URL | Критерии |
|--------|-----|----------|
| Фотографии | `/search` | автор, город (GeoDB), место, вид сущности, ID ТС, маршрут, галерея, текст, камера (EXIF), дата съёмки/публикации |
| ТС | `/vsearch` | вид сущности, ID/номер, текст |
| Комментарии | `/csearch` | текст, автор, ID фото, дата |
| Авторы | `/authors` | никнейм или email |

Быстрый поиск фото пользователя: `/search?id=USER_ID`

# Обновление существующей установки

```bash
cd /var/www/nativegallery
git pull
composer install --no-dev
mysql ngallery < sqlcore/sql_0005.sql   # если ещё не применялась
sudo systemctl reload nginx php8.3-fpm
```

# Changelog

История изменений: [CHANGELOG.md](CHANGELOG.md)