ALTER TABLE categorias
ADD COLUMN ativa TINYINT(1) NOT NULL DEFAULT 1;

UPDATE categorias
SET ativa = 0;

INSERT INTO categorias (nome, tipo) VALUES
('Venda de produtos', 'receita'),
('Prestação de serviços', 'receita'),
('Receitas financeiras', 'receita'),
('Comissões e royalties', 'receita'),
('Outras receitas', 'receita'),
('Fornecedores', 'despesa'),
('Custos com terceiros', 'despesa'),
('Folha de pagamento', 'despesa'),
('Encargos e benefícios', 'despesa'),
('Aluguel e condomínio', 'despesa'),
('Água, energia e telecomunicações', 'despesa'),
('Impostos e taxas', 'despesa'),
('Marketing e publicidade', 'despesa'),
('Transporte e logística', 'despesa'),
('Materiais e suprimentos', 'despesa'),
('Software e tecnologia', 'despesa'),
('Serviços profissionais', 'despesa'),
('Manutenção e equipamentos', 'despesa'),
('Despesas bancárias', 'despesa'),
('Viagens corporativas', 'despesa'),
('Outras despesas operacionais', 'despesa');