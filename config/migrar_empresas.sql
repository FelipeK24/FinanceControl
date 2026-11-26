CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO empresas (nome) VALUES ('Empresa atual');

ALTER TABLE usuarios
ADD COLUMN empresa_id INT NULL AFTER id;

UPDATE usuarios
SET empresa_id = (SELECT id FROM empresas ORDER BY id LIMIT 1);

ALTER TABLE usuarios
MODIFY empresa_id INT NOT NULL,
ADD KEY idx_usuarios_empresa (empresa_id),
ADD CONSTRAINT fk_usuarios_empresa
    FOREIGN KEY (empresa_id)
    REFERENCES empresas(id)
    ON DELETE CASCADE;

ALTER TABLE categorias
ADD COLUMN empresa_id INT NULL AFTER id;

UPDATE categorias
SET empresa_id = (SELECT id FROM empresas ORDER BY id LIMIT 1);

ALTER TABLE categorias
MODIFY empresa_id INT NOT NULL,
ADD KEY idx_categorias_empresa (empresa_id),
ADD CONSTRAINT fk_categorias_empresa
    FOREIGN KEY (empresa_id)
    REFERENCES empresas(id)
    ON DELETE CASCADE;