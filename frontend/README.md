# GexusFilm Frontend

React/Vite frontend проекта GexusFilm. Основное описание архитектуры, настройки
окружения и локального/production-запуска находится в корневом [`README.md`](../README.md).

## Команды

Из каталога `frontend/`:

```bash
npm install
npm run dev
npm test
npm run lint
npm run build
```

Vite в dev-режиме проксирует `/api` на локальный backend. Не размещайте TMDB
credentials или другие секреты в `VITE_*`: переменные Vite встраиваются в
публичный клиентский bundle.