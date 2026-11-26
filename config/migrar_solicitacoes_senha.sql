CREATE TABLE solicitacoes_redefinicao_senha (
    usuario_id INT PRIMARY KEY,
    solicitada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pendente TINYINT(1) NOT NULL DEFAULT 1,
    resolvida_em TIMESTAMP NULL,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;