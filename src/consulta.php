<?php
    // Fuso horário de Moçambique (UTC+2) — primeira instrução
    date_default_timezone_set('Africa/Maputo');

    session_start();
    require_once __DIR__ . '/db.php';

    $lang = isset($_GET['lang']) ? $_GET['lang'] : 'pt';
    if (!in_array($lang, ['pt', 'en'], true)) $lang = 'pt';

    $txt = [
        'pt' => [
            'title'          => 'Consultar Marcação | Vida',
            'subtitulo'      => 'Introduza o seu ticket para ver os detalhes da sua consulta.',
            'label_ticket'   => 'Código do Ticket',
            'placeholder'    => 'Ex: V-9F8A21',
            'btn_consultar'  => 'Consultar',
            'erro_nao_enc'   => 'Ticket não encontrado. Verifique o código e tente novamente.',
            'erro_data_inv'  => 'Não é possível remarcar para essa data. Escolha outra data disponível.',
            'erro_nao_pend'  => 'Esta marcação já não pode ser alterada.',
            'ok_remarcado'   => 'Nova data confirmada com sucesso!',
            'ok_cancelado'   => 'Consulta cancelada com sucesso.',
            'label_data'     => 'Data da Consulta',
            'label_hora'     => 'Hora',
            'hora_pendente'  => 'A confirmar pela recepção',
            'label_tipo'     => 'Tipo de Consulta',
            'normal'         => 'Normal',
            'urgente'        => 'Urgente',
            'label_medico'   => 'Médico',
            'medico_pend'    => 'A atribuir pela recepção',
            'label_estado'   => 'Estado',
            'est_pendente'   => 'Pendente',
            'est_concluido'  => 'Concluído',
            'est_cancelado'  => 'Cancelado',
            'btn_pdf'        => 'Descarregar Comprovativo (PDF)',
            'secao_gerir'    => 'Gerir Marcação',
            'label_remarcar' => 'Remarcar para outro dia',
            'btn_remarcar'   => 'Confirmar Nova Data',
            'btn_cancelar'   => 'Cancelar Consulta',
            'confirm_cancel' => 'Tem a certeza que deseja cancelar esta consulta? Esta acção não pode ser desfeita.',
            'aviso_concl'    => 'Esta consulta já foi concluída — não é possível alterá-la.',
            'aviso_cancel'   => 'Esta consulta foi cancelada.',
            'link_outro'     => '← Consultar outro ticket',
            'nota_seguranca' => 'Este ticket é pessoal e único — só quem o possui pode ver e gerir esta marcação.',
            'voltar_site'    => '← Voltar ao site principal',
            'escolha_data'   => '— Escolha uma data —',
            'aviso_hora_lim' => 'O horário de marcações terminou às 16h. A primeira data disponível é amanhã.',
        ],
        'en' => [
            'title'          => 'Check Appointment | Vida',
            'subtitulo'      => 'Enter your ticket to see your appointment details.',
            'label_ticket'   => 'Ticket Code',
            'placeholder'    => 'E.g.: V-9F8A21',
            'btn_consultar'  => 'Check',
            'erro_nao_enc'   => 'Ticket not found. Check the code and try again.',
            'erro_data_inv'  => 'This date is not available for rescheduling. Please pick another date.',
            'erro_nao_pend'  => 'This appointment can no longer be changed.',
            'ok_remarcado'   => 'New date confirmed successfully!',
            'ok_cancelado'   => 'Appointment cancelled successfully.',
            'label_data'     => 'Appointment Date',
            'label_hora'     => 'Time',
            'hora_pendente'  => 'To be confirmed by reception',
            'label_tipo'     => 'Appointment Type',
            'normal'         => 'Normal',
            'urgente'        => 'Urgent',
            'label_medico'   => 'Doctor',
            'medico_pend'    => 'To be assigned by reception',
            'label_estado'   => 'Status',
            'est_pendente'   => 'Pending',
            'est_concluido'  => 'Completed',
            'est_cancelado'  => 'Cancelled',
            'btn_pdf'        => 'Download Receipt (PDF)',
            'secao_gerir'    => 'Manage Appointment',
            'label_remarcar' => 'Reschedule to another day',
            'btn_remarcar'   => 'Confirm New Date',
            'btn_cancelar'   => 'Cancel Appointment',
            'confirm_cancel' => 'Are you sure you want to cancel this appointment? This cannot be undone.',
            'aviso_concl'    => 'This appointment has already been completed — it can no longer be changed.',
            'aviso_cancel'   => 'This appointment has been cancelled.',
            'link_outro'     => '← Check another ticket',
            'nota_seguranca' => 'This ticket is personal and unique — only the holder can view and manage this appointment.',
            'voltar_site'    => '← Back to main site',
            'escolha_data'   => '— Choose a date —',
            'aviso_hora_lim' => 'Booking hours ended at 4 PM. The first available date is tomorrow.',
        ],
    ];
    $t = $txt[$lang];
    $outro_lang       = $lang == 'pt' ? 'en' : 'pt';
    $outro_lang_label = $lang == 'pt' ? 'English' : 'Português';

    // ── Acções (POST): remarcar para outro dia OU cancelar ─────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ticketPost = strtoupper(trim($_POST['ticket'] ?? ''));
        $acao       = $_POST['acao'] ?? '';

        $stmt = db()->prepare("SELECT * FROM marcacoes WHERE ticket = ? LIMIT 1");
        $stmt->execute([$ticketPost]);
        $alvo = $stmt->fetch();

        if ($alvo && $alvo['estado'] === 'Pendente') {
            if ($acao === 'cancelar') {
                db()->prepare("UPDATE marcacoes SET estado = 'Cancelado' WHERE ticket = ?")->execute([$ticketPost]);
                header('Location: consulta.php?lang=' . $lang . '&ticket=' . urlencode($ticketPost) . '&ok=2');
                exit;
            }
            if ($acao === 'remarcar') {
                $novaData     = trim($_POST['nova_data'] ?? '');
                $hojeServidor = date('Y-m-d');
                $horaServidor = (int)date('H');

                $valida = (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $novaData) && $novaData >= $hojeServidor;
                if ($valida && $novaData === $hojeServidor && $horaServidor >= 16) $valida = false;

                if ($valida) {
                    db()->prepare("UPDATE marcacoes SET data = ?, hora = NULL WHERE ticket = ?")
                        ->execute([$novaData, $ticketPost]);
                    header('Location: consulta.php?lang=' . $lang . '&ticket=' . urlencode($ticketPost) . '&ok=1');
                    exit;
                }
                header('Location: consulta.php?lang=' . $lang . '&ticket=' . urlencode($ticketPost) . '&erro=1');
                exit;
            }
        }
        header('Location: consulta.php?lang=' . $lang . '&ticket=' . urlencode($ticketPost) . '&erro=2');
        exit;
    }

    // ── Notificação global activa ───────────────────────────────────────────────
    $notificacao = null;
    try {
        $n = get_notificacao();
        if ($n && $n['ativa']) $notificacao = $n;
    } catch (Exception $e) {
        // BD indisponível — ignora notificação
    }

    // ── Mensagens vindas de um redirect (PRG) ──────────────────────────────────
    $okMsg     = '';
    $erroAccao = '';
    if (isset($_GET['ok']))   $okMsg     = $_GET['ok']   == 1 ? $t['ok_remarcado']  : $t['ok_cancelado'];
    if (isset($_GET['erro'])) $erroAccao = $_GET['erro'] == 1 ? $t['erro_data_inv'] : $t['erro_nao_pend'];

    // ── Procurar marcação pelo ticket ────────────────────────────────────────
    $erroBusca      = '';
    $marcacao       = null;
    $ticketConsulta = trim($_GET['ticket'] ?? '');

    if ($ticketConsulta !== '') {
        $ticketBusca = strtoupper($ticketConsulta);
        $stmt = db()->prepare("SELECT * FROM marcacoes WHERE ticket = ? LIMIT 1");
        $stmt->execute([$ticketBusca]);
        $marcacao = $stmt->fetch();
        if (!$marcacao) $erroBusca = $t['erro_nao_enc'];
    }

    $podeGerir = $marcacao && $marcacao['estado'] === 'Pendente';

    if ($marcacao) {
        $dias_pt = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
        $dias_en = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $dataObj = DateTime::createFromFormat('Y-m-d', $marcacao['data']);
        if ($dataObj) {
            $nomeDia = $lang === 'pt' ? $dias_pt[(int)$dataObj->format('w')] : $dias_en[(int)$dataObj->format('w')];
            $dataFormatada = $dataObj->format('d/m/Y') . ' (' . $nomeDia . ')';
        } else {
            $dataFormatada = htmlspecialchars($marcacao['data']);
        }
        $horaFormatada   = formatar_hora($marcacao['hora']) ?: $t['hora_pendente'];
        $tipoFormatado   = $marcacao['urgencia'] === 'urgente' ? $t['urgente'] : $t['normal'];
        $medicoFormatado = $marcacao['medico'] !== '' ? $marcacao['medico'] : $t['medico_pend'];
        $estadoMap = [
            'Pendente'  => $t['est_pendente'],
            'Concluido' => $t['est_concluido'],
            'Cancelado' => $t['est_cancelado'],
        ];
        $estadoFormatado = $estadoMap[$marcacao['estado']] ?? $marcacao['estado'];
        $estadoCor = [
            'Pendente'  => ['bg' => '#fef3c7', 'fg' => '#92400e'],
            'Concluido' => ['bg' => '#dcfce7', 'fg' => '#166534'],
            'Cancelado' => ['bg' => '#fee2e2', 'fg' => '#991b1b'],
        ][$marcacao['estado']] ?? ['bg' => '#f1f5f9', 'fg' => '#475569'];
    }
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $t['title'] ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
        <style>
            * { font-family: 'Inter', sans-serif; }
            .brand { font-family: 'Playfair Display', serif; }
            .bg-page { background: linear-gradient(135deg, #0a1f44 0%, #1565c0 100%); min-height: 100vh; }
            @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
            .fade-up { animation: fadeUp .4s ease forwards; }
            input:focus, select:focus { border-color: #1565c0; box-shadow: 0 0 0 3px rgba(21,101,192,0.15); }
            .btn-primary { background: linear-gradient(135deg, #1565c0, #00b4d8); transition: opacity .2s, transform .2s; }
            .btn-primary:hover { opacity: .9; transform: scale(1.01); }
            @keyframes pulse-ring { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.1);opacity:0.7} }
            .notif-pulse { animation: pulse-ring 2s infinite; }
        </style>
    </head>
    <body class="bg-page flex items-start justify-center p-4 py-10">

        <?php if ($notificacao): ?>
        <div class="fixed top-0 left-0 w-full bg-amber-500 text-white py-3 px-6 flex items-center justify-between z-50">
            <div class="flex items-center gap-3 max-w-3xl mx-auto">
                <span class="text-xl notif-pulse">⚠️</span>
                <strong class="text-sm"><?= htmlspecialchars($notificacao['mensagem_' . $lang] ?? $notificacao['mensagem_pt']) ?></strong>
            </div>
        </div>
        <?php endif; ?>

        <div class="w-full max-w-lg fade-up <?= $notificacao ? 'mt-12' : '' ?>">
            <div class="text-center mb-7">
                <a href="index.php?lang=<?= $lang ?>" class="brand text-white text-3xl">
                    <span class="text-cyan-400">Vida</span> Centro de Saúde
                </a>
                <p class="text-blue-200 text-sm mt-2"><?= $t['subtitulo'] ?></p>
            </div>

            <div class="bg-white rounded-3xl p-8 shadow-2xl">

                <!-- FORM DE PESQUISA -->
                <form method="GET" action="consulta.php" class="<?= $marcacao ? 'mb-6 pb-6 border-b border-gray-100' : '' ?>">
                    <input type="hidden" name="lang" value="<?= $lang ?>">
                    <label class="block text-sm font-semibold text-gray-600 mb-2"><?= $t['label_ticket'] ?></label>
                    <div class="flex gap-2">
                        <input type="text" name="ticket" required autocomplete="off"
                            value="<?= htmlspecialchars($ticketConsulta) ?>"
                            placeholder="<?= $t['placeholder'] ?>"
                            style="font-family:monospace;letter-spacing:.05em;text-transform:uppercase;"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-gray-700 focus:outline-none transition">
                        <button type="submit"
                            class="btn-primary text-white px-6 rounded-xl font-bold text-sm whitespace-nowrap">
                            <?= $t['btn_consultar'] ?>
                        </button>
                    </div>
                    <?php if ($erroBusca): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm mt-4 flex items-center gap-2">
                        <span>⚠️</span> <?= htmlspecialchars($erroBusca) ?>
                    </div>
                    <?php endif; ?>
                </form>

                <?php if ($marcacao): ?>

                <?php if ($okMsg): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm mb-5 flex items-center gap-2">
                    <span>✅</span> <?= htmlspecialchars($okMsg) ?>
                </div>
                <?php endif; ?>
                <?php if ($erroAccao): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm mb-5 flex items-center gap-2">
                    <span>⚠️</span> <?= htmlspecialchars($erroAccao) ?>
                </div>
                <?php endif; ?>

                <!-- TICKET + ESTADO -->
                <div class="flex items-center justify-between mb-5">
                    <div class="font-bold text-xl tracking-widest" style="color:#0a1f44;font-family:monospace;">
                        <?= htmlspecialchars($marcacao['ticket']) ?>
                    </div>
                    <span class="text-xs font-bold px-3 py-1.5 rounded-full" style="background:<?= $estadoCor['bg'] ?>;color:<?= $estadoCor['fg'] ?>;">
                        <?= $estadoFormatado ?>
                    </span>
                </div>

                <!-- DADOS BÁSICOS -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1"><?= $t['label_data'] ?></div>
                        <div class="font-semibold text-gray-800 text-sm"><?= $dataFormatada ?></div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1"><?= $t['label_hora'] ?></div>
                        <div class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($horaFormatada) ?></div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1"><?= $t['label_tipo'] ?></div>
                        <div class="font-semibold text-gray-800 text-sm"><?= $tipoFormatado ?></div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1"><?= $t['label_medico'] ?></div>
                        <div class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($medicoFormatado) ?></div>
                    </div>
                </div>

                <a href="recibo.php?ticket=<?= urlencode($marcacao['ticket']) ?>" target="_blank"
                    class="btn-primary w-full text-white py-3 rounded-full font-bold mb-6 inline-flex items-center justify-center gap-2 text-sm">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                    <?= $t['btn_pdf'] ?>
                </a>

                <?php if ($podeGerir): ?>
                <!-- GERIR MARCAÇÃO -->
                <div class="border-t border-gray-100 pt-6">
                    <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wide"><?= $t['secao_gerir'] ?></h3>

                    <form method="POST" action="consulta.php" class="mb-4">
                        <input type="hidden" name="ticket" value="<?= htmlspecialchars($marcacao['ticket']) ?>">
                        <input type="hidden" name="acao" value="remarcar">
                        <label class="block text-xs font-semibold text-gray-600 mb-2"><?= $t['label_remarcar'] ?></label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <select id="sel-data-resched" name="nova_data" required
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:outline-none transition">
                                <option value=""><?= $t['escolha_data'] ?></option>
                            </select>
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm whitespace-nowrap transition">
                                <?= $t['btn_remarcar'] ?>
                            </button>
                        </div>
                        <div id="aviso-hora-resched" style="display:none" class="mt-2 bg-amber-50 border border-amber-300 text-amber-700 rounded-xl px-3 py-2 text-xs font-medium">
                            <?= $t['aviso_hora_lim'] ?>
                        </div>
                    </form>

                    <form method="POST" action="consulta.php"
                        onsubmit="return confirm(<?= json_encode($t['confirm_cancel']) ?>)">
                        <input type="hidden" name="ticket" value="<?= htmlspecialchars($marcacao['ticket']) ?>">
                        <input type="hidden" name="acao" value="cancelar">
                        <button type="submit"
                            class="w-full border-2 border-red-200 text-red-600 hover:bg-red-50 py-2.5 rounded-xl font-semibold text-sm transition">
                            <?= $t['btn_cancelar'] ?>
                        </button>
                    </form>
                </div>
                <?php elseif ($marcacao['estado'] === 'Concluido'): ?>
                <p class="text-center text-sm text-gray-400 border-t border-gray-100 pt-5"><?= $t['aviso_concl'] ?></p>
                <?php else: ?>
                <p class="text-center text-sm text-gray-400 border-t border-gray-100 pt-5"><?= $t['aviso_cancel'] ?></p>
                <?php endif; ?>

                <p class="text-center text-xs text-gray-400 mt-6"><?= $t['nota_seguranca'] ?></p>

                <div class="text-center mt-3">
                    <a href="consulta.php?lang=<?= $lang ?>" class="text-blue-500 hover:text-blue-700 text-xs font-semibold transition"><?= $t['link_outro'] ?></a>
                </div>

                <?php endif; ?>
            </div>

            <div class="text-center mt-6">
                <a href="index.php?lang=<?= $lang ?>" class="text-blue-200/60 hover:text-blue-200 text-xs transition">
                    <?= $t['voltar_site'] ?>
                </a>
            </div>
        </div>

        <!-- SELECTOR DE IDIOMA — flutuante, fora de qualquer navbar -->
        <a href="?lang=<?= $outro_lang ?><?= $ticketConsulta !== '' ? '&ticket=' . urlencode($ticketConsulta) : '' ?>" id="lang-float"
            class="fixed z-40 bottom-5 right-5 bg-white shadow-lg hover:shadow-xl text-xs font-bold px-4 py-2.5 rounded-full flex items-center gap-2 transition"
            style="color:#0a1f44;">
            <svg width="14" height="14" fill="none" stroke="#0a1f44" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
            <?= $outro_lang_label ?>
        </a>

        <?php if ($podeGerir): ?>
        <script>
        // Gera datas disponíveis para remarcação, usando o relógio LOCAL do browser
        (function() {
            const lang    = <?= json_encode($lang) ?>;
            const isPt    = (lang === 'pt');
            const dias_pt = ['','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
            const dias_en = ['','Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            const sel     = document.getElementById('sel-data-resched');
            const aviso   = document.getElementById('aviso-hora-resched');
            if (!sel) return;

            const agora        = new Date();
            const hora         = agora.getHours();
            const HORA_LIMITE  = 16;
            const passouLimite = hora >= HORA_LIMITE;

            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);

            if (passouLimite && aviso) aviso.style.display = 'block';

            let adicionados = 0;
            let offset      = passouLimite ? 1 : 0;

            while (adicionados < 15) {
                const d = new Date(hoje);
                d.setDate(hoje.getDate() + offset);
                const diaSemana = d.getDay();

                if (diaSemana !== 0 && diaSemana !== 6) {
                    const yyyy = d.getFullYear();
                    const mm   = String(d.getMonth() + 1).padStart(2, '0');
                    const dd   = String(d.getDate()).padStart(2, '0');
                    const val  = `${yyyy}-${mm}-${dd}`;

                    const nIdx    = diaSemana === 0 ? 7 : diaSemana;
                    const nomeDia = isPt ? dias_pt[nIdx] : dias_en[nIdx];

                    let label = `${dd}/${mm}/${yyyy} (${nomeDia})`;
                    if (offset === 0) label += isPt ? ' — Hoje' : ' — Today';

                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = label;
                    sel.appendChild(opt);
                    adicionados++;
                }
                offset++;
            }
        })();
        </script>
        <?php endif; ?>
    </body>
</html>
