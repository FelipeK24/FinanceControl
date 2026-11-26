ALTER TABLE usuarios
    MODIFY empresa_id INT NULL,
    MODIFY tipo ENUM('superadmin', 'admin', 'usuario') NOT NULL DEFAULT 'usuario';