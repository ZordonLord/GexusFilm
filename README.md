# GexusFilm

GexusFilm — киноагрегатор с фронтендом на React/Vite и бекендом на PHP. Приложение показывает трендовые фильмы, популярные релизы, сейчас идущие киносеансы и будущие премьеры.

## Стек

- Frontend: React, Vite, Tailwind CSS
- Backend: PHP (без фреймворка)
- Database: PostgreSQL
- API: TMDB (The Movie Database)

## Структура проекта

```text
backend/
  public/
    api/                # JSON API для фронтенда
    movie.php           # страница фильма
    movies.php          # страница списка популярных фильмов
    search.php          # страница поиска
  src/
    bootstrap.php
    config.php
    Database.php
    MovieRepository.php
    TmdbClient.php
  database.sql
  .env.example
frontend/
  src/
    App.jsx
    main.jsx
    pages/
    components/
    styles/
  package.json
  vite.config.js
  eslint.config.js
  README.md
docker-compose.yml
```

## Что работает

- Загрузка трендовых, популярных, сейчас идущих и будущих фильмов
- Поиск фильмов по названию
- Страница фильма с деталями
- Кэширование API-ответов TMDB в PostgreSQL

## Настройка backend

Скопируй пример конфигурации:

```powershell
Copy-Item backend\.env.example backend\.env
```

Открой `backend/.env` и задай ключ TMDB и параметры базы данных:

```env
TMDB_API_KEY=your_tmdb_api_key

DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=zordonfilm
DB_USERNAME=zordonfilm
DB_PASSWORD=zordonfilm

CACHE_TTL_MINUTES=1440
```

### Важно

Для работы сохранения данных в PostgreSQL требуется PHP-расширение `pdo_pgsql`.

Проверь его командой:

```powershell
php -m
```

Если `pdo_pgsql` отсутствует, включи его в `php.ini`.

## Запуск PostgreSQL

```powershell
docker compose up -d postgres
```

При первом запуске docker автоматически применит схему из `backend/database.sql`.

## Запуск backend

```powershell
php -S localhost:8000 -t backend/public
```

После этого API доступны по адресу:

- `http://localhost:8000/api/movies.php`
- `http://localhost:8000/api/search.php?q=matrix`
- `http://localhost:8000/api/movie.php?id=603`

> В текущей версии фронтенда база `API_BASE` в `frontend/src/services/api.js` указывает на `http://138.124.240.208:8000/api`.
> Для локальной разработки замените `API_BASE` на `http://localhost:8000/api`.

## Запуск frontend

```powershell
cd frontend
npm install
npm run dev
```

Откройте адрес из вывода Vite, например:

```text
http://localhost:5173
```

## Конфигурация frontend

Фронтенд использует `frontend/src/styles/app.css` как основной global-стиль и `frontend/src/pages/HomePage.css` для страницы с фильмами.

## Как работает кэширование

1. Фронтенд запрашивает данные у PHP API.
2. Backend проверяет PostgreSQL-кэш.
3. Если кэш действителен, данные возвращаются из PostgreSQL.
4. Если нет — backend запрашивает TMDB.
5. Ответ сохраняется и возвращается клиенту.

## Исключения из репозитория

В репозиторий не должны попадать:

- `backend/.env`
- `api.txt`
- `node_modules/`
- локальные файлы, логи и кэши

Пример конфигурации хранится в `backend/.env.example`.
