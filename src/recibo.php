<?php
/**
 * recibo.php — gera o comprovativo em PDF de uma marcação a partir do ticket.
 * Acesso público: quem tem o ticket (código único, nunca repetido) pode obter
 * o comprovativo — não é exigido login, à semelhança de consulta.php.
 */
date_default_timezone_set('Africa/Maputo');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pdf_helper.php';

$ticket = strtoupper(trim($_GET['ticket'] ?? ''));
if ($ticket === '') {
    http_response_code(400);
    exit('Ticket não indicado.');
}

$stmt = db()->prepare("SELECT * FROM marcacoes WHERE ticket = ? LIMIT 1");
$stmt->execute([$ticket]);
$m = $stmt->fetch();

if (!$m) {
    http_response_code(404);
    exit('Ticket não encontrado.');
}

// ── Formatação dos dados ───────────────────────────────────────────────────
$dias_pt = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$dataObj = DateTime::createFromFormat('Y-m-d', $m['data']);
$dataFormatada = $dataObj
    ? $dataObj->format('d/m/Y') . ' (' . $dias_pt[(int)$dataObj->format('w')] . ')'
    : htmlspecialchars($m['data']);

$horaFormatada   = formatar_hora($m['hora']) ?: 'A confirmar pela recepção';
$tipoFormatado   = $m['urgencia'] === 'urgente' ? 'Urgente' : 'Normal';
$estadoMap       = ['Pendente' => 'Pendente', 'Concluido' => 'Concluído', 'Cancelado' => 'Cancelado'];
$estadoFormatado = $estadoMap[$m['estado']] ?? $m['estado'];
$medicoFormatado = $m['medico'] !== '' ? $m['medico'] : 'A atribuir pela recepção';
$geradoEm        = date('d/m/Y H:i');

// ── Construção do PDF ───────────────────────────────────────────────────────
$pdf = new SimplePdf();
$W = $pdf->width();
$H = $pdf->height();

$navy  = [10, 31, 68];
$cyan  = [173, 216, 255];
$muted = [100, 115, 145];
$dark  = [20, 25, 40];
$line  = [222, 228, 238];
$boxbg = [232, 240, 254];

// Cabeçalho
$pdf->rect(0, $H - 130, $W, 130, $navy, true);
$pdf->text(50, $H - 58, 'Vida Centro de Saúde', 'F2', 23, [255, 255, 255]);
$pdf->text(50, $H - 80, 'Excelência médica ao seu serviço.', 'F1', 11, $cyan);
$pdf->text(50, $H - 105, 'Maputo, Moçambique  ·  +258 84 000 0000  ·  geral@vidacentro.mz', 'F1', 9, $cyan);

// Título
$pdf->text(50, $H - 168, 'Comprovativo de Marcação de Consulta', 'F2', 16, $navy);
$pdf->line(50, $H - 182, $W - 50, $H - 182, $line, 1);

// Caixa do ticket
$boxY = $H - 290;
$boxH = 85;
$pdf->rect(50, $boxY, $W - 100, $boxH, $boxbg, true);
$pdf->text(0, $boxY + $boxH - 26, 'O SEU TICKET', 'F1', 10, $muted, 'center', $W);
$pdf->text(0, $boxY + 18, $m['ticket'], 'F2', 28, $navy, 'center', $W);

// Detalhes
$y = $boxY - 38;
$rowH = 25;
$rows = [
    ['Data da consulta', $dataFormatada],
    ['Hora',              $horaFormatada],
    ['Tipo de consulta',  $tipoFormatado],
    ['Médico',            $medicoFormatado],
    ['Estado actual',     $estadoFormatado],
];
foreach ($rows as [$label, $value]) {
    $pdf->text(50, $y, $label . ':', 'F2', 11.5, [70, 80, 100]);
    $pdf->text(230, $y, $value, 'F1', 11.5, $dark);
    $y -= $rowH;
}

$y -= 12;
$pdf->line(50, $y, $W - 50, $y, $line, 1);
$y -= 26;

// Instruções
$pdf->text(50, $y, 'Como gerir esta marcação', 'F2', 11.5, $navy);
$y -= 20;
$instrucoes = [
    'Guarde este comprovativo — pode precisar dele se esquecer o seu ticket.',
    'Para saber a hora exacta da consulta, ou cancelar/remarcar para outro dia,',
    'aceda o site da clínica e introduza o ticket acima.',
    'Este código é pessoal e único: só quem o possui acede aos dados da marcação.',
];
foreach ($instrucoes as $linha) {
    $pdf->text(50, $y, $linha, 'F1', 10.5, [70, 80, 100]);
    $y -= 16;
}

// Rodapé
$pdf->line(50, 68, $W - 50, 68, $line, 1);
$pdf->text(50, 50, 'Documento gerado automaticamente em ' . $geradoEm, 'F1', 8.5, [150, 155, 170]);
$pdf->text(0, 50, 'Vida Centro de Saúde', 'F1', 8.5, [150, 155, 170], 'right', $W - 50);

$pdfBytes = $pdf->output();
$filename = 'comprovativo_' . preg_replace('/[^A-Za-z0-9_-]/', '', $m['ticket']) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, max-age=0, must-revalidate');
echo $pdfBytes;
