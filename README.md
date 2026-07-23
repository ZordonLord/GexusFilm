# GexusFilm

GexusFilm — киноагрегатор с фронтендом на React/Vite и бекендом на PHP. Приложение показывает трендовые фильмы, популярные релизы, сейчас идущие киносеансы и будущие премьеры. В текущей версии (v0.2) backend перешёл на единый front-controller с роутером и PSR-4 автозагрузку через Composer.

## Стек

- **Frontend:** React 19, Vite 5, React Router 7, CSS Modules
- **Backend:** PHP 8.1+, Composer, PSR-4 автозагрузка (`App\`)
- **Database:** PostgreSQL 16 (кэш TMDB)
- **API:** TMDB (The Movie Database)
- **Dev-инструменты:** PHPUnit 11, PHP_CodeSniffer 3.9 (PSR-12)

## Структура проекта

```text
backend/
  composer.json         # зависимости PHP + PSR-4 автозагрузка
  composer.lock         # lock-файл зависимостей
  phpunit.xml           # конфиг PHPUnit
  phpcs.xml             # конфиг PHP_CodeSniffer (PSR-12)
  public/
    index.php           # единый front-controller / роутер
    api/                # прокси-файлы для обратной совместимости URL
  src/
    bootstrap.php       # автозагрузка, хелперы
    config.php          # загрузка .env
    routes.php          # маршруты + closure-обработчики
    Router.php          # лёгкий роутер
    Response.php        # формирование JSON-ответов
    Database.php        # PDO → PostgreSQL
    MovieRepository.php # кэш и persistence
    TmdbClient.php      # клиент TMDB API
  tests/                # unit-тесты (PHPUnit)
  database.sql          # схема PostgreSQL
  .env.example          # пример конфигурации
frontend/
  src/
    App.jsx
    main.jsx
    pages/
    components/
    services/
  package.json
  vite.config.js
  eslint.config.js
docker-compose.yml      # PostgreSQL для локальной разработки
```

## Что работает

- Загрузка трендовых, популярных, сейчас идущих и будущих фильмов
- Поиск фильмов по названию
- Страница фильма с деталями
- Кэширование API-ответов TMDB в PostgreSQL
- Единый front-controller с маршрутизацией
- PSR-4 автозагрузка классов через Composer
- Unit-тесты и линтинг PHP

## Подготовка окружения

### Требования

- PHP 8.1+ с расширениями: `pdo`, `pdo_pgsql`, `curl`, `mbstring`
- Composer 2.x
- Node.js 18+ и npm
- Docker (для PostgreSQL)

### Проверка PHP-расширений

```powershell
php -m
```

Убедитесь, что в списке есть `pdo_pgsql`. Если нет — включите его в `php.ini`.

## Настройка backend

1. Скопируйте пример конфигурации:

```powershell
Copy-Item backend\.env.example backend\.env
```

2. Откройте `backend/.env` и задайте ключ TMDB и параметры базы данных:

```env
TMDB_API_KEY=your_tmdb_api_key

DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=zordonfilm
DB_USERNAME=zordonfilm
DB_PASSWORD=zordonfilm

CACHE_TTL_MINUTES=1440
```

3. Установите PHP-зависимости:

```powershell
cd backend
composer install
```

## Запуск PostgreSQL

```powershell
docker compose up -d postgres
```

При первом запуске Docker автоматически применит схему из `backend/database.sql`.

## Запуск backend (локальная разработка)

```powershell
php -S 127.0.0.1:8000 -t backend/public backend/public/index.php
```

> Важно: используйте `backend/public/index.php` как router script, иначе маршруты без `.php` не будут работать.

После этого API доступны по адресам:

- `http://127.0.0.1:8000/api/trending`
- `http://127.0.0.1:8000/api/movies`
- `http://127.0.0.1:8000/api/now-playing`
- `http://127.0.0.1:8000/api/upcoming`
- `http://127.0.0.1:8000/api/genres`
- `http://127.0.0.1:8000/api/movie?id=603`
- `http://127.0.0.1:8000/api/search?q=matrix`
- `http://127.0.0.1:8000/api/discover?genre_id=28`

Старые URL с `.php` также продолжают работать для обратной совместимости:

- `http://127.0.0.1:8000/api/movies.php`
- `http://127.0.0.1:8000/api/movie.php?id=603`

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

> В `frontend/src/services/api.js` параметр `API_BASE` по умолчанию указывает на `http://138.124.240.208:8000/api`. Для локальной разработки замените его на `http://127.0.0.1:8000/api`.

## Тесты и линтинг backend

```powershell
cd backend

# Unit-тесты
composer test

# Линтинг PHP по PSR-12
composer lint

# Автоисправление стиля
composer lint-fix
```

## Запуск на сервере (production)

### 1. Клонирование и зависимости

```bash
git clone https://github.com/ZordonLord/ZordonFilm.git
cd ZordonFilm/backend
composer install --no-dev --optimize-autoloader
```

> Для production рекомендуется `--no-dev`. Dev-зависимости (PHPUnit, PHPCS) не нужны на сервере.

### 2. Конфигурация

```bash
cp .env.example .env
nano .env
```

Задайте:

- `TMDB_API_KEY` — ключ от TMDB
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — параметры PostgreSQL
- `CACHE_TTL_MINUTES` — TTL кэша

### 3. База данных

Убедитесь, что PostgreSQL доступен, и примените `database.sql`:

```bash
psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE -f database.sql
```

### 4. Nginx + PHP-FPM

Пример конфигурации Nginx:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/ZordonFilm/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000; # или unix:/run/php/php-fpm.sock
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

> `try_files` перенаправляет все запросы в `index.php` — единый front-controller.

### 5. Frontend

Соберите статику:

```bash
cd ../frontend
npm install
npm run build
```

Разместите содержимое `frontend/dist/` за Nginx или CDN.

### 6. Права

```bash
chmod -R 755 /var/www/ZordonFilm/backend
```

## Как работает кэширование

1. Фронтенд запрашивает данные у PHP API.
2. Backend проверяет PostgreSQL-кэш (`api_cache`).
3. Если кэш действителен, данные возвращаются из PostgreSQL.
4. Если нет — backend запрашивает TMDB.
5. Ответ сохраняется в PostgreSQL и возвращается клиенту.

## Исключения из репозитория

В репозиторий не должны попадать:

- `backend/.env`
- `api.txt`
- `node_modules/`
- `backend/vendor/`
- локальные файлы, логи и кэши

Пример конфигурации хранится в `backend/.env.example`.

## Документация проекта

Подробная документация живёт в Obsidian-ваулте проекта:

- `01_Projects/GexusFilm/ProductVision.md`
- `01_Projects/GexusFilm/Roadmap.md`
- `01_Projects/GexusFilm/ProductBacklog.md`
- `01_Projects/GexusFilm/Architecture.md`
- `01_Projects/GexusFilm/TechStack.md`
- `01_Projects/GexusFilm/DevelopmentRules.md`
- `01_Projects/GexusFilm/CodingStandards.md`
- `01_Projects/GexusFilm/APIStandards.md`
- `01_Projects/GexusFilm/DatabaseDesign.md`
