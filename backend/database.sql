CREATE TABLE IF NOT EXISTS movies (
    id BIGSERIAL PRIMARY KEY,
    tmdb_id INTEGER NOT NULL,
    media_type VARCHAR(10) NOT NULL DEFAULT 'movie',
    title TEXT,
    original_title TEXT,
    overview TEXT,
    poster_path TEXT,
    backdrop_path TEXT,
    release_date DATE,
    runtime INTEGER,
    vote_average NUMERIC(3, 1),
    popularity NUMERIC(10, 3),
    genre_ids JSONB NOT NULL DEFAULT '[]'::jsonb,
    genres JSONB NOT NULL DEFAULT '[]'::jsonb,
    tmdb_payload JSONB NOT NULL,
    has_details BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(tmdb_id, media_type)
);

CREATE INDEX IF NOT EXISTS movies_title_idx ON movies USING GIN (to_tsvector('simple', COALESCE(title, '')));
CREATE INDEX IF NOT EXISTS movies_popularity_idx ON movies (popularity DESC);

CREATE TABLE IF NOT EXISTS api_cache (
    cache_key TEXT PRIMARY KEY,
    response JSONB NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS api_cache_expires_at_idx ON api_cache (expires_at);

CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    display_name VARCHAR(80),
    region VARCHAR(5) NOT NULL DEFAULT 'RU',
    locale VARCHAR(10) NOT NULL DEFAULT 'ru-RU',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT users_email_lowercase CHECK (email = lower(email))
);

CREATE TABLE IF NOT EXISTS user_lists (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    list_type VARCHAR(20) NOT NULL
        CHECK (list_type IN ('favorites', 'watch_later')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE UNIQUE INDEX IF NOT EXISTS user_lists_user_type_unique_idx
    ON user_lists (user_id, list_type);
CREATE INDEX IF NOT EXISTS user_lists_user_idx ON user_lists (user_id);

CREATE TABLE IF NOT EXISTS list_items (
    id BIGSERIAL PRIMARY KEY,
    list_id BIGINT NOT NULL REFERENCES user_lists(id) ON DELETE CASCADE,
    media_type VARCHAR(10) NOT NULL CHECK (media_type IN ('movie', 'tv')),
    source VARCHAR(20) NOT NULL DEFAULT 'tmdb',
    source_id BIGINT NOT NULL CHECK (source_id > 0),
    added_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (list_id, media_type, source, source_id)
);
CREATE INDEX IF NOT EXISTS list_items_list_idx ON list_items (list_id, added_at DESC);

CREATE TABLE IF NOT EXISTS user_media_history (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    media_type VARCHAR(10) NOT NULL CHECK (media_type IN ('movie', 'tv')),
    source VARCHAR(20) NOT NULL DEFAULT 'tmdb',
    source_id BIGINT NOT NULL CHECK (source_id > 0),
    first_viewed_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    last_viewed_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, media_type, source, source_id)
);
CREATE INDEX IF NOT EXISTS user_media_history_recent_idx
    ON user_media_history (user_id, last_viewed_at DESC, id DESC);