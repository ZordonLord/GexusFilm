# GexusFilm

Киноагрегатор для поиска и выбора фильмов и сериалов. Проект объединяет React-приложение, структурированный PHP API, TMDB как внешний источник контента и PostgreSQL-кэш.

> **Текущий статус:** v0.2 завершена — базовый каталог фильмов и сериалов, общий Media UI, единый API-клиент и REST-маршрутизация работают. Следующий этап — v0.3: поиск, фильтры, Redis и Meilisearch.

[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=20232a)](https://react.dev/)
[![Vite](https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white)](https://vite.dev/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/)

## Содержание

- [Возможности](#возможности)
- [Текущее состояние](#текущее-состояние)
- [Стек](#стек)
- [Архитектура](#архитектура)
- [Структура проекта](#структура-проекта)
- [Быстрый запуск](#быстрый-запуск)
- [API](#api)
- [Проверки](#проверки)
- [План развития](#план-развития)
- [Конфигурация и безопасность](#конфигурация-и-безопасность)

## Возможности

### Реализовано

- каталоги фильмов: trending, popular, now playing и upcoming;
- каталоги сериалов: trending, popular, on the air и airing today;
- страницы деталей фильма и сериала;
- получение сезонов и эпизодов сериала;
- поиск и discover API с параметрами типа контента, жанра и страницы;
- единый frontend Media UI для фильмов и сериалов;
- PostgreSQL-кэш ответов TMDB с TTL;
- единый front-controller и параметризованный Router;
- Composer PSR-4 автозагрузка, PHPUnit и PHP_CodeSniffer.

### В разработке

Публичный каталог и базовый API работают. Интерфейс отдельного поиска и расширенная фильтрация пока не завершены: frontend содержит главную страницу, каталог и страницу медиа, а экран `/search` находится в плане v0.3.

## Текущее состояние

| Версия | Содержание | Статус |
| --- | --- | --- |
| v0.1 | Базовый каталог, TMDB и PostgreSQL-кэш | ✅ Готово |
| v0.2 | Структурированный backend, сериалы, Media UI и REST-маршруты | ✅ Готово |
| v0.3 | Поиск, фильтры, Redis и Meilisearch | 🎯 Следующий этап |
| v0.4 | Аккаунты и пользовательские данные | ⬜ Запланировано |
| v0.5 | Списки, оценки и watch providers | ⬜ Запланировано |
| v0.6 | Персоны, расширенные детали и UX | ⬜ Запланировано |
| v1.0 | CI/CD, мониторинг, PWA, i18n и production-ready релиз | ⬜ Запланировано |

## Стек

| Область | Технологии |
| --- | --- |
| Frontend | React 19, React Router 7, Vite 8, Axios |
| Backend | PHP 8.1+, Composer, PSR-4, собственный Router |
| Database | PostgreSQL 16 |
| External API | TMDB API через `ContentSourceInterface` и `TmdbClient` |
| Tests | PHPUnit 11 |
| Code quality | PHP_CodeSniffer 3.9, ESLint |
| Local infrastructure | Docker Compose |

CSS Modules — целевой стандарт стилей. Сейчас во frontend ещё используется Tailwind CSS и Vite plugin; миграция на CSS Modules запланирована отдельной технической задачей.

## Архитектура

```text
React/Vite frontend
        │ HTTP / JSON
        ▼
public/index.php → Router → Controller → Service
                                      ├── Repository → PostgreSQL
                                      └── ContentSourceInterface → TmdbClient → TMDB
```

Основные принципы:

- `backend/public/index.php` — единая точка входа для API;
- `Router` сопоставляет HTTP-метод и путь, но не содержит бизнес-логику;
- Controller принимает параметры и формирует транспортный ответ;
- Service содержит бизнес-правила;
- Repository работает с PostgreSQL и кэшем;
- `ContentSourceInterface` скрывает конкретный внешний источник;
- публичные endpoints проектируются под сценарии GexusFilm, а не копируют структуру TMDB;
- один endpoint может агрегировать несколько запросов источника в единый ответ.

На текущем этапе маршруты зарегистрированы в `backend/src/routes.php`. При росте API их планируется разделить на модули `movies`, `tv`, `search`, `people`, `auth` и `lists` без изменения публичных URL.

## Структура проекта

```text
.
├── backend/
│   ├── public/index.php           # front-controller
│   ├── src/
│   │   ├── Router.php             # HTTP-маршрутизация
│   │   ├── routes.php             # регистрация REST-маршрутов
│   │   ├── TmdbClient.php         # клиент TMDB
│   │   ├── Service/               # бизнес-логика и ContentSourceInterface
│   │   ├── Repository/            # доступ к PostgreSQL и кэшу
│   │   └── Http/Controllers/      # HTTP-контроллеры
│   ├── tests/                     # PHPUnit-тесты
│   ├── database.sql               # схема PostgreSQL
│   ├── composer.json              # PHP-зависимости и scripts
│   ├── phpunit.xml
│   └── phpcs.xml
├── frontend/
│   ├── src/
│   │   ├── pages/                 # Home, Catalog и Media
│   │   ├── components/            # MediaCard, MediaRow, MediaHero, SeasonList
│   │   └── services/api.js        # единый Axios-клиент
│   ├── package.json
│   └── vite.config.js
├── data/                          # локальные данные проекта
├── docker-compose.yml             # PostgreSQL для разработки
└── README.md
```

## Быстрый запуск

### Требования

- PHP 8.1+ с расширениями `pdo`, `pdo_pgsql`, `curl` и `mbstring`;
- Composer 2.x;
- Node.js 18+ и npm;
- Docker и Docker Compose;
- API-ключ TMDB.

### 1. Клонировать репозиторий

```bash
git clone https://github.com/ZordonLord/GexusFilm.git
cd GexusFilm
```

### 2. Настроить backend

```bash
cp backend/.env.example backend/.env
```

В Windows PowerShell:

```powershell
Copy-Item backend\.env.example backend\.env
```

Откройте `backend/.env` и укажите как минимум `TMDB_API_KEY`. Для локального Docker Compose подходят параметры:

```env
TMDB_API_KEY=your_tmdb_api_key

DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=DATABASE
DB_USERNAME=USERNAME
DB_PASSWORD=PASSWORD

CACHE_TTL_MINUTES=1440
```

### 3. Запустить PostgreSQL

```bash
docker compose up -d postgres
```

Схема из `backend/database.sql` применяется автоматически при первом создании Docker volume.

### 4. Установить PHP-зависимости и запустить API

```bash
cd backend
composer install
php -S 127.0.0.1:8000 -t public public/index.php
```

`public/index.php` нужно передать как router script PHP-сервера, чтобы работали REST-маршруты без суффикса `.php`.

### 5. Установить frontend-зависимости и запустить Vite

В новом терминале из корня проекта:

```bash
cd frontend
npm install
npm run dev
```

Откройте адрес, который выведет Vite, обычно `http://localhost:5173`.

В режиме разработки Vite проксирует `/api` на `http://localhost:8000`. Для другого backend можно задать:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000
```

## API

Все endpoints используют `GET` и возвращают JSON.

### Фильмы

| Endpoint | Назначение |
| --- | --- |
| `/api/movies/trending` | Трендовые фильмы |
| `/api/movies/popular` | Популярные фильмы |
| `/api/movies/now-playing` | Фильмы в кинотеатрах сейчас |
| `/api/movies/upcoming` | Предстоящие релизы |
| `/api/movies/{id}` | Детали фильма |

### Сериалы

| Endpoint | Назначение |
| --- | --- |
| `/api/tv-shows/trending` | Трендовые сериалы |
| `/api/tv-shows/popular` | Популярные сериалы |
| `/api/tv-shows/on-the-air` | Сериалы в эфире |
| `/api/tv-shows/airing-today` | Сериалы, выходящие сегодня |
| `/api/tv-shows/{id}` | Детали сериала |
| `/api/tv-shows/{id}/season/{number}` | Сезон и эпизоды |

### Общие endpoints

| Endpoint | Пример параметров | Назначение |
| --- | --- | --- |
| `/api/trending` | `?type=tv` | Тренды фильмов или сериалов |
| `/api/genres` | `?type=movie` или `?type=tv` | Жанры |
| `/api/search` | `?q=matrix&type=movie` | Поиск по названию |
| `/api/discover` | `?type=movie&genre_id=28&page=1` | Подборка по фильтрам |

Суффикс `.php` нормализуется front-controller для обратной совместимости, но отдельные HTTP 301 redirect-маршруты не заявляются.

Примеры запросов:

```bash
curl http://127.0.0.1:8000/api/movies/popular
curl "http://127.0.0.1:8000/api/movies/603"
curl "http://127.0.0.1:8000/api/search?q=matrix&type=movie"
curl "http://127.0.0.1:8000/api/discover?type=tv&genre_id=18&page=1"
```

## Проверки

### Backend

```bash
cd backend
composer test       # PHPUnit
composer lint       # PHP_CodeSniffer
composer lint-fix   # автоматическое исправление стиля
```

### Frontend

```bash
cd frontend
npm run lint
npm run build
```

## План развития

### v0.3 — Поиск и фильтры

1. Добавить Meilisearch для полнотекстового поиска и фасетных фильтров.
2. Добавить Redis для горячего кэша, единых TTL/ключей и rate limit.
3. Реализовать pipeline PostgreSQL → Meilisearch с идемпотентной индексацией.
4. Расширить Search/Discover API сортировкой, пагинацией, валидацией и единым форматом ошибок.
5. Добавить SearchPage, debounce, URL-состояние и loading/empty/error states.
6. Перенести frontend-стили с Tailwind на CSS Modules.

### v0.4–v0.6 — Пользовательские сценарии

- **v0.4:** регистрация, вход, auth middleware, профиль и безопасное хранение паролей.
- **v0.5:** избранное, watch later, пользовательские списки, оценки, прогресс и watch providers.
- **v0.6:** страницы персон, credits, videos, images, similar, recommendations и UX-полировка.

### v1.0 — Production-ready

- CI/CD, unit/integration/e2e smoke-тесты;
- логирование, мониторинг и резервные копии PostgreSQL;
- HTTPS, production-конфигурация и воспроизводимый deploy;
- PWA, базовый offline shell, RU/EN и SEO/OG.

AI-поиск, мульти-источник, социальные функции и монетизация остаются дальним планом и не входят в текущий MVP.

## Конфигурация и безопасность

- не добавляйте `backend/.env`, `backend/vendor/`, `frontend/node_modules/` и локальные логи в Git;
- не размещайте TMDB API key во frontend;
- храните секреты только в переменных окружения или секрет-хранилище deployment-среды;
- перед production-публикацией замените тестовые пароли PostgreSQL и настройте HTTPS;
- PostgreSQL является источником долговременных данных, а Redis и Meilisearch в будущем будут производными/временными хранилищами.

## Документация

Актуальные продуктовые и архитектурные правила ведутся в Obsidian:

- ProductBacklog — задачи и критерии готовности;
- TODO — текущий оперативный фокус;
- Roadmap — версии и этапы;
- Changelog — история завершённых изменений;
- Decisions — принятые архитектурные решения;
- Architecture, API Standards и Database Design — архитектура и контракты.

## Лицензия

Проект находится в разработке. Лицензия для публичного использования пока не определена.