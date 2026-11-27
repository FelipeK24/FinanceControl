ALTER TABLE usuarios
ADD COLUMN tema ENUM('claro', 'escuro') NOT NULL DEFAULT 'claro';
