# ZordonFilm

Агрегатор фильмов на базе TMDB API. Проект показывает популярные фильмы, ищет фильмы по названию и открывает страницу фильма с деталями. Backend умеет сохранять ответы TMDB в PostgreSQL, чтобы повторно использовать уже полученные данные.

## Стек

- Frontend: React, TypeScript, Vite, Tailwind CSS
- Backend: PHP
- Database: PostgreSQL
- API: The Movie Database API

## Структура проекта

```text
backend/
  public/
    api/              # JSON API для фронтенда
    movies.php        # простая PHP-страница популярных фильмов
    movie.php         # простая PHP-страница фильма
    search.php        # простая PHP-страница поиска
  src/
    TmdbClient.php
    Database.php
    MovieRepository.php
    bootstrap.php
    config.php
  database.sql
frontend/
  src/
docker-compose.yml
```

## Настройка backend

Создай локальный env-файл:

```powershell
Copy-Item backend\.env.example backend\.env
```

В `backend/.env` можно поменять:

```env
TMDB_API_KEY=your_tmdb_api_key

DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=zordonfilm
DB_USERNAME=zordonfilm
DB_PASSWORD=zordonfilm

CACHE_TTL_MINUTES=1440
```

Важно: для подключения PHP к PostgreSQL нужен модуль `pdo_pgsql`. Проверь его так:

```powershell
php -m
```

Если `pdo_pgsql` в списке нет, включи расширение в `php.ini`. Без него приложение продолжит работать через TMDB, но сохранять данные в PostgreSQL не сможет.

## Запуск PostgreSQL

```powershell
docker compose up -d postgres
```

При первом запуске Docker применит схему из `backend/database.sql` и создаст таблицы `movies` и `api_cache`.

## Запуск backend

```powershell
php -S localhost:8000 -t backend/public
```

API endpoints:

- `http://localhost:8000/api/movies.php`
- `http://localhost:8000/api/search.php?q=matrix`
- `http://localhost:8000/api/movie.php?id=603`

## Запуск frontend

```powershell
Set-Location frontend
npm install
npm run dev
```

После запуска Vite откроет адрес вида:

```text
http://localhost:5173
```

Frontend сейчас ожидает backend на `http://localhost:8000`.

## Как работает сохранение данных

1. Frontend запрашивает фильмы у PHP API.
2. Backend сначала проверяет PostgreSQL-кэш.
3. Если кэш найден и не истек, backend возвращает данные из PostgreSQL.
4. Если данных нет, backend делает запрос в TMDB.
5. Ответ TMDB сохраняется в PostgreSQL и возвращается клиенту.

Данные популярных фильмов и поиска сохраняются как кэш API-ответов, а карточки фильмов дополнительно сохраняются в таблицу `movies`.

## Git

В репозиторий не должны попадать:

- `backend/.env`
- `api.txt`
- `node_modules/`
- локальные логи, кэши и временные файлы

Пример настроек хранится в `backend/.env.example`.
