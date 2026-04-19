<?php
// Endpoint AJAX para confirmar ticket
session_start();
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] != 'recepcionista') {
    http_response_code(403);
    echo 'Não autorizado';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Método inválido';
    exit;
}

$ticket   = trim($_POST['ticket']   ?? '');
$medico   = trim($_POST['medico']   ?? '');
$processo = trim($_POST['processo'] ?? '');

if (!$ticket || !$medico || !$processo) {
    echo 'Dados incompletos';
    exit;
}

$caminho  = 'data/marcacao.json';
$marcacoes = json_decode(file_get_contents($caminho), true) ?? [];
$encontrado = false;

foreach ($marcacoes as &$m) {
    if ($m['ticket'] === $ticket) {
        $m['estado']   = 'Pendente'; // Mantém pendente até médico concluir
        $m['medico']   = $medico;
        $m['processo'] = $processo;
        $encontrado = true;
        break;
    }
}

if (!$encontrado) {
    echo 'Ticket não encontrado';
    exit;
}

file_put_contents($caminho, json_encode($marcacoes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'ok';
