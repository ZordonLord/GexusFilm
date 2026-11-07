DROP INDEX IF EXISTS user_system_list_idx;

CREATE UNIQUE INDEX IF NOT EXISTS user_lists_user_type_unique_idx
    ON user_lists (user_id, list_type);