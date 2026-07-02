# Параметры конфигурации (`ngallery.yaml`)

Файл `ngallery.yaml` в корне проекта — главный источник настроек. При установке через `deploy/install-ubuntu-24.04.sh` создаётся автоматически; при ручной установке скопируйте `ngallery-example.yaml`.

Структура: корневой ключ `ngallery` → `root` → параметры.

## Overlay-файлы (перекрытие yaml)

Помимо `ngallery.yaml`, движок поддерживает JSON-overlay в каталоге `storage/`. Они применяются в `index.php` **поверх** yaml и не изменяют исходный файл на диске.

| Файл | Кто управляет | Что перекрывает |
|------|---------------|-----------------|
| `storage/auth-settings.json` | Админ (`/admin?type=AuthSettings`) | `registration`, `openvk` (инстансы, включение регистрации) |
| `storage/server-settings.json` | Владелец (`/admin?type=ServerSettings`, `admin = 4`) | Параметры `root` (включая `debug`, `footerslogan`, `title` и др.) |

Приоритет: `ngallery.yaml` → `storage/server-settings.json` → `storage/auth-settings.json` (auth применяется отдельно).

Права на запись для PHP-FPM (`www-data`):

```bash
chown -R www-data:www-data storage
chmod -R 775 storage
```

### Debug (Tracy) через админку

Владелец сервера может включать и выключать Tracy в разделе **Админка → Сервер** без правки yaml. Состояние сохраняется в `storage/server-settings.json` (`root.debug`). На production рекомендуется держать выключенным.

## Общие

| Параметр | Тип | По умолчанию | Описание |
|----------|-----|--------------|----------|
| `title` | string | `NativeGallery` | Заголовок сайта (`<title>`, логотип) |
| `adminemail` | string | — | Email администратора (уведомления, отправитель писем) |
| `showtitle` | bool | `false` | Показывать текстовое название рядом с логотипом |
| `logo` | string | `/static/img/logosmall.png` | URL логотипа в шапке |
| `description` | string | `""` | Meta description |
| `keywords` | string | `""` | Meta keywords |
| `maintenance` | bool | `false` | Режим обслуживания (страница «сервер недоступен») |
| `debug` | bool | `false` | Tracy debugger; логи пишутся в `logslocation` |
| `alloweddomains` | array | `[]` | Разрешённые домены для CORS/проверок |
| `botkey` | string | `""` | Ключ защиты от ботов (если используется) |
| `logslocation` | string | `/logs` | Каталог логов Tracy (относительно корня проекта) |
| `encryptionkey` | string | — | 32-байтный hex-ключ шифрования (генерируется при установке) |
| `footerslogan` | string | — | Текст в подвале сайта (отображается на всех страницах) |
| `timezone` | string | `Europe/Moscow` | Часовой пояс PHP для дат, конкурсов и форм |
| `cloudflare-caching` | bool | `false` | Добавлять `?timestamp` к CSS/JS (сброс кэша) |

### Доступ по странам

```yaml
access:
  type: 'allow'   # allow | deny
  countries: ''   # коды стран через запятую
```

### Дополнительные пункты меню (необязательно)

```yaml
navbar:
  - name: 'Мой раздел'
    link: '/custom'
```

Если ключ `navbar` отсутствует, дополнительные пункты не выводятся.

## База данных

```yaml
db:
  name: 'ngallery'
  host: '127.0.0.1'
  login: 'ngallery'
  password: 'secret'
```

| Параметр | Описание |
|----------|----------|
| `name` | Имя базы MySQL/MariaDB |
| `host` | Хост (обычно `127.0.0.1`) |
| `login` | Пользователь БД |
| `password` | Пароль |

## Хранилище файлов

```yaml
storage:
  type: 'server'   # server | s3
  s3:
    domains:
      public: ''
      gateway: ''
    credentials:
      key: ''
      secret: ''
      region: 'auto'
      version: 'latest'
      bucket: ''
```

| `storage.type` | Описание |
|----------------|----------|
| `server` | Файлы в каталоге `uploads/` на сервере (рекомендуется для VPS) |
| `s3` | Amazon S3, Cloudflare R2, MinIO и совместимые хранилища |

Для локального хранилища нужны права на запись: `uploads/`, `cdn/`, `storage/locks/`, `logs/`.

## Почта

```yaml
email:
  credentials:
    host: 'smtp.example.com'
    username: 'user'
    password: 'pass'
    port: 465
  from:
    address: 'noreply@example.com'
```

Используется при `registration.emailverify: true` и системных уведомлениях.

## Изображения

```yaml
img:
  proxy: true    # проксирование/сжатие через /api/photo/compress
  percent: 50    # качество сжатия (0–100)
```

## Регистрация

```yaml
registration:
  emailverify: false
  prohibited_usernames: ''
  access:
    public: true
```

| Параметр | Описание |
|----------|----------|
| `emailverify` | Требовать подтверждение email перед полным доступом |
| `prohibited_usernames` | Запрещённые ники (через запятую или список) |
| `access.public` | Разрешить публичную регистрацию |

Настройки регистрации можно менять в админке (`/admin?type=AuthSettings`); изменения сохраняются в `storage/auth-settings.json`.

## Фотографии

```yaml
photo:
  upload:
    allow: true
    premoderation: true
    defaultindex: 5.0
    allowgif: true
  uploadindex:
    enabled: false
    default: 5
    acceptvalue: 1
    declinevalue: 2
```

| Параметр | Описание |
|----------|----------|
| `upload.allow` | Разрешить загрузку фото |
| `upload.premoderation` | Новые фото требуют модерации (`moderated=0`) |
| `upload.defaultindex` | Стартовый индекс качества |
| `upload.allowgif` | Разрешить GIF |
| `uploadindex.enabled` | Включить оценку качества при загрузке |
| `uploadindex.default` | Значение по умолчанию |
| `uploadindex.acceptvalue` | Порог «принять» |
| `uploadindex.declinevalue` | Порог «отклонить» |

## Видео

```yaml
video:
  upload:
    allow: true
```

Требуется установленный `ffmpeg` в PATH.

## Комментарии

```yaml
comments:
  premoderation: false
```

## Фотоконкурсы

```yaml
contests:
  enabled: true
  autonew:
    enabled: true
    times:
      pretendsopen: 'now'
      pretendsclose: '2d'
      open: 'now'
      close: '2d'
```

| Параметр | Описание |
|----------|----------|
| `enabled` | Включить модуль конкурсов |
| `autonew.enabled` | Автоматически создавать новые конкурсы по cron |
| `autonew.times.*` | Относительное время этапов (`now`, `2d`, `1w` и т.д.) |

Cron: `deploy/setup-cron.sh` → задача каждые 5 минут для `ExecContests.php`.

## Вход через OpenVK

Поддерживается авторизация через несколько инстансов OpenVK. В `ngallery-example.yaml` и на production-сервере форка настроены два провайдера:

- **openvk.org** (`openvk_org`) — основной инстанс;
- **vepurovk.xyz** (`vepurovk`) — второй инстанс OVK на сервере, пример для сайтов с собственным или альтернативным узлом OpenVK.

Дополнительные инстансы добавляются в `openvk.providers` по тому же шаблону.

```yaml
openvk:
  enabled: true
  client_name: 'CTTC Gallery'
  redirect_uri: ''
  response_type: 'php'
  auto_register: true
  providers:
    openvk_org:
      enabled: true
      label: 'OpenVK.org'
      domain: 'https://openvk.org'
      api_domain: 'https://api.openvk.org'
      accent: '#5181b8'
      icon: 'https://openvk.org/assets/packages/static/openvk/img/favicon.ico'
    vepurovk:                    # второй инстанс OVK (production-сервер форка)
      enabled: true
      label: 'VepurOVK'
      domain: 'https://vepurovk.xyz'
      accent: '#45668e'
      icon: 'https://vepurovk.xyz/assets/packages/static/openvk/img/favicon.ico'
```

| Параметр | Описание |
|----------|----------|
| `enabled` | Показывать кнопки входа и вкладку привязки в профиле |
| `client_name` | Название сайта на странице подтверждения OpenVK |
| `redirect_uri` | Callback URL; пусто = `https://ВАШ_ДОМЕН/auth/callback` |
| `response_type` | `php` (токен в query) или `token` (токен в `#`, нужен JS) |
| `auto_register` | Создавать локальный аккаунт при первом входе через OpenVK |
| `providers.*.enabled` | Включить инстанс (кнопка на `/login`); по умолчанию `true` |
| `providers.*.api_domain` | API-хост (для openvk.org обязательно `https://api.openvk.org`) |
| `providers.*.accent` | Цвет кнопки входа |
| `providers.*.icon` | Favicon/иконка инстанса |

Управление из админки: `/admin?type=AuthSettings` (для `admin ≥ 1`). Все инстансы (включая из yaml) можно **редактировать**, **удалять** и **добавлять**; изменения в `storage/auth-settings.json` перекрывают `ngallery.yaml`. При удалении инстанса можно перенести привязки пользователей OpenVK на другой узел.

Привязка существующего аккаунта: `/lk/profile?type=OpenVK`.

## WebSockets

```yaml
websockets:
  messages: ""
```

Зарезервировано для будущего функционала личных сообщений.

## Уровни администраторов (БД, не yaml)

Поле `users.admin`:

| Значение | Роль | Доступ |
|----------|------|--------|
| `0` | Пользователь | — |
| `1` | Администратор | Полный доступ к админке |
| `2` | Фотомодератор | Модерация фото |
| `3` | Модератор | Ограниченная модерация |
| `4` | Владелец сервера | Всё из `1` + раздел «Сервер», Debug, overlay `server-settings.json` |

Первого администратора назначают через SQL после регистрации:

```sql
UPDATE users SET admin = 1 WHERE username = 'ваш_ник';
```

Владельца сервера (для production-диагностики и правки конфига без SSH):

```sql
UPDATE users SET admin = 4 WHERE username = 'ваш_ник';
```

Миграция для роли владельца **не требуется** — используется существующее поле `admin`.