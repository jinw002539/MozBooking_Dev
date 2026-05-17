<?php
    /**
     * confirmar_ticket.php — endpoint AJAX (recepcionista)
     * Atribui médico e processo a um ticket; mantém estado Pendente
     * até o médico concluir na sua própria tela.
     */
    session_start();
    require_once __DIR__ . '/db.php';

    header('Content-Type: application/json');

    if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'recepcionista') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Não autorizado']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'msg' => 'Método inválido']);
        exit;
    }

    $ticket   = trim($_POST['ticket']   ?? '');
    $medico   = trim($_POST['medico']   ?? '');
    $processo = trim($_POST['processo'] ?? '');

    if (!$ticket || !$medico || !$processo) {
        echo json_encode(['ok' => false, 'msg' => 'Dados incompletos']);
        exit;
    }

    $pdo  = db();
    $stmt = $pdo->prepare("
        UPDATE marcacoes
        SET medico = ?, processo = ?, estado = 'Pendente'
        WHERE ticket = ?
    ");
    $stmt->execute([$medico, $processo, $ticket]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['ok' => false, 'msg' => 'Ticket não encontrado']);
        exit;
    }

    echo json_encode(['ok' => true, 'msg' => 'Confirmado']);
