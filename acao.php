<?php

require_once __DIR__ . '/includes/auth.php';

exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

validarToken();

$acao = textoPost('acao');
if (in_array($acao, ['selecionar_empresa', 'limpar_empresa'], true)) {
    exigirAdminGeral();
} else {
    exigirEmpresa();
}

$usuarioId = (int) $_SESSION['usuario_id'];
$empresaId = (int) ($_SESSION['empresa_id'] ?? 0);
$ehAdmin = ehAdminEmpresa();







function textoPost($campo) {

    if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
        return '';
    }

    return trim($_POST[$campo]);
}



function comprimentoTexto($texto) {

    if (function_exists('mb_strlen')) {
        return mb_strlen($texto, 'UTF-8');
    }

    $resultado = preg_match_all('/./us', $texto);

    return $resultado === false ? PHP_INT_MAX : $resultado;
}



function valorEmCentavos($valor, $permitirZero = false) {

    if (!is_string($valor) && !is_numeric($valor)) {
        return false;
    }

    $valor = trim((string) $valor);

    if (!preg_match('/^\d{1,10}(?:[.,]\d{1,2})?$/D', $valor)) {
        return false;
    }

    $valor = str_replace(',', '.', $valor);

    $partes = explode('.', $valor);

    $inteiros = (int) $partes[0];

    $decimais = isset($partes[1])
        ? (int) str_pad($partes[1], 2, '0')
        : 0;

    $centavos = ($inteiros * 100) + $decimais;

    if (!$permitirZero && $centavos <= 0) {
        return false;
    }

    return $centavos;
}



function centavosParaDecimal($centavos) {

    $centavos = (int) $centavos;

    $inteiros = intdiv($centavos, 100);
    $decimais = $centavos % 100;

    return $inteiros . '.' . str_pad(
        (string) $decimais,
        2,
        '0',
        STR_PAD_LEFT
    );
}



function voltar($pagina) {

    header('Location: ' . $pagina);
    exit;
}



function voltarLimites($mes = null, $ano = null) {

    $mes = filter_var($mes, FILTER_VALIDATE_INT);
    $ano = filter_var($ano, FILTER_VALIDATE_INT);

    if ($mes === false || $mes < 1 || $mes > 12) {
        $mes = (int) date('m');
    }

    if ($ano === false || $ano < 2000 || $ano > 2100) {
        $ano = (int) date('Y');
    }

    voltar('/pages/limites.php?mes=' . $mes . '&ano=' . $ano);
}



function dataValida($data) {

    if (
        !is_string($data) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $data)
    ) {
        return false;
    }

    return checkdate(
        (int) substr($data, 5, 2),
        (int) substr($data, 8, 2),
        (int) substr($data, 0, 4)
    );
}



function calcularProximaDataRecorrencia(
    $dataAtual,
    $frequencia,
    $dataInicio
) {

    $atual = new DateTimeImmutable($dataAtual);
    $inicio = new DateTimeImmutable($dataInicio);

    switch ($frequencia) {

        case 'semanal':

            return $atual->modify('+7 days')->format('Y-m-d');


        case 'quinzenal':

            return $atual->modify('+14 days')->format('Y-m-d');


        case 'mensal':

            $mesAlvo = $atual->modify('first day of next month');

            $diaReferencia = (int) $inicio->format('d');
            $ultimoDia = (int) $mesAlvo->format('t');

            $dia = min($diaReferencia, $ultimoDia);

            return $mesAlvo->format('Y-m-') . str_pad(
                (string) $dia,
                2,
                '0',
                STR_PAD_LEFT
            );


        case 'anual':

            $anoAlvo = (int) $atual->format('Y') + 1;

            $mesReferencia = (int) $inicio->format('m');
            $diaReferencia = (int) $inicio->format('d');

            $mesBase = new DateTimeImmutable(
                sprintf('%04d-%02d-01', $anoAlvo, $mesReferencia)
            );

            $ultimoDia = (int) $mesBase->format('t');

            $dia = min($diaReferencia, $ultimoDia);

            return sprintf(
                '%04d-%02d-%02d',
                $anoAlvo,
                $mesReferencia,
                $dia
            );
    }

    throw new InvalidArgumentException('Frequência inválida.');
}






switch ($acao) {


    case 'selecionar_empresa':

        $empresaSelecionadaId = (int) ($_POST['empresa_id'] ?? 0);
        $stmt = $pdo->prepare(
            "SELECT id, nome FROM empresas WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$empresaSelecionadaId]);
        $empresa = $stmt->fetch();

        if (!$empresa) {
            voltar('/pages/empresas.php?erro=empresa');
        }

        $_SESSION['empresa_contexto_id'] = (int) $empresa['id'];
        $_SESSION['empresa_id'] = (int) $empresa['id'];
        $_SESSION['empresa_nome'] = $empresa['nome'];

        voltar('/pages/dashboard.php');


    case 'limpar_empresa':

        unset($_SESSION['empresa_contexto_id']);
        $_SESSION['empresa_id'] = 0;
        $_SESSION['empresa_nome'] = 'Admin geral';

        voltar('/pages/empresas.php');


    
    
    

    case 'criar_lancamento':

        $descricao = textoPost('descricao');
        $valorCentavos = valorEmCentavos($_POST['valor'] ?? '');
        $tipo = textoPost('tipo');
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $data = $_POST['data'] ?? '';

        if (
            $descricao === '' ||
            comprimentoTexto($descricao) > 150 ||
            $valorCentavos === false ||
            !in_array($tipo, ['receita', 'despesa'], true) ||
            $categoriaId <= 0 ||
            !dataValida($data)
        ) {
            voltar('/pages/lancamentos.php');
        }

        $stmt = $pdo->prepare(
            "SELECT id
             FROM categorias
             WHERE id = ?
               AND tipo = ?
                 AND ativa = 1
                 AND empresa_id = ?"
        );

             $stmt->execute([$categoriaId, $tipo, $empresaId]);

        if (!$stmt->fetch()) {
            voltar('/pages/lancamentos.php');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO lancamentos
                (usuario_id, categoria_id, descricao, valor, tipo, data)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $usuarioId,
            $categoriaId,
            $descricao,
            centavosParaDecimal($valorCentavos),
            $tipo,
            $data
        ]);

        voltar('/pages/lancamentos.php');


    
    
    

    case 'editar_lancamento':

        $id = (int) ($_POST['id'] ?? 0);
        $descricao = textoPost('descricao');
        $valorCentavos = valorEmCentavos($_POST['valor'] ?? '');
        $tipo = textoPost('tipo');
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $data = $_POST['data'] ?? '';

        if (
            $id <= 0 ||
            $descricao === '' ||
            comprimentoTexto($descricao) > 150 ||
            $valorCentavos === false ||
            !in_array($tipo, ['receita', 'despesa'], true) ||
            $categoriaId <= 0 ||
            !dataValida($data)
        ) {
            voltar('/pages/lancamentos.php');
        }

        $stmt = $pdo->prepare(
            "SELECT id
             FROM categorias
             WHERE id = ?
                 AND tipo = ?
                 AND empresa_id = ?"
        );

             $stmt->execute([$categoriaId, $tipo, $empresaId]);

        if (!$stmt->fetch()) {
            voltar('/pages/lancamentos.php');
        }

        $sql = "UPDATE lancamentos
                SET categoria_id = ?,
                    descricao = ?,
                    valor = ?,
                    tipo = ?,
                    data = ?
                WHERE id = ?";

        $parametros = [
            $categoriaId,
            $descricao,
            centavosParaDecimal($valorCentavos),
            $tipo,
            $data,
            $id
        ];

        if ($ehAdmin) {
            $sql .= " AND usuario_id IN (
                SELECT id FROM usuarios WHERE empresa_id = ?
            )";
            $parametros[] = $empresaId;
        } else {
            $sql .= " AND usuario_id = ?";
            $parametros[] = $usuarioId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        voltar('/pages/lancamentos.php');


    
    
    

    case 'excluir_lancamento':

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            voltar('/pages/lancamentos.php');
        }

        $sql = "DELETE FROM lancamentos WHERE id = ?";
        $parametros = [$id];

        if ($ehAdmin) {
            $sql .= " AND usuario_id IN (
                SELECT id FROM usuarios WHERE empresa_id = ?
            )";
            $parametros[] = $empresaId;
        } else {
            $sql .= " AND usuario_id = ?";
            $parametros[] = $usuarioId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        voltar('/pages/lancamentos.php');


    
    
    

    case 'criar_categoria':

        exigirAdmin();

        $nome = textoPost('nome');
        $tipo = textoPost('tipo');

        if (
            $nome === '' ||
            comprimentoTexto($nome) > 80 ||
            !in_array($tipo, ['receita', 'despesa'], true)
        ) {
            voltar('/pages/categorias.php');
        }

        $stmt = $pdo->prepare(
              "INSERT INTO categorias (empresa_id, nome, tipo)
               VALUES (?, ?, ?)"
        );

           $stmt->execute([$empresaId, $nome, $tipo]);

        voltar('/pages/categorias.php');


    
    
    

    case 'editar_categoria':

        exigirAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $nome = textoPost('nome');
        $tipo = textoPost('tipo');

        if (
            $id <= 0 ||
            $nome === '' ||
            comprimentoTexto($nome) > 80 ||
            !in_array($tipo, ['receita', 'despesa'], true)
        ) {
            voltar('/pages/categorias.php');
        }

        $stmt = $pdo->prepare(
              "SELECT id, tipo
             FROM categorias
               WHERE id = ? AND empresa_id = ?"
        );

           $stmt->execute([$id, $empresaId]);
        $categoriaAtual = $stmt->fetch();

        if (!$categoriaAtual) {
            voltar('/pages/categorias.php');
        }

        
        if ($categoriaAtual['tipo'] !== $tipo) {

            $stmt = $pdo->prepare(
                "SELECT
                    (
                        SELECT COUNT(*)
                        FROM lancamentos
                        WHERE categoria_id = ?
                    ) +
                    (
                        SELECT COUNT(*)
                        FROM recorrencias
                        WHERE categoria_id = ?
                    ) +
                    (
                        SELECT COUNT(*)
                        FROM limites_gastos
                        WHERE categoria_id = ?
                    ) AS total"
            );

            $stmt->execute([$id, $id, $id]);

            $utilizacoes = $stmt->fetch();

            if ((int) $utilizacoes['total'] > 0) {
                voltar('/pages/categorias.php');
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE categorias
             SET nome = ?, tipo = ?
             WHERE id = ? AND empresa_id = ?"
        );

        try {

            $stmt->execute([$nome, $tipo, $id, $empresaId]);

        } catch (PDOException $e) {

            error_log('Erro ao editar categoria: ' . $e->getMessage());

            voltar('/pages/categorias.php');
        }

        voltar('/pages/categorias.php');


    
    
    

    case 'excluir_categoria':

        exigirAdmin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            voltar('/pages/categorias.php');
        }

        try {

            $stmt = $pdo->prepare(
                "DELETE FROM categorias
                  WHERE id = ? AND empresa_id = ?"
            );

              $stmt->execute([$id, $empresaId]);

        } catch (PDOException $e) {

            error_log('Erro ao excluir categoria: ' . $e->getMessage());
        }

        voltar('/pages/categorias.php');


    
    
    

    case 'alterar_usuario':

        exigirAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $tipo = textoPost('tipo');

        if (
            $id <= 0 ||
            $id === $usuarioId ||
            !in_array($tipo, ['admin', 'usuario'], true)
        ) {
            voltar('/pages/usuarios.php');
        }

        $stmt = $pdo->prepare(
            "UPDATE usuarios
             SET tipo = ?
               WHERE id = ? AND empresa_id = ?"
        );

           $stmt->execute([$tipo, $id, $empresaId]);

        voltar('/pages/usuarios.php');



    case 'criar_usuario':

        exigirAdmin();

        $nome = textoPost('nome');
        $email = textoPost('email');
        $senha = $_POST['senha'] ?? null;
        $confirmar = $_POST['confirmar'] ?? null;

        if (
            $nome === '' ||
            comprimentoTexto($nome) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            comprimentoTexto($email) > 150 ||
            !is_string($senha) ||
            !is_string($confirmar) ||
            strlen($senha) < 8 ||
            $senha !== $confirmar
        ) {
            voltar('/pages/usuarios.php?erro=dados');
        }

        $stmt = $pdo->prepare(
            "SELECT id FROM usuarios WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            voltar('/pages/usuarios.php?erro=email');
        }

        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (empresa_id, nome, email, senha, tipo)
             VALUES (?, ?, ?, ?, 'usuario')"
        );

        try {
            $stmt->execute([$empresaId, $nome, $email, $hash]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                voltar('/pages/usuarios.php?erro=email');
            }

            throw $e;
        }

        voltar('/pages/usuarios.php?sucesso=funcionario');


    case 'redefinir_senha_usuario':

        exigirAdmin();

        $alvoId = (int) ($_POST['usuario_id'] ?? 0);
        $senha = $_POST['senha'] ?? null;
        $confirmar = $_POST['confirmar'] ?? null;

        if (
            $alvoId <= 0 ||
            !is_string($senha) ||
            !is_string($confirmar) ||
            strlen($senha) < 8 ||
            $senha !== $confirmar
        ) {
            voltar('/pages/usuarios.php?erro=senha');
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                                "SELECT s.usuario_id
                                 FROM solicitacoes_redefinicao_senha s
                                 INNER JOIN usuarios u ON u.id = s.usuario_id
                                 WHERE s.usuario_id = ?
                                     AND s.pendente = 1
                                     AND u.empresa_id = ?
                 FOR UPDATE"
            );
                        $stmt->execute([$alvoId, $empresaId]);

            if (!$stmt->fetch()) {
                $pdo->rollBack();
                voltar('/pages/usuarios.php?erro=solicitacao');
            }

            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "UPDATE usuarios
                 SET senha = ?
                 WHERE id = ? AND empresa_id = ?"
            );
            $stmt->execute([$hash, $alvoId, $empresaId]);

            $stmt = $pdo->prepare(
                "UPDATE solicitacoes_redefinicao_senha
                 SET pendente = 0, resolvida_em = CURRENT_TIMESTAMP
                 WHERE usuario_id = ? AND pendente = 1"
            );
            $stmt->execute([$alvoId]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

        voltar('/pages/usuarios.php?sucesso=senha_redefinida');


    
    
    

    case 'excluir_usuario':

        exigirAdmin();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $id === $usuarioId) {
            voltar('/pages/usuarios.php');
        }

        $stmt = $pdo->prepare(
            "DELETE FROM usuarios
               WHERE id = ? AND empresa_id = ?"
        );

           $stmt->execute([$id, $empresaId]);

        voltar('/pages/usuarios.php');


    
    
    

    case 'criar_meta':

        $nome = textoPost('nome');
        $descricao = textoPost('descricao');

        $objetivoCentavos = valorEmCentavos(
            $_POST['valor_objetivo'] ?? ''
        );

        $inicialCentavos = valorEmCentavos(
            $_POST['valor_inicial'] ?? '0',
            true
        );

        $dataInicio = $_POST['data_inicio'] ?? '';
        $prazo = $_POST['prazo'] ?? '';

        $prazoValido = $prazo === '' || dataValida($prazo);

        if (
            $nome === '' ||
            comprimentoTexto($nome) > 120 ||
            comprimentoTexto($descricao) > 255 ||
            $objetivoCentavos === false ||
            $inicialCentavos === false ||
            !dataValida($dataInicio) ||
            !$prazoValido ||
            ($prazo !== '' && $prazo < $dataInicio)
        ) {
            voltar('/pages/metas.php');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO metas_financeiras
            (
                usuario_id,
                nome,
                descricao,
                valor_objetivo,
                valor_inicial,
                data_inicio,
                prazo
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $usuarioId,
            $nome,
            $descricao !== '' ? $descricao : null,
            centavosParaDecimal($objetivoCentavos),
            centavosParaDecimal($inicialCentavos),
            $dataInicio,
            $prazo !== '' ? $prazo : null
        ]);

        voltar('/pages/metas.php');


    
    
    

    case 'contribuir_meta':

        $metaId = (int) ($_POST['meta_id'] ?? 0);
        $tipo = textoPost('tipo');

        $valorCentavos = valorEmCentavos(
            $_POST['valor'] ?? ''
        );

        $descricao = textoPost('descricao');
        $data = $_POST['data'] ?? '';

        if (
            $metaId <= 0 ||
            !in_array($tipo, ['deposito', 'retirada'], true) ||
            $valorCentavos === false ||
            comprimentoTexto($descricao) > 150 ||
            !dataValida($data)
        ) {
            voltar('/pages/metas.php');
        }

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT id, valor_inicial
                 FROM metas_financeiras
                 WHERE id = ?
                   AND usuario_id = ?
                   AND status = 'ativa'
                 FOR UPDATE"
            );

            $stmt->execute([$metaId, $usuarioId]);

            $meta = $stmt->fetch();

            if (!$meta) {

                $pdo->rollBack();

                voltar('/pages/metas.php');
            }

            $stmt = $pdo->prepare(
                "SELECT
                    COALESCE(
                        SUM(
                            CASE
                                WHEN tipo = 'deposito' THEN valor
                                WHEN tipo = 'retirada' THEN -valor
                                ELSE 0
                            END
                        ),
                        0
                    ) AS total_contribuicoes
                 FROM contribuicoes_metas
                 WHERE meta_id = ?"
            );

            $stmt->execute([$metaId]);

            $contribuicoes = $stmt->fetch();

            $saldoInicialCentavos = valorEmCentavos(
                (string) $meta['valor_inicial'],
                true
            );

            $contribuicoesCentavos = valorEmCentavos(
                (string) $contribuicoes['total_contribuicoes'],
                true
            );

            if (
                $saldoInicialCentavos === false ||
                $contribuicoesCentavos === false
            ) {
                throw new RuntimeException(
                    'Não foi possível calcular o saldo da meta.'
                );
            }

            $saldoAtual = $saldoInicialCentavos
                + $contribuicoesCentavos;

            if (
                $tipo === 'retirada' &&
                $valorCentavos > $saldoAtual
            ) {

                $pdo->rollBack();

                voltar('/pages/metas.php');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO contribuicoes_metas
                    (meta_id, tipo, valor, descricao, data)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $metaId,
                $tipo,
                centavosParaDecimal($valorCentavos),
                $descricao !== '' ? $descricao : null,
                $data
            ]);

            $pdo->commit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Erro ao registrar contribuição: ' . $e->getMessage());

            throw $e;
        }

        voltar('/pages/metas.php');


    
    
    

    case 'alterar_status_meta':

        $metaId = (int) ($_POST['meta_id'] ?? 0);
        $status = textoPost('status');

        if (
            $metaId <= 0 ||
            !in_array($status, ['ativa', 'pausada', 'concluida'], true)
        ) {
            voltar('/pages/metas.php');
        }

        if ($status === 'concluida') {
            $stmt = $pdo->prepare(
                "SELECT
                    (
                        m.valor_inicial + COALESCE(
                            SUM(
                                CASE
                                    WHEN c.tipo = 'deposito' THEN c.valor
                                    WHEN c.tipo = 'retirada' THEN -c.valor
                                    ELSE 0
                                END
                            ), 0
                        )
                    ) >= m.valor_objetivo AS objetivo_atingido
                 FROM metas_financeiras m
                 LEFT JOIN contribuicoes_metas c
                    ON c.meta_id = m.id
                 WHERE m.id = ?
                   AND m.usuario_id = ?
                 GROUP BY m.id"
            );

            $stmt->execute([$metaId, $usuarioId]);
            $meta = $stmt->fetch();

            if (!$meta || (int) $meta['objetivo_atingido'] !== 1) {
                voltar('/pages/metas.php');
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE metas_financeiras
             SET status = ?
             WHERE id = ?
               AND usuario_id = ?"
        );

        $stmt->execute([$status, $metaId, $usuarioId]);

        voltar('/pages/metas.php');


    
    
    

    case 'excluir_meta':

        $metaId = (int) ($_POST['meta_id'] ?? 0);

        if ($metaId <= 0) {
            voltar('/pages/metas.php');
        }

        $stmt = $pdo->prepare(
            "DELETE FROM metas_financeiras
             WHERE id = ?
               AND usuario_id = ?"
        );

        $stmt->execute([$metaId, $usuarioId]);

        voltar('/pages/metas.php');


    
    
    

    case 'criar_limite':

        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);

        $limiteCentavos = valorEmCentavos(
            $_POST['valor_limite'] ?? ''
        );

        $mes = filter_var(
            $_POST['mes'] ?? '',
            FILTER_VALIDATE_INT
        );

        $ano = filter_var(
            $_POST['ano'] ?? '',
            FILTER_VALIDATE_INT
        );

        if (
            $categoriaId <= 0 ||
            $limiteCentavos === false ||
            $mes === false ||
            $mes < 1 ||
            $mes > 12 ||
            $ano === false ||
            $ano < 2000 ||
            $ano > 2100
        ) {
            voltarLimites($mes, $ano);
        }

        $stmt = $pdo->prepare(
            "SELECT id
             FROM categorias
             WHERE id = ?
                             AND tipo = 'despesa'
                 AND ativa = 1
                 AND empresa_id = ?"
        );

             $stmt->execute([$categoriaId, $empresaId]);

        if (!$stmt->fetch()) {
            voltarLimites($mes, $ano);
        }

        try {

            $stmt = $pdo->prepare(
                "INSERT INTO limites_gastos
                    (usuario_id, categoria_id, ano, mes, valor_limite)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $usuarioId,
                $categoriaId,
                $ano,
                $mes,
                centavosParaDecimal($limiteCentavos)
            ]);

        } catch (PDOException $e) {

            
            if ($e->getCode() !== '23000') {
                error_log('Erro ao criar limite: ' . $e->getMessage());
            }
        }

        voltarLimites($mes, $ano);


    
    
    

    case 'editar_limite':

        $limiteId = (int) ($_POST['limite_id'] ?? 0);

        $limiteCentavos = valorEmCentavos(
            $_POST['valor_limite'] ?? ''
        );

        $mes = filter_var(
            $_POST['mes'] ?? '',
            FILTER_VALIDATE_INT
        );

        $ano = filter_var(
            $_POST['ano'] ?? '',
            FILTER_VALIDATE_INT
        );

        if (
            $limiteId <= 0 ||
            $limiteCentavos === false ||
            $mes === false ||
            $mes < 1 ||
            $mes > 12 ||
            $ano === false ||
            $ano < 2000 ||
            $ano > 2100
        ) {
            voltarLimites($mes, $ano);
        }

        $stmt = $pdo->prepare(
            "UPDATE limites_gastos
             SET valor_limite = ?
             WHERE id = ?
               AND usuario_id = ?"
        );

        $stmt->execute([
            centavosParaDecimal($limiteCentavos),
            $limiteId,
            $usuarioId
        ]);

        voltarLimites($mes, $ano);


    
    
    

    case 'excluir_limite':

        $limiteId = (int) ($_POST['limite_id'] ?? 0);

        $mes = filter_var(
            $_POST['mes'] ?? '',
            FILTER_VALIDATE_INT
        );

        $ano = filter_var(
            $_POST['ano'] ?? '',
            FILTER_VALIDATE_INT
        );

        if ($limiteId <= 0) {
            voltarLimites($mes, $ano);
        }

        $stmt = $pdo->prepare(
            "DELETE FROM limites_gastos
             WHERE id = ?
               AND usuario_id = ?"
        );

        $stmt->execute([$limiteId, $usuarioId]);

        voltarLimites($mes, $ano);


    
    
    

    case 'criar_recorrencia':

        $descricao = textoPost('descricao');

        $valorCentavos = valorEmCentavos(
            $_POST['valor'] ?? ''
        );

        $tipo = textoPost('tipo');
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $frequencia = textoPost('frequencia');

        $dataInicio = $_POST['data_inicio'] ?? '';
        $dataFim = $_POST['data_fim'] ?? '';

        $frequenciasPermitidas = [
            'semanal',
            'quinzenal',
            'mensal',
            'anual'
        ];

        if (
            $descricao === '' ||
            comprimentoTexto($descricao) > 150 ||
            $valorCentavos === false ||
            !in_array($tipo, ['receita', 'despesa'], true) ||
            $categoriaId <= 0 ||
            !in_array($frequencia, $frequenciasPermitidas, true) ||
            !dataValida($dataInicio) ||
            ($dataFim !== '' && !dataValida($dataFim)) ||
            ($dataFim !== '' && $dataFim < $dataInicio)
        ) {
            voltar('/pages/recorrencias.php');
        }

        $stmt = $pdo->prepare(
            "SELECT id
             FROM categorias
             WHERE id = ?
                 AND tipo = ?
                 AND ativa = 1
                 AND empresa_id = ?"
        );

             $stmt->execute([$categoriaId, $tipo, $empresaId]);

        if (!$stmt->fetch()) {
            voltar('/pages/recorrencias.php');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO recorrencias
                (
                    usuario_id,
                    categoria_id,
                    descricao,
                    valor,
                    tipo,
                    frequencia,
                    data_inicio,
                    proxima_data,
                    data_fim,
                    ativa
                )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );

        $stmt->execute([
            $usuarioId,
            $categoriaId,
            $descricao,
            centavosParaDecimal($valorCentavos),
            $tipo,
            $frequencia,
            $dataInicio,
            $dataInicio,
            $dataFim !== '' ? $dataFim : null
        ]);

        voltar('/pages/recorrencias.php');


    
    
    

    case 'editar_recorrencia':

        $recorrenciaId = (int) ($_POST['recorrencia_id'] ?? 0);

        $descricao = textoPost('descricao');

        $valorCentavos = valorEmCentavos(
            $_POST['valor'] ?? ''
        );

        $tipo = textoPost('tipo');
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $frequencia = textoPost('frequencia');

        $dataInicio = $_POST['data_inicio'] ?? '';
        $dataFim = $_POST['data_fim'] ?? '';

        $frequenciasPermitidas = [
            'semanal',
            'quinzenal',
            'mensal',
            'anual'
        ];

        if (
            $recorrenciaId <= 0 ||
            $descricao === '' ||
            comprimentoTexto($descricao) > 150 ||
            $valorCentavos === false ||
            !in_array($tipo, ['receita', 'despesa'], true) ||
            $categoriaId <= 0 ||
            !in_array($frequencia, $frequenciasPermitidas, true) ||
            !dataValida($dataInicio) ||
            ($dataFim !== '' && !dataValida($dataFim)) ||
            ($dataFim !== '' && $dataFim < $dataInicio)
        ) {
            voltar('/pages/recorrencias.php');
        }

        $stmt = $pdo->prepare(
            "SELECT id
             FROM categorias
             WHERE id = ?
                 AND tipo = ?
                 AND empresa_id = ?"
        );

             $stmt->execute([$categoriaId, $tipo, $empresaId]);

        if (!$stmt->fetch()) {
            voltar('/pages/recorrencias.php');
        }

        $stmt = $pdo->prepare(
            "SELECT proxima_data
             FROM recorrencias
             WHERE id = ?
               AND usuario_id = ?"
        );

        $stmt->execute([$recorrenciaId, $usuarioId]);

        $recorrencia = $stmt->fetch();

        if (!$recorrencia) {
            voltar('/pages/recorrencias.php');
        }

        $proximaData = $recorrencia['proxima_data'];

        if ($proximaData < $dataInicio) {
            $proximaData = $dataInicio;
        }

        $ativa = 1;

        if ($dataFim !== '' && $proximaData > $dataFim) {
            $ativa = 0;
        }

        $stmt = $pdo->prepare(
            "UPDATE recorrencias
             SET categoria_id = ?,
                 descricao = ?,
                 valor = ?,
                 tipo = ?,
                 frequencia = ?,
                 data_inicio = ?,
                 proxima_data = ?,
                 data_fim = ?,
                 ativa = ?
             WHERE id = ?
               AND usuario_id = ?"
        );

        $stmt->execute([
            $categoriaId,
            $descricao,
            centavosParaDecimal($valorCentavos),
            $tipo,
            $frequencia,
            $dataInicio,
            $proximaData,
            $dataFim !== '' ? $dataFim : null,
            $ativa,
            $recorrenciaId,
            $usuarioId
        ]);

        voltar('/pages/recorrencias.php');


    
    
    

    case 'alterar_status_recorrencia':

        $recorrenciaId = (int) ($_POST['recorrencia_id'] ?? 0);

        $ativa = filter_var(
            $_POST['ativa'] ?? '',
            FILTER_VALIDATE_INT
        );

        if (
            $recorrenciaId <= 0 ||
            $ativa === false ||
            !in_array($ativa, [0, 1], true)
        ) {
            voltar('/pages/recorrencias.php');
        }

        $stmt = $pdo->prepare(
            "UPDATE recorrencias
             SET ativa = ?
             WHERE id = ?
               AND usuario_id = ?"
        );

        $stmt->execute([
            $ativa,
            $recorrenciaId,
            $usuarioId
        ]);

        voltar('/pages/recorrencias.php');


    
    
    

    case 'excluir_recorrencia':

        $recorrenciaId = (int) ($_POST['recorrencia_id'] ?? 0);

        if ($recorrenciaId <= 0) {
            voltar('/pages/recorrencias.php');
        }

        $stmt = $pdo->prepare(
            "DELETE FROM recorrencias
             WHERE id = ?
               AND usuario_id = ?"
        );

        $stmt->execute([
            $recorrenciaId,
            $usuarioId
        ]);

        voltar('/pages/recorrencias.php');


    
    
    

    case 'confirmar_recorrencia':

        $recorrenciaId = (int) ($_POST['recorrencia_id'] ?? 0);

        if ($recorrenciaId <= 0) {
            voltar('/pages/recorrencias.php');
        }

        try {

            $pdo->beginTransaction();

            
            $stmt = $pdo->prepare(
                "SELECT
                    id,
                    usuario_id,
                    categoria_id,
                    descricao,
                    valor,
                    tipo,
                    frequencia,
                    data_inicio,
                    proxima_data,
                    data_fim,
                    ativa
                 FROM recorrencias
                 WHERE id = ?
                   AND usuario_id = ?
                 FOR UPDATE"
            );

            $stmt->execute([
                $recorrenciaId,
                $usuarioId
            ]);

            $recorrencia = $stmt->fetch();

            if (
                !$recorrencia ||
                (int) $recorrencia['ativa'] !== 1
            ) {

                $pdo->rollBack();

                voltar('/pages/recorrencias.php');
            }

            $dataOcorrencia = $recorrencia['proxima_data'];
            $hoje = date('Y-m-d');

            if ($dataOcorrencia > $hoje) {

                $pdo->rollBack();

                voltar('/pages/recorrencias.php');
            }

            if (
                !empty($recorrencia['data_fim']) &&
                $dataOcorrencia > $recorrencia['data_fim']
            ) {

                $pdo->prepare(
                    "UPDATE recorrencias
                     SET ativa = 0
                     WHERE id = ?
                       AND usuario_id = ?"
                )->execute([
                    $recorrenciaId,
                    $usuarioId
                ]);

                $pdo->commit();

                voltar('/pages/recorrencias.php');
            }

            
            $stmt = $pdo->prepare(
                "SELECT id
                 FROM categorias
                 WHERE id = ?
                                     AND tipo = ?
                                     AND empresa_id = ?"
            );

            $stmt->execute([
                $recorrencia['categoria_id'],
                                $recorrencia['tipo'],
                                $empresaId
            ]);

            if (!$stmt->fetch()) {

                $pdo->rollBack();

                voltar('/pages/recorrencias.php');
            }

            
            $stmt = $pdo->prepare(
                "INSERT INTO lancamentos
                    (
                        usuario_id,
                        categoria_id,
                        descricao,
                        valor,
                        tipo,
                        data,
                        recorrencia_id
                    )
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $usuarioId,
                $recorrencia['categoria_id'],
                $recorrencia['descricao'],
                $recorrencia['valor'],
                $recorrencia['tipo'],
                $dataOcorrencia,
                $recorrenciaId
            ]);

            $novaData = calcularProximaDataRecorrencia(
                $dataOcorrencia,
                $recorrencia['frequencia'],
                $recorrencia['data_inicio']
            );

            $novaAtiva = 1;

            if (
                !empty($recorrencia['data_fim']) &&
                $novaData > $recorrencia['data_fim']
            ) {
                $novaAtiva = 0;
            }

            $stmt = $pdo->prepare(
                "UPDATE recorrencias
                 SET proxima_data = ?,
                     ativa = ?
                 WHERE id = ?
                   AND usuario_id = ?"
            );

            $stmt->execute([
                $novaData,
                $novaAtiva,
                $recorrenciaId,
                $usuarioId
            ]);

            $pdo->commit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Erro ao confirmar recorrência: ' . $e->getMessage());

            throw $e;
        }

        voltar('/pages/recorrencias.php');


    
    
    

    default:

        http_response_code(400);
        exit('Ação inválida.');
}
