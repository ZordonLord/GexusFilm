CREATE TABLE IF NOT EXISTS user_lists (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    list_type VARCHAR(20) NOT NULL
        CHECK (list_type IN ('favorites', 'watch_later')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS user_system_list_idx
    ON user_lists (user_id, list_type)
    WHERE list_type IN ('favorites', 'watch_later');
CREATE UNIQUE INDEX IF NOT EXISTS user_system_list_idx
    ON user_lists (user_id, list_type)
    WHERE list_type IN ('favorites', 'watch_later');
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