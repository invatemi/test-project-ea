# WB API Test — Этап 1

## Описание

Laravel CLI-приложение для импорта данных из [тестового API Wildberries](https://github.com/cy322666/wb-api) в локальную MySQL.

**Возможности этапа 1:**
- Docker Compose: сервисы **php** и **mysql** (MySQL на порту **3307**, не 3306)
- Мультиаккаунтная модель: компании → аккаунты → токены API
- Типы токенов (api_key, bearer, login_password) и привязка к API-сервисам
- Изоляция данных по `account_id` (данные разных аккаунтов не затирают друг друга)
- Автоимпорт **2 раза в день** (08:00 и 20:00, настраивается)
- Импорт только **свежих данных** с момента последней синхронизации
- Обработка rate limit (HTTP 429, «Too many requests») с exponential backoff
- Отладочный вывод в консоль (`-v`)

Стек: Docker, PHP 8.4, Laravel 12, MySQL 8.

**Репозиторий:** https://github.com/invatemi/test-project-ea

---

## Первый запуск

**Требования:** Docker Desktop, Docker Compose v2.

1. Скопировать конфиг:
   ```bash
   cp .env.example .env
   ```

2. Заполнить в `.env`:
   - `DB_PASSWORD` и `MYSQL_ROOT_PASSWORD` — один пароль MySQL
   - `WB_API_HOST` — URL тестового API
   - `WB_API_KEY` — ключ тестового API (или создайте токен в БД через artisan)
   - `MYSQL_PORT` — порт MySQL на хосте (по умолчанию **3307**)
   - `MYSQL_BIND_HOST` — интерфейс публикации (по умолчанию **127.0.0.1**)

3. Запустить:
   ```bash
   docker compose up -d --build
   ```

4. Проверить:
   ```bash
   docker compose ps
   ```

MySQL доступен на хосте: `${MYSQL_BIND_HOST}:${MYSQL_PORT}` (по умолчанию `127.0.0.1:3307`), база `wb_api_test`.

При первом запуске контейнера `WbApiSeeder` подставит `WB_API_HOST` в таблицу `api_services`.

---

## Тесты

```bash
docker compose exec php php artisan test
```

Покрытие: шифрование токенов, rate limit, мультиаккаунт, fresh data, artisan-команды создания токена.

Отчёт по этапам: [REPORT.md](REPORT.md)

---

## Настройка компании, аккаунта и токена

При первом запуске в БД уже есть компания **Legacy** и аккаунт **default** (id=1), API-сервис **wb_test**.

### 1. Создать компанию
```bash
docker compose exec php php artisan app:company:create "ООО Ромашка"
```

### 2. Создать аккаунт
```bash
docker compose exec php php artisan app:account:create 2 "WB-основной"
```

### 3. Создать API-сервис (если нужен новый)
```bash
docker compose exec php php artisan app:api-service:create wb_test "${WB_API_HOST}" "WB Test API"
```

### 4. Создать тип токена (если нужен новый)
```bash
docker compose exec php php artisan app:token-type:create api_key "API Key"
docker compose exec php php artisan app:token-type:create bearer "Bearer Token"
docker compose exec php php artisan app:token-type:create login_password "Login/Password"
```

### 5. Привязать тип токена к API-сервису
```bash
docker compose exec php php artisan app:api-service:attach-token-type wb_test api_key
```

### 6. Сохранить токен аккаунта
```bash
docker compose exec php php artisan app:account-token:create 1 wb_test api_key --key=ВАШ_КЛЮЧ
```

---

## Импорт данных

### Полный импорт (все сущности, все аккаунты)
```bash
docker compose exec php php artisan app:import-all --all-accounts
```

### По отдельности
```bash
docker compose exec php php artisan app:import-incomes --account=1
docker compose exec php php artisan app:import-orders --account=1
docker compose exec php php artisan app:import-sales --account=1
docker compose exec php php artisan app:import-stocks --account=1
```

### Ручной период (полный диапазон)
```bash
docker compose exec php php artisan app:import-orders --account=1 --date-from=2024-01-01 --date-to=2024-12-31
```

### Отладочный вывод
```bash
docker compose exec php php artisan app:import-orders --account=1 -v
```

### Импорт через очередь
```bash
docker compose exec php php artisan app:import-all --all-accounts --queue
docker compose exec php php artisan queue:work --once
```

Для постоянной обработки очереди установите `RUN_QUEUE_WORKER=true` в `.env` и перезапустите контейнер.

---

## Автоматический импорт (2 раза в день)

В PHP-контейнере **supervisor** запускает `php artisan schedule:work`. Планировщик ставит job `ImportAllAccountsJob`, который диспатчит импорт по каждому аккаунту и сущности в очередь.

Расписание по умолчанию: **08:00** и **20:00** (timezone `APP_TIMEZONE`).

Настройка в `.env`:
```
IMPORT_SCHEDULE_TIMES=8,20
APP_TIMEZONE=Europe/Moscow
QUEUE_CONNECTION=database
RUN_QUEUE_WORKER=true
```

Логи планировщика: `storage/logs/scheduler.log`  
Логи supervisor: `storage/logs/supervisor-*.log`

Проверка здоровья контейнера:
```bash
docker compose exec php php artisan app:health
```

---

## Структура БД

### Справочники
| Таблица | Описание |
|---------|----------|
| `companies` | Компании |
| `accounts` | Аккаунты компании |
| `api_services` | Внешние API-сервисы |
| `token_types` | Типы токенов |
| `api_service_token_types` | Допустимые типы для сервиса |
| `account_tokens` | Токены аккаунтов (зашифрованы) |
| `account_sync_states` | Состояние синхронизации |

### Данные WB API
| Таблица | UNIQUE включает account_id |
|---------|---------------------------|
| `incomes` | account_id + income_id + article + barcode + size |
| `orders` | account_id + odid / account_id + srid |
| `sales` | account_id + sale_id |
| `stocks` | account_id + date + nm_id + warehouse + barcode + size |

---

## Обслуживание

```bash
docker compose ps
docker compose logs php
docker compose logs mysql
docker compose exec php php artisan app:clear-imports
docker compose down
docker compose down -v   # удалить данные БД
```

Миграции (при старте контейнера выполняются автоматически):
```bash
docker compose exec php php artisan migrate
```

Схема БД создаётся **только через миграции Laravel**. Для чистой переинициализации: `docker compose down -v && docker compose up --build`.

---

## Обработка ошибок API

- Throttling между запросами (`WB_API_REQUEST_DELAY_MS`, по умолчанию 1100 мс)
- Retry при HTTP 429 и теле «Too many requests»
- Exponential backoff до 300 секунд
- Учёт заголовка `Retry-After`
