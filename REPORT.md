# Отчёт по проекту WB API Test

## План 25.06

**Задачи:**
1. Выложить БД MySQL на нестандартный порт (публикация только на localhost).
2. Перенести все чувствительные данные в `.env`, продублировать шаблон в `.env.example`.
3. Убрать захардкоженные URL и секреты из кода, `db.sql` и миграций — использовать переменные окружения и seeder.
4. Добавить PHPUnit-тесты на уязвимые участки логики (токены, rate limit, мультиаккаунт, fresh data).
5. Прогнать тесты и убедиться, что все проходят.
6. Обновить отчёт «План / Факт».

---

## Факт 25.06

**Задачи:**
1. MySQL публикуется на **`127.0.0.1:${MYSQL_PORT}`** (по умолчанию **3307**, не 3306) через `MYSQL_BIND_HOST` и `MYSQL_PORT` в `.env`.
2. Чувствительные данные вынесены в `.env`: пароли БД (`DB_PASSWORD`, `MYSQL_ROOT_PASSWORD`), `APP_KEY`, `WB_API_KEY`, `WB_API_HOST` и прочие настройки API/планировщика; шаблон актуализирован в `.env.example`.
3. Удалён hardcoded default URL из `config/wb_api.php`; seed API-сервиса перенесён в **`WbApiSeeder`** (читает `WB_API_HOST` из env); `db.sql` больше не содержит URL API; entrypoint запускает seeder при старте контейнера.
4. Добавлены **17 PHPUnit-тестов** (34 assertions):
   - шифрование credentials (`AccountTokenTest`);
   - rate limit / retry / 403 (`WbApiClientTest`);
   - свежие данные по sync state (`FreshDataRangeResolverTest`);
   - мультиаккаунт и токены (`AccountResolverTest`);
   - создание токена через artisan (`CreateAccountTokenCommandTest`).
5. Тесты прогнаны: **`php artisan test` — 17 passed**.
6. Отчёт обновлён (этот файл).

---

## План 24.06 (Этап 1 — исходное ТЗ)

**Задачи:**
1. Docker-compose: php + mysql, нестандартный порт MySQL.
2. Ежедневное обновление данных 2 раза в день.
3. Обработка «Too many requests» и отказоустойчивость.
4. Отладочный вывод в консоль.
5. Структура БД: компании, аккаунты, токены, API-сервисы, типы токенов.
6. Консольные команды CRUD для справочников и токенов.
7. Мультиаккаунт в методах импорта.
8. Поле `account_id`, изоляция данных между аккаунтами.
9. Импорт только свежих данных по полю `date`.
10. Тестирование и публикация на GitHub.

---

## Факт 24.06 (Этап 1 — выполнено)

**Задачи:**
1. Настроен docker-compose (**php** + **mysql**), MySQL на порту **3307**, cron для scheduler.
2. Автоимпорт **2 раза в день** (08:00, 20:00) через `app:import-all --all-accounts`.
3. Retry при HTTP 429 / «Too many requests» с exponential backoff.
4. Debug-вывод через `-v` и `storage/logs/laravel.log`.
5. Схема БД: `companies`, `accounts`, `api_services`, `token_types`, `account_tokens`, `account_sync_states`.
6. Команды: `app:company:create`, `app:account:create`, `app:api-service:create`, `app:token-type:create`, `app:api-service:attach-token-type`, `app:account-token:create`.
7. Импорт с `--account=` / `--all-accounts`, токены из БД.
8. `account_id` во всех data-таблицах, UNIQUE-ключи с `account_id`.
9. `FreshDataRangeResolver` + `account_sync_states` для свежих данных.
10. Протестировано вручную, код на GitHub: https://github.com/invatemi/test-project-ea
