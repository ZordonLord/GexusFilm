# GexusFilm

Киноагрегатор для поиска и выбора фильмов и сериалов. На текущем этапе проект
сохраняет простую цепочку данных:

```text
Frontend → PHP API → PostgreSQL → TMDB
```

PostgreSQL используется как долговременное хранилище медиа и TTL-кэш ответов
TMDB.

> **Текущий статус:** базовый каталог фильмов и сериалов, поиск/discover, общий
> Media UI, единый API-клиент и REST-маршрутизация работают. Следующий фокус —
> пользовательские сценарии и качество MVP без преждевременного усложнения.

## Возможности

- каталоги фильмов: trending, popular, now playing и upcoming;
- каталоги сериалов: trending, popular, on the air и airing today;
- страницы деталей фильма и сериала, сезоны и эпизоды;
- поиск и discover через PostgreSQL с fallback к TMDB;
- PostgreSQL-кэш ответов TMDB с TTL;
- сохранение сводных и детальных данных медиа в PostgreSQL;
- локальная защита TMDB: singleflight, ограничение запросов и circuit breaker;
- единый frontend Media UI, front-controller и параметризованный Router.

## Стек

| Область | Технологии |
| --- | --- |
| Frontend | React 19, React Router 7, Vite 8, Axios |
| Backend | PHP 8.1+, Composer, PSR-4, собственный Router |
| Database | PostgreSQL 16 |
| External API | TMDB API через `ContentSourceInterface` и `TmdbClient` |
| Tests | PHPUnit 11 |
| Code quality | PHP_CodeSniffer 3.13, ESLint |
| Local infrastructure | Docker Compose |

CSS Modules и обычный CSS — целевой стандарт стилей frontend.

## Архитектура

```text
React/Vite frontend
        │ HTTP / JSON
        ▼
public/index.php → Router → Controller → Service
                                      ├── Repository → PostgreSQL
                                      │                 └── api_cache / movies
                                      └── ContentSourceInterface
                                                └── TmdbClient → TMDB
```

Основные принципы:

- `backend/public/index.php` — единая точка входа API;
- `Router` отвечает только за сопоставление HTTP-метода и пути;
- Controller принимает параметры и формирует транспортный ответ;
- Service содержит бизнес-правила и выбирает PostgreSQL либо внешний источник;
- Repository работает с PostgreSQL и не зависит от внешнего поискового сервиса;
- `ContentSourceInterface` скрывает конкретный внешний источник;
- PostgreSQL хранит медиа и долговременный TTL-кэш;
- недоступность PostgreSQL не скрывает TMDB: каталог может продолжить работу по
  внешнему источнику, если это позволяет конкретный сценарий;
- локальная защита TMDB не требует внешнего кэша и работает в пределах одного
  хоста.

### Будущая инфраструктура

Redis и Meilisearch намеренно исключены из текущего runtime, Compose, Composer и
переменных окружения. Вернуться к ним можно отдельными задачами после измерений
и появления сценариев, которые оправдывают усложнение:

- Redis — распределённый кэш, rate limit и auth-состояние при необходимости
  горизонтального масштабирования или пользовательских сессий;
- Meilisearch — полнотекстовый поиск и фасетные фильтры после подтверждения,
  что PostgreSQL-поиск перестал отвечать требованиям по скорости или качеству.

## Структура проекта

```text
.
├── backend/
│   ├── public/index.php           # front-controller
│   ├── src/
│   │   ├── Router.php             # HTTP-маршрутизация
│   │   ├── routes.php             # REST-маршруты
│   │   ├── TmdbClient.php         # клиент TMDB
│   │   ├── Service/               # бизнес-логика и ContentSourceInterface
│   │   ├── Repository/            # PostgreSQL и TTL-кэш
│   │   ├── Infrastructure/        # локальная защита TMDB
│   │   └── Http/Controllers/      # HTTP-контроллеры
│   ├── tests/                     # PHPUnit-тесты
│   ├── database.sql               # схема PostgreSQL
│   ├── composer.json              # PHP-зависимости и scripts
│   └── phpunit.xml
├── frontend/
│   ├── src/pages/                 # Home, Catalog, Search и Media
│   ├── src/components/            # общие Media-компоненты
│   ├── src/services/api.js        # единый Axios-клиент
│   └── package.json
├── docker-compose.yml             # только PostgreSQL
├── data/                          # локальные данные проекта
└── README.md
```

## Быстрый запуск

### Требования

- PHP 8.1+ с расширениями `pdo`, `pdo_pgsql`, `curl` и `mbstring`;
- Composer 2.x;
- Node.js 20.19+ или 22.12+ и npm;
- Docker и Docker Compose;
- API-ключ TMDB.

### 1. Настроить окружение

```bash
cp backend/.env.example backend/.env
cp .env.example .env
```

В Windows PowerShell:

```powershell
Copy-Item backend\.env.example backend\.env
Copy-Item .env.example .env
```

Укажите `TMDB_API_KEY` в `backend/.env`. Для локального Docker Compose
используются параметры PostgreSQL из примера:

```env
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gexusfilm
DB_USERNAME=gexusfilm
DB_PASSWORD=gexusfilm-local-password
```

### 2. Запустить PostgreSQL

```bash
docker compose up -d postgres
docker compose ps
```

Схема из `backend/database.sql` применяется автоматически при первом создании
Docker volume.

### 3. Запустить backend

```bash
cd backend
composer install
php -S 127.0.0.1:8000 -t public public/index.php
```

`public/index.php` передаётся как router script PHP-сервера, чтобы REST-маршруты
работали без суффикса `.php`.

### 4. Запустить frontend

В новом терминале из корня проекта:

```bash
cd frontend
npm install
npm run dev
```

По умолчанию Vite проксирует `/api` на `http://localhost:8000`. Для другого
backend создайте файл `frontend/.env.local` (он не попадёт в Git):

```env
VITE_API_BASE_URL=http://localhost:8001
```

Значение — это только origin backend без завершающего `/`; frontend сам добавит
путь `/api`. Например, запрос уйдёт на `http://localhost:8001/api/movies/popular`.
После изменения env-файла перезапустите `npm run dev`. В качестве шаблона можно
скопировать `frontend/.env.example`.

В Windows PowerShell:

```powershell
Copy-Item frontend\.env.example frontend\.env.local
```

## API

Все endpoints используют `GET` и возвращают JSON.

| Endpoint | Назначение |
| --- | --- |
| `/api/movies/trending` | Трендовые фильмы |
| `/api/movies/popular` | Популярные фильмы |
| `/api/movies/now-playing` | Фильмы в кинотеатрах сейчас |
| `/api/movies/upcoming` | Предстоящие фильмы |
| `/api/movies/{id}` | Детали фильма |
| `/api/tv-shows/trending` | Трендовые сериалы |
| `/api/tv-shows/popular` | Популярные сериалы |
| `/api/tv-shows/on-the-air` | Сериалы в эфире |
| `/api/tv-shows/airing-today` | Сериалы, выходящие сегодня |
| `/api/tv-shows/{id}` | Детали сериала |
| `/api/tv-shows/{id}/season/{number}` | Сезон и эпизоды |
| `/api/trending?type=tv` | Общие тренды |
| `/api/genres?type=movie` | Жанры |
| `/api/search?q=matrix&type=movie` | Поиск |
| `/api/discover?type=movie&genre_id=28&page=1` | Подборка по фильтрам |
| `/api/health` | Состояние PostgreSQL/TMDB-контура |

Поиск сначала проверяет PostgreSQL, а при отсутствии подходящих локальных
данных обращается к TMDB и сохраняет результат в PostgreSQL. Ответ сохраняет
совместимый формат `page/results/total_pages/total_results`.

## Проверки

### Backend

```bash
cd backend
composer test
composer lint
composer audit
```

Или из корня:

```bash
composer --working-dir=backend test
composer --working-dir=backend lint
```

### Frontend

```bash
cd frontend
npm run lint
npm run build
```

## План развития

### Ближайший фокус

1. Стабилизировать каталог, поиск и discover на связке
   `frontend → PostgreSQL → TMDB`.
2. Добавить аккаунты и пользовательские данные только после фиксации auth-модели.
3. Добавить списки, оценки, watch providers и расширенные детали TMDB.
4. Измерить реальные узкие места до добавления новых инфраструктурных сервисов.

### Отложенная инфраструктура

- **Redis:** вернуть в план при необходимости распределённого кэша, auth-состояния
  или rate limit между несколькими backend-инстансами.
- **Meilisearch:** вернуть в план при доказанной недостаточной скорости/качестве
  PostgreSQL-поиска и готовой модели индексации.

### Дальнейший план

- CI/CD, мониторинг, резервные копии PostgreSQL, PWA, i18n и SEO;
- персоны, credits, videos, similar и рекомендации через `ContentSourceInterface`;
- AI-поиск, мульти-источник, социальные функции и монетизация — только после
  проверки ценности основного сценария.

## Конфигурация и безопасность

- не добавляйте `backend/.env`, `backend/vendor/`, `frontend/node_modules/` и
  локальные логи в Git;
- не размещайте TMDB API key во frontend;
- храните секреты только в переменных окружения или secret manager;
- не отключайте TLS-проверку TMDB;
- замените локальные пароли PostgreSQL перед production;
- `docker compose down -v` удаляет named volume PostgreSQL и может уничтожить
  локальные данные.

## Документация

Актуальные продуктовые и архитектурные правила ведутся в Obsidian:

- ProductBacklog — задачи и критерии готовности;
- TODO — текущий оперативный фокус;
- Roadmap — версии и этапы;
- Changelog — история изменений;
- Decisions — принятые архитектурные решения;
- Architecture, API Standards и Database Design — архитектура и контракты.

## Лицензия

Проект находится в разработке. Лицензия для публичного использования пока не
определена.