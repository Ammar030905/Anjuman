-- ============================================================
-- Anjuman E Ezzy - Supabase / PostgreSQL Schema
-- ============================================================

BEGIN;

CREATE TABLE IF NOT EXISTS users (
  id BIGSERIAL PRIMARY KEY,
  its_number CHAR(8) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(10) NOT NULL DEFAULT 'user' CHECK (role IN ('admin', 'user')),
  status SMALLINT NOT NULL DEFAULT 1 CHECK (status IN (0, 1)),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_users_phone ON users (phone);
CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);
CREATE INDEX IF NOT EXISTS idx_users_status ON users (status);

CREATE TABLE IF NOT EXISTS streams (
  id BIGSERIAL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  youtube_url VARCHAR(500) NOT NULL,
  youtube_video_id VARCHAR(20) NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'offline' CHECK (status IN ('live', 'offline')),
  created_by BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_streams_status ON streams (status);
CREATE INDEX IF NOT EXISTS idx_streams_created_by ON streams (created_by);
CREATE INDEX IF NOT EXISTS idx_streams_created_at ON streams (created_at);

CREATE TABLE IF NOT EXISTS activity_logs (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NULL REFERENCES users (id) ON DELETE SET NULL,
  action VARCHAR(255) NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(500) DEFAULT NULL,
  timestamp TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_activity_user_id ON activity_logs (user_id);
CREATE INDEX IF NOT EXISTS idx_activity_timestamp ON activity_logs (timestamp);

INSERT INTO users (its_number, name, phone, password, role, status)
VALUES ('12345678', 'Super Admin', '9876543210', '$2b$12$FF8xMLFxUlhVkImu9jqUgeVtR5ndxYq9rgGxdU/vcObgFdcZrO45C', 'admin', 1)
ON CONFLICT (its_number) DO NOTHING;

COMMIT;