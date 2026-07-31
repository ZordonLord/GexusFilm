# GX-08 — Рефакторинг маршрутов API: REST-стиль и консолидация

> **Идентификатор:** GX-08
> **Приоритет:** 🔴 high
> **Эпик:** A — Структурный backend (v0.2 → v0.3)
> **Статус:** ⬜ to do

---

## Перед началом — обязательно прочитать

Согласно [[AI_AGENTS.md]] и [[DevelopmentRules]]:

1. **Obsidian (ZordonLordVault):**
   - `00_System/AI Instructions.md` — глобальные правила
   - `01_Projects/GexusFilm/APIStandards.md` — целевая карта эндпоинтов (раздел 10.2) и раздел 9 (совместимость)
   - `01_Projects/GexusFilm/Architecture.md` — слои и архитектура
   - `01_Projects/GexusFilm/CodingStandards.md` — PSR-12, camelCase, snake_case
   - `01_Projects/GexusFilm/DevelopmentRules.md` — DoD, Git, документация
   - `01_Projects/GexusFilm/Архитектура API GexusFilm.md` — принципы проектирования API
   - `01_Projects/GexusFilm/TODO.md` — оперативный трекер
   - `01_Projects/GexusFilm/ProductBacklog.md` — бэклог

2. **Код проекта:**
   - `backend/src/routes.php` — текущие маршруты (125 строк, 17 closure-обработчиков)
   - `backend/src/Router.php` — текущий роутер (только точное совпадение, без `{param}`)
   - `backend/src/Http/Controllers/` — контроллеры (не менять сигнатуры!)
   - `backend/src/Service/` — сервисы
   - `frontend/src/services/api.js` — API-клиент фронтенда (нужно обновить пути)

---

## Описание

Привести `backend/src/routes.php` в соответствие с целевой картой эндпоинтов [[APIStandards]] (раздел 10.2), при этом сохранив обратную совместимость для всех текущих URL (раздел 9).

**Корневая проблема:** Текущий `routes.php` — это "v0.1 legacy", не рефакторившийся вместе с GX-01..GX-07. Содержит 17 closure-обработчиков с прямым доступом к `$_GET`, дублированием логики и не-REST путями.

**Ключевое требование:** Старые URL должны продолжать работать (через 301 редиректы).

---

## Зависимости

- ✅ GX-01 — Router существует
- ✅ GX-03 — Service-слой (MovieService/TvService) готов
- ✅ GX-05 — TV-эндпоинты работают
- ✅ GX-07 — Frontend использует Router API

---

## Текущее состояние (17 маршрутов)

```
Movies:
  GET /api/trending          → MovieController::getTrending()
  GET /api/movies            → MovieController::getPopular()
  GET /api/now-playing       → MovieController::getNowPlaying()
  GET /api/upcoming          → MovieController::getUpcoming()
  GET /api/genres            → MovieController::getGenres()
  GET /api/movie?id={id}     → MovieController::getMovie($id)
  GET /api/search?q={query}  → MovieController::search($query)
  GET /api/discover?...      → MovieController::discover($params)

TV:
  GET /api/tv/trending       → TvController::getTrending()
  GET /api/tv/popular        → TvController::getPopular()
  GET /api/tv/on-the-air     → TvController::getOnTheAir()
  GET /api/tv/airing-today   → TvController::getAiringToday()
  GET /api/tv/genres         → TvController::getGenres()
  GET /api/tv-shows?id={id}  → TvController::getTv($id) | getPopular()
  GET /api/tv-shows/season?series_id=&season_number= → TvController::getSeason(...)
  GET /api/tv/search?q=...   → TvController::search($query)
  GET /api/tv/discover?...   → TvController::discover($params)
```

---

## Целевое состояние (после рефакторинга)

### Movies (группированы под `/api/movies/`)
```
GET /api/movies/trending?window=day|week         (было /api/trending)
GET /api/movies/popular                          (было /api/movies)
GET /api/movies/now-playing                      (было /api/now-playing)
GET /api/movies/upcoming                         (было /api/upcoming)
GET /api/movies/{id}                             (было /api/movie?id=)
```

### TV Shows (группированы под `/api/tv-shows/`)
```
GET /api/tv-shows/trending?window=day|week       (было /api/tv/trending)
GET /api/tv-shows/popular                        (было /api/tv/popular)
GET /api/tv-shows/on-the-air                     (было /api/tv/on-the-air)
GET /api/tv-shows/airing-today                   (было /api/tv/airing-today)
GET /api/tv-shows/{id}                           (было /api/tv-shows?id=)
GET /api/tv-shows/{id}/season/{number}           (было /api/tv-shows/season?series_id=&season_number=)
```

### Общие (консолидированные — единый эндпоинт для movie+tv)
```
GET /api/genres?type=movie|tv                    (было /api/genres + /api/tv/genres)
GET /api/trending?type=all|movie|tv&window=day|week  (было /api/trending + /api/tv/trending)
GET /api/search?q=&type=movie|tv                 (было /api/search + /api/tv/search)
GET /api/discover?type=movie|tv&genre=&year=&sort_by=  (было /api/discover + /api/tv/discover)
```

---

## Критерии завершения (Definition of Done)

- [ ] **Router.php** расширен: поддерживает паттерны `{param}` в путях
- [ ] Все новые URL работают и возвращают корректные данные
- [ ] Старые URL продолжают работать (301-редирект на новые)
- [ ] Дублирующие эндпоинты (search, discover, genres, trending) консолидированы
- [ ] Обработчики в routes.php не содержат прямых обращений к `$_GET` (параметры извлекаются Router'ом)
- [ ] Ответы соответствуют [[APIStandards]] (формат `{data, meta}` для коллекций, единый формат ошибок)
- [ ] `phpcs` проходит без ошибок
- [ ] Тесты (`phpunit`) проходят; добавлены тесты на новые маршруты (happy-path минимум)
- [ ] `services/api.js` фронтенда обновлён (если используются старые пути)
- [ ] [[APIStandards]] обновлён: раздел 10.1 (текущие) исправлен на фактические новые URL
- [ ] В `Changelog/` создана заметка `GX-08-routes-refactor.md`
- [ ] `TODO.md` обновлён (GX-08 → ✅)
- [ ] `ProductBacklog.md` обновлён (GX-08 добавлен в EPIC A)

---

## Инструкции по выполнению

### Этап 1: Расширить Router (GX-08.1)

**Файл:** `backend/src/Router.php`

Добавить поддержку параметризованных маршрутов. Сейчас `dispatch()` делает точное совпадение `$this->routes[$method][$path]`. Нужно:

1. Хранить маршруты как массив `[{pattern, regex, paramNames, handler}]`
2. При регистрации `/api/movies/{id}` компилировать в regex `#^/api/movies/([^/]+)$#`
3. При dispatch — искать совпадение по regex, извлекать параметры
4. Передавать параметры в обработчик: `$handler($params)` вместо `$handler()`
5. Сохранить обратную совместимость: точные совпадения без `{param}` должны работать как раньше
6. Собирать `$_GET` query-параметры и передавать вместе с path-параметрами

```php
// Целевой интерфейс Router:
$router->get('/api/movies/{id}', function (array $params) use ($movieController) {
    $id = (int) ($params['id'] ?? 0);
    json_response($movieController->getMovie($id));
});
```

### Этап 2: Консолидировать дублирующие эндпоинты (GX-08.2)

Объединить пары movie/tv эндпоинтов. Создать единые обработчики, которые диспатчат по параметру `type`:

```php
$router->get('/api/genres', function (array $params) use ($movieController, $tvController) {
    $type = $params['type'] ?? 'movie';
    $data = $type === 'tv' ? $tvController->getGenres() : $movieController->getGenres();
    json_response($data);
});
```

### Этап 3: REST-пути (GX-08.3)

Переименовать пути согласно целевой карте. Группировать под ресурсами.

### Этап 4: Обратная совместимость (GX-08.4)

Все старые URL должны отдавать **301 Moved Permanently** на новые:

```php
$router->get('/api/movie', function () {
    $id = $_GET['id'] ?? 0;
    header('Location: /api/movies/' . $id, true, 301);
    exit;
});
```

### Этап 5: Вынести извлечение параметров (GX-08.5)

Router должен извлекать query-параметры и передавать их вместе с path-параметрами. Обработчики не должны напрямую обращаться к `$_GET`.

### Этап 6: Обновить тесты и документацию (GX-08.6)

1. Запустить `composer test` — убедиться, что существующие тесты проходят
2. Добавить тесты на новые маршруты в `tests/`
3. Обновить `services/api.js` во фронтенде (если пути изменились)
4. Создать `01_Projects/GexusFilm/Changelog/GX-08-routes-refactor.md`
5. Обновить `APIStandards.md` (раздел 10.1 — привести к фактическому состоянию)
6. Обновить `TODO.md` (отметить GX-08 как ✅)
7. Обновить `ProductBacklog.md` (добавить GX-08 в EPIC A)

---

## Важно

- **Не ломать существующие тесты**
- **Следовать PSR-12** (`phpcs.xml` в корне backend)
- **Не менять сигнатуры контроллеров/сервисов** — только routes.php + Router.php
- **Не дублировать логику** — если две ветки делают одно и то же, вынести в общий обработчик
- **Перед началом** — прочитать документацию в Obsidian (см. список выше)
- **После завершения** — обновить документацию согласно [[DevelopmentRules]] п.6

---

## Git

- Ветка: `refactor/GX-08-routes-refactor`
- Коммиты по Conventional Commits: `feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`
- Каждый коммит ссылается на ID задачи: `refactor(routes): add {param} pattern support to Router (GX-08)`
- PR не мержится без прохождения линтеров/тестов и обновления документации

---

## Связанные документы

- [[APIStandards]] — целевая карта 10.2, раздел 9 (совместимость)
- [[Architecture]] — слои Controller→Service→Repository
- [[CodingStandards]] — PSR-12, camelCase, snake_case
- [[DevelopmentRules]] — Definition of Done, Git, документация
- [[Архитектура API GexusFilm]] — принципы проектирования API