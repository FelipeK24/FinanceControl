<?php

require_once __DIR__ . '/includes/auth.php';

exigirEmpresa();





$usuarioId = $_SESSION['usuario_id'];
$empresaId = (int) $_SESSION['empresa_id'];
$ehAdmin = ehAdminEmpresa();





$busca = trim($_GET['busca'] ?? '');
$tipo = $_GET['tipo'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$dataInicial = $_GET['data_inicial'] ?? '';
$dataFinal = $_GET['data_final'] ?? '';
$ordenacao = $_GET['ordenacao'] ?? 'data_desc';





if (!in_array($tipo, ['', 'receita', 'despesa'], true)) {
    $tipo = '';
}

if (
    $categoria !== '' &&
    (!ctype_digit((string) $categoria) || (int) $categoria <= 0)
) {
    $categoria = '';
}

function dataExportacaoValida($data)
{
    if ($data === '') {
        return false;
    }

    $dataObjeto = DateTime::createFromFormat('!Y-m-d', $data);

    return $dataObjeto && $dataObjeto->format('Y-m-d') === $data;
}

if (!dataExportacaoValida($dataInicial)) {
    $dataInicial = '';
}

if (!dataExportacaoValida($dataFinal)) {
    $dataFinal = '';
}

if (
    $dataInicial !== '' &&
    $dataFinal !== '' &&
    $dataInicial > $dataFinal
) {
    $dataInicial = '';
    $dataFinal = '';
}

$ordenacoesPermitidas = [
    'data_desc' => 'l.data DESC, l.id DESC',
    'data_asc' => 'l.data ASC, l.id ASC',
    'valor_desc' => 'l.valor DESC, l.data DESC',
    'valor_asc' => 'l.valor ASC, l.data DESC'
];

if (!isset($ordenacoesPermitidas[$ordenacao])) {
    $ordenacao = 'data_desc';
}

$ordenacaoSQL = $ordenacoesPermitidas[$ordenacao];





$where = " WHERE 1 = 1 ";

$parametros = [];


if ($ehAdmin) {
    $where .= " AND l.usuario_id IN (
        SELECT id FROM usuarios WHERE empresa_id = ?
    )";
    $parametros[] = $empresaId;
} else {
    $where .= " AND l.usuario_id = ?";
    $parametros[] = $usuarioId;
}


if ($busca !== '') {

    $where .= "
        AND (
            l.descricao LIKE ?
            OR c.nome LIKE ?
            OR u.nome LIKE ?
        )
    ";

    $termoBusca = '%' . $busca . '%';

    $parametros[] = $termoBusca;
    $parametros[] = $termoBusca;
    $parametros[] = $termoBusca;
}


if ($tipo !== '') {
    $where .= " AND l.tipo = ?";
    $parametros[] = $tipo;
}


if ($categoria !== '') {
    $where .= " AND l.categoria_id = ?";
    $parametros[] = (int) $categoria;
}


if ($dataInicial !== '') {
    $where .= " AND l.data >= ?";
    $parametros[] = $dataInicial;
}


if ($dataFinal !== '') {
    $where .= " AND l.data <= ?";
    $parametros[] = $dataFinal;
}





$sql = "
    SELECT
        l.id,
        l.descricao,
        l.valor,
        l.tipo,
        l.data,
        c.nome AS categoria,
        u.nome AS usuario

    FROM lancamentos l

    INNER JOIN categorias c
        ON c.id = l.categoria_id

    INNER JOIN usuarios u
        ON u.id = l.usuario_id

    $where

    ORDER BY $ordenacaoSQL
";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);

$lancamentos = $stmt->fetchAll();





function protegerCSV($valor)
{
    $valor = (string) $valor;

    
    if (preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $valor)) {
        return "'" . $valor;
    }

    return $valor;
}






if (ob_get_length()) {
    ob_clean();
}

$nomeArquivo = 'financecontrol_lancamentos_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');

header(
    'Content-Disposition: attachment; filename="' .
    $nomeArquivo .
    '"'
);

header('Pragma: no-cache');
header('Expires: 0');

$arquivo = fopen('php://output', 'w');


fwrite($arquivo, "\xEF\xBB\xBF");





if ($ehAdmin) {

    fputcsv(
        $arquivo,
        [
            'ID',
            'Data',
            'Descrição',
            'Categoria',
            'Tipo',
            'Valor',
            'Usuário'
        ],
        ';',
        '"',
        '\\'
    );

} else {

    fputcsv(
        $arquivo,
        [
            'ID',
            'Data',
            'Descrição',
            'Categoria',
            'Tipo',
            'Valor'
        ],
        ';',
        '"',
        '\\'
    );

}





foreach ($lancamentos as $lancamento) {

    $linha = [
        (int) $lancamento['id'],

        date(
            'd/m/Y',
            strtotime($lancamento['data'])
        ),

        protegerCSV($lancamento['descricao']),

        protegerCSV($lancamento['categoria']),

        $lancamento['tipo'] === 'receita'
            ? 'Receita'
            : 'Despesa',

        number_format(
            (float) $lancamento['valor'],
            2,
            ',',
            '.'
        )
    ];

    
    if ($ehAdmin) {
        $linha[] = protegerCSV($lancamento['usuario']);
    }

    fputcsv(
        $arquivo,
        $linha,
        ';',
        '"',
        '\\'
    );
}


fclose($arquivo);

exit;
