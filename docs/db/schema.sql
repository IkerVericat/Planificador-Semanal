-- =====================================================================
-- Planificador Semanal · Esquema de base de datos (PostgreSQL 15+)
-- App multitenant: todos los datos de usuario llevan user_id.
-- =====================================================================

CREATE EXTENSION IF NOT EXISTS pgcrypto;   -- gen_random_uuid()
CREATE EXTENSION IF NOT EXISTS citext;     -- email sin distinguir mayúsculas

-- ---------------------------------------------------------------------
-- Utilidad: mantener updated_at al día
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger AS $$
BEGIN
  NEW.updated_at := now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ---------------------------------------------------------------------
-- users · pantallas "Crea tu cuenta" e "Iniciar sesión"
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id             uuid        PRIMARY KEY DEFAULT gen_random_uuid(),
  name           text        NOT NULL CHECK (length(trim(name)) > 0),
  email          citext      NOT NULL UNIQUE,
  password_hash  text        NOT NULL,              -- bcrypt/argon2, nunca la contraseña
  timezone       text        NOT NULL DEFAULT 'Europe/Madrid',
  week_starts_on smallint    NOT NULL DEFAULT 1 CHECK (week_starts_on BETWEEN 0 AND 6), -- 1 = lunes
  default_view   text        NOT NULL DEFAULT 'week' CHECK (default_view IN ('week','month','year')),
  created_at     timestamptz NOT NULL DEFAULT now(),
  updated_at     timestamptz NOT NULL DEFAULT now()
);
CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON users
  FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ---------------------------------------------------------------------
-- password_reset_tokens · "¿Olvidaste tu contraseña?" + "Continuar"
-- ---------------------------------------------------------------------
CREATE TABLE password_reset_tokens (
  id          uuid        PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id     uuid        NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token_hash  text        NOT NULL UNIQUE,          -- se guarda el hash del token enviado por correo
  expires_at  timestamptz NOT NULL,
  used_at     timestamptz,
  created_at  timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX idx_password_reset_tokens_user ON password_reset_tokens (user_id);

-- ---------------------------------------------------------------------
-- sessions · sesiones de inicio de sesión (opcional si se usa JWT)
-- ---------------------------------------------------------------------
CREATE TABLE sessions (
  id          uuid        PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id     uuid        NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token_hash  text        NOT NULL UNIQUE,
  user_agent  text,
  ip_address  inet,
  expires_at  timestamptz NOT NULL,
  created_at  timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX idx_sessions_user ON sessions (user_id);

-- ---------------------------------------------------------------------
-- categories · etiquetas de color: FIJO, URGENTE, IMPORTANTE, NUEVA
-- Cada usuario tiene las suyas (se crean por defecto al registrarse).
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id          uuid        PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id     uuid        NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  name        text        NOT NULL CHECK (length(trim(name)) > 0),
  color       char(7)     NOT NULL CHECK (color ~ '^#[0-9A-Fa-f]{6}$'),
  position    smallint    NOT NULL DEFAULT 0,       -- orden en la barra de etiquetas
  created_at  timestamptz NOT NULL DEFAULT now(),
  updated_at  timestamptz NOT NULL DEFAULT now(),
  UNIQUE (user_id, name),
  UNIQUE (user_id, id)                              -- permite FK compuesta desde tasks
);
CREATE TRIGGER trg_categories_updated_at BEFORE UPDATE ON categories
  FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ---------------------------------------------------------------------
-- tasks · las tareas que aparecen en Semana / Mes / Año
-- Una tarea tiene fecha y hora de inicio (9 AM, 7:30 PM...) y una categoría.
-- Las tareas "fijas" pueden repetirse con recurrence (se expanden en la app).
-- ---------------------------------------------------------------------
CREATE TABLE tasks (
  id               uuid        PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id          uuid        NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  category_id      uuid,
  title            text        NOT NULL CHECK (length(trim(title)) > 0),
  notes            text,
  task_date        date        NOT NULL,
  start_time       time,                              -- NULL = tarea de todo el día
  end_time         time,
  is_completed     boolean     NOT NULL DEFAULT false,
  completed_at     timestamptz,
  recurrence       text        NOT NULL DEFAULT 'none'
                               CHECK (recurrence IN ('none','daily','weekly','monthly','yearly')),
  recurrence_days  smallint[],                        -- para 'weekly': 1=lunes … 7=domingo
  recurrence_until date,
  created_at       timestamptz NOT NULL DEFAULT now(),
  updated_at       timestamptz NOT NULL DEFAULT now(),
  -- La categoría tiene que ser del mismo usuario que la tarea
  FOREIGN KEY (user_id, category_id) REFERENCES categories (user_id, id) ON DELETE SET NULL (category_id),
  CHECK (end_time IS NULL OR (start_time IS NOT NULL AND end_time > start_time)),
  CHECK (recurrence_until IS NULL OR recurrence_until >= task_date),
  CHECK (recurrence_days IS NULL OR recurrence_days <@ ARRAY[1,2,3,4,5,6,7]::smallint[])
);
CREATE TRIGGER trg_tasks_updated_at BEFORE UPDATE ON tasks
  FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Consultas por rango de fechas (semana, mes, año) de un usuario
CREATE INDEX idx_tasks_user_date ON tasks (user_id, task_date, start_time);
CREATE INDEX idx_tasks_user_recurring ON tasks (user_id) WHERE recurrence <> 'none';
CREATE INDEX idx_tasks_category ON tasks (category_id);

-- ---------------------------------------------------------------------
-- task_exceptions · excepciones de una tarea recurrente
-- (saltar un día concreto o marcarla hecha solo ese día)
-- ---------------------------------------------------------------------
CREATE TABLE task_exceptions (
  id              uuid        PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id         uuid        NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  task_id         uuid        NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
  occurrence_date date        NOT NULL,
  is_skipped      boolean     NOT NULL DEFAULT false,
  is_completed    boolean     NOT NULL DEFAULT false,
  created_at      timestamptz NOT NULL DEFAULT now(),
  UNIQUE (task_id, occurrence_date)
);
CREATE INDEX idx_task_exceptions_user_date ON task_exceptions (user_id, occurrence_date);

-- ---------------------------------------------------------------------
-- Categorías por defecto al crear un usuario (colores del mockup)
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION create_default_categories() RETURNS trigger AS $$
BEGIN
  INSERT INTO categories (user_id, name, color, position) VALUES
    (NEW.id, 'Fijo',       '#C9CDF2', 1),
    (NEW.id, 'Urgente',    '#EB775A', 2),
    (NEW.id, 'Importante', '#8DC63F', 3),
    (NEW.id, 'Nueva',      '#F4B942', 4);
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_users_default_categories AFTER INSERT ON users
  FOR EACH ROW EXECUTE FUNCTION create_default_categories();

-- ---------------------------------------------------------------------
-- Aislamiento multitenant con Row Level Security (opcional pero recomendado)
-- La app ejecuta en cada petición:  SET LOCAL app.user_id = '<uuid del usuario>';
-- El rol propietario de las tablas no se ve afectado salvo con FORCE.
-- ---------------------------------------------------------------------
ALTER TABLE categories      ENABLE ROW LEVEL SECURITY;
ALTER TABLE tasks           ENABLE ROW LEVEL SECURITY;
ALTER TABLE task_exceptions ENABLE ROW LEVEL SECURITY;

CREATE POLICY categories_owner ON categories
  USING (user_id = current_setting('app.user_id', true)::uuid)
  WITH CHECK (user_id = current_setting('app.user_id', true)::uuid);

CREATE POLICY tasks_owner ON tasks
  USING (user_id = current_setting('app.user_id', true)::uuid)
  WITH CHECK (user_id = current_setting('app.user_id', true)::uuid);

CREATE POLICY task_exceptions_owner ON task_exceptions
  USING (user_id = current_setting('app.user_id', true)::uuid)
  WITH CHECK (user_id = current_setting('app.user_id', true)::uuid);
