# Параметры конфигурации (`ngallery.yaml`)

Файл `ngallery.yaml` в корне проекта — главный источник настроек. При установке через `deploy/install-ubuntu-24.04.sh` создаётся автоматически; при ручной установке скопируйте `ngallery-example.yaml`.

Структура: корневой ключ `ngallery` → `root` → параметры.

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
| `footerslogan` | string | — | Текст в подвале сайта |
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

```yaml
openvk:
  enabled: true
  client_name: 'CTTC Gallery'
  redirect_uri: ''
  response_type: 'php'
  auto_register: true
  providers:
    openvk_org:
      label: 'OpenVK.org'
      domain: 'https://openvk.org'
      api_domain: 'https://api.openvk.org'
      accent: '#5181b8'
      icon: 'https://openvk.org/assets/packages/static/openvk/img/favicon.ico'
    vepurovk:
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
| `providers.*.api_domain` | API-хост (для openvk.org обязательно `https://api.openvk.org`) |
| `providers.*.accent` | Цвет кнопки входа |
| `providers.*.icon` | Favicon/иконка инстанса |

Привязка существующего аккаунта: `/lk/profile?type=OpenVK`.

## WebSockets

```yaml
websockets:
  messages: ""
```

Зарезервировано для будущего функционала личных сообщений.

## Уровни администраторов (БД, не yaml)

Поле `users.admin`:

| Значение | Роль |
|----------|------|
| `0` | Пользователь |
| `1` | Администратор |
| `2` | Фотомодератор |
| `3` | Модератор |