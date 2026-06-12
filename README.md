# WB API Test

## Описание

Laravel-приложение для импорта данных из [тестового API Wildberries](https://github.com/cy322666/wb-api) в локальную MySQL.

Поддерживаемые сущности: **incomes** (поставки), **orders** (заказы), **sales** (продажи), **stocks** (остатки).

Стек: Docker, PHP 8.4, Laravel, MySQL 8. Данные с API сохраняются в БД `wb_api_test`; отдельный удалённый сервер БД не требуется.

---

## Первый запуск

**Требования:** Docker Desktop, Docker Compose v2, доступ в интернет (для WB API).

1. Скопировать конфиг:
   ```bash
   cp .env.example .env
   ```
   На Windows — скопировать файл `.env.example` в `.env` вручную.

2. Заполнить в `.env`:
   - `DB_PASSWORD` и `MYSQL_ROOT_PASSWORD` — один и тот же пароль MySQL
   - `WB_API_KEY` — ключ тестового API

   `APP_KEY` сгенерируется автоматически при первом запуске контейнера.

3. Запустить контейнеры:
   ```bash
   docker compose up -d --build
   ```

4. Проверить, что сервисы работают:
   ```bash
   docker compose ps
   ```

---

## Эксплуатация

### Импорт данных

```bash
docker compose exec app php artisan app:import-incomes
docker compose exec app php artisan app:import-orders
docker compose exec app php artisan app:import-sales
docker compose exec app php artisan app:import-stocks
```

Опции периода:

```bash
docker compose exec app php artisan app:import-orders --date-from=2024-01-01 --date-to=2024-12-31
docker compose exec app php artisan app:import-stocks --date=2026-06-12
```

Импорт orders и sales для полного диапазона может занять несколько минут из‑за лимита API (60 запросов/мин).

### Просмотр данных

MySQL доступен на хосте: `127.0.0.1`, порт `3307` (значение `MYSQL_PORT` в `.env`), база `wb_api_test`, пользователь `root`.

Таблицы: `incomes`, `orders`, `sales`, `stocks`.

Пример через контейнер:

```bash
docker compose exec mysql mysql -uroot -p wb_api_test
```

Пример подсчёта записей:

```bash
docker compose exec app php artisan tinker --execute="echo App\Models\Order::count();"
```

### Обслуживание

```bash
docker compose ps                  # статус контейнеров
docker compose logs app            # логи Laravel
docker compose logs mysql          # логи MySQL
docker compose down                # остановка
docker compose down -v             # остановка и полное удаление данных БД
```

Очистить импортированные данные:

```bash
docker compose exec app php artisan app:clear-imports
```
