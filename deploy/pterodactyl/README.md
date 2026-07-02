# Pterodactyl Egg — NativeGallery

1. Соберите образ на ноде: `docker build -t ghcr.io/lanmikeman/nativegallery:latest .` (из корня репозитория)
2. Admin → Nests → **Import Egg** → `egg-nativegallery.json`
3. Создайте сервер; укажите **внешнюю** MySQL/MariaDB в переменных
4. Allocation: проксируйте порт на nginx (:8080 в контейнере)

Документация: [docs/pterodactyl.md](../../docs/pterodactyl.md)