<?php
    session_start();
    require_once __DIR__ . '/db.php';

    if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'recepcionista') {
        header('Location: login.php'); exit;
    }

    $pdo = db();
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // ── POST: guardar marcação ────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

        if ($_POST['action'] === 'update') {
            $ticket   = trim($_POST['ticket']   ?? '');
            $medico   = trim($_POST['medico']   ?? '');
            $processo = trim($_POST['processo'] ?? '');
            $estado   = trim($_POST['estado']   ?? 'Pendente');
            $horaPost = trim($_POST['hora']     ?? '');
            $hora     = hora_valida($horaPost) ? $horaPost : null;

            // Regras de negócio
            if ($estado === 'Em atendimento') $estado = 'Pendente';
            if (is_medico_clinica($medico) && $estado === 'Concluido') $estado = 'Pendente';
            if (empty($medico) && $estado === 'Concluido') $estado = 'Pendente';

            $stmt = $pdo->prepare("
                UPDATE marcacoes
                SET medico = ?, processo = ?, estado = ?, hora = ?
                WHERE ticket = ?
            ");
            $stmt->execute([$medico, $processo, $estado, $hora, $ticket]);

            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true, 'type' => 1]);
                exit;
            }
            header('Location: recepcionista.php?ok=1'); exit;
        }

        if ($_POST['action'] === 'notificar') {
            $msg_pt = trim($_POST['msg_pt'] ?? '');
            $msg_en = trim($_POST['msg_en'] ?? '');
            $ativa  = (int)($_POST['ativa'] ?? 0);

            // Upsert: mantém sempre apenas 1 linha (a mais recente)
            $existe = $pdo->query("SELECT id FROM notificacoes ORDER BY id DESC LIMIT 1")->fetch();
            if ($existe) {
                $pdo->prepare("UPDATE notificacoes SET ativa=?, mensagem_pt=?, mensagem_en=?, criado_em=NOW() WHERE id=?")
                    ->execute([$ativa, $msg_pt, $msg_en, $existe['id']]);
            } else {
                $pdo->prepare("INSERT INTO notificacoes (ativa, mensagem_pt, mensagem_en) VALUES (?,?,?)")
                    ->execute([$ativa, $msg_pt, $msg_en]);
            }

            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true, 'type' => 2]);
                exit;
            }
            header('Location: recepcionista.php?ok=2'); exit;
        }
    }

    // ── LEITURA DE DADOS ──────────────────────────────────────────────────────────
    $hoje = date('Y-m-d');

    // Stats
    $stat = $pdo->prepare("
        SELECT
            COUNT(*)                                                              AS total_hoje,
            COUNT(*) FILTER (WHERE estado = 'Pendente')                          AS pendentes,
            COUNT(*) FILTER (WHERE cliente = 'novo')                             AS novos,
            COUNT(*) FILTER (WHERE urgencia = 'urgente')                         AS urgentes
        FROM marcacoes WHERE data = ?
    ");
    $stat->execute([$hoje]);
    $stats = $stat->fetch();

    // Gráfico: últimos 7 dias
    $chart_labels = [];
    $chart_vals   = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = date('d/m', strtotime($d));
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM marcacoes WHERE data = ?");
        $cnt->execute([$d]);
        $chart_vals[] = (int)$cnt->fetchColumn();
    }

    // Marcações de hoje ordenadas: activas primeiro, depois concluídas/canceladas
    $mhoje = $pdo->prepare("
        SELECT *,
            CASE WHEN estado IN ('Concluido','Cancelado') THEN 1 ELSE 0 END AS ordem
        FROM marcacoes
        WHERE data = ?
        ORDER BY ordem ASC, criado_em ASC
    ");
    $mhoje->execute([$hoje]);
    $marcacoes_hoje = $mhoje->fetchAll();

    // Notificação actual
    $notif_atual = get_notificacao() ?? ['ativa'=>0,'mensagem_pt'=>'','mensagem_en'=>''];

    // Pool de processos disponíveis
    $pool_processos = pool_processos();

    $medicos_lista    = $GLOBALS['MEDICOS_LISTA'];
    $medicos_externos = $GLOBALS['MEDICOS_EXTERNOS'];
?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Receção | Vida Centro de Saúde</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
        <style>
            :root {
                --navy: #dde3ed;
                --navy2: #e8edf5;
                --card: rgba(255,255,255,0.85);
                --border: rgba(15,60,120,0.10);
                --teal: #0fd4c8;
                --teal2: #0ab5ab;
                --accent: #3b82f6;
                --gold: #f59e0b;
                --red: #ef4444;
                --green: #10b981;
                --text: #0d1117;
                --muted: #4a5568;
            }
            * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
            body { background: var(--navy); color: var(--text); min-height: 100vh; }
            .brand-font { font-family: 'DM Serif Display', serif; }
            
            /* Sidebar */
            .sidebar {
                background: linear-gradient(180deg, #1a3a6b 0%, #0f2755 100%);
                border-right: 1px solid var(--border);
                width: 220px; flex-shrink: 0;
            }
            .nav-item {
                display: flex; align-items: center; gap: 10px;
                padding: 10px 14px; border-radius: 10px;
                font-size: 13.5px; font-weight: 500;
                color: rgba(255,255,255,0.88); transition: all .2s; cursor: pointer;
                text-decoration: none;
            }
            .nav-item:hover { background: rgba(255,255,255,0.1); color: #ffffff; }
            .nav-item.active { background: rgba(15,212,200,0.12); color: var(--teal); }
            
            /* Cards */
            .card {
                background: #ffffff;
                border: 1px solid var(--border);
                border-radius: 16px;
                backdrop-filter: blur(8px);
            }
            .stat-card {
                background: #ffffff;
                border: 1px solid var(--border);
                border-radius: 14px;
                padding: 20px 22px;
                transition: transform .2s, border-color .2s;
            }
            .stat-card:hover { transform: translateY(-2px); border-color: rgba(15,212,200,0.25); }
            
            /* Table */
            .data-table { width: 100%; border-collapse: collapse; }
            .data-table thead th {
                padding: 11px 16px; text-align: left;
                font-size: 11px; font-weight: 600; letter-spacing: .08em;
                text-transform: uppercase; color: var(--muted);
                border-bottom: 1px solid var(--border);
            }
            .data-table tbody tr { border-bottom: 1px solid rgba(255,255,255,0.04); transition: background .15s; }
            .data-table tbody tr:hover { background: #ffffff; }
            .data-table tbody td { padding: 12px 16px; font-size: 13.5px; vertical-align: middle; }
            
            /* Badges */
            .badge { display:inline-flex; align-items:center; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; letter-spacing:.03em; }
            .badge-urgent  { background: rgba(239,68,68,.15);  color: #fca5a5; border:1px solid rgba(239,68,68,.25); }
            .badge-normal  { background: rgba(107,122,153,.12); color: var(--muted); border:1px solid rgba(107,122,153,.2); }
            .badge-novo    { background: rgba(16,185,129,.12); color: #6ee7b7; border:1px solid rgba(16,185,129,.2); }
            .badge-antigo  { background: rgba(107,122,153,.08); color: #9ca3af; border:1px solid rgba(107,122,153,.15); font-size:11px; }
            .badge-pendente { background: rgba(245,158,11,.12); color: #fcd34d; border:1px solid rgba(245,158,11,.2); }
            .badge-concluido{ background: rgba(16,185,129,.12); color: #6ee7b7; border:1px solid rgba(16,185,129,.2); }
            .badge-cancelado{ background: rgba(239,68,68,.12);  color: #fca5a5; border:1px solid rgba(239,68,68,.2); }
            
            /* Ticket pill */
            .ticket-pill {
                font-family: 'DM Mono', 'Courier New', monospace;
                font-size: 11.5px; font-weight: 700;
                padding: 4px 10px; border-radius: 7px;
                background: rgba(15,212,200,.1); color: var(--teal);
                border: 1px solid rgba(15,212,200,.2);
                letter-spacing: .05em;
            }
            .ticket-pill.done {
                background: rgba(107,122,153,.1); color: var(--muted);
                border-color: rgba(107,122,153,.2);
            }
            /* linhas concluídas/canceladas — fundo colorido subtil, sem opacity baixa */
            tr.tr-concluido { background: rgba(16,185,129,0.07); }
            tr.tr-cancelado { background: rgba(239,68,68,0.07); }
            /* linha pendente com médico atribuído (à espera do médico interno) */
            tr.tr-aguarda   { background: rgba(245,158,11,0.06); }
            /* label de estado bloqueado */
            .estado-lock {
                display:inline-flex; align-items:center; gap:5px;
                font-size:12px; font-weight:600; color: var(--muted);
            }
            
            /* Form controls */
            select, input[type=text], input[type=time] {
                background: rgba(15,60,120,0.06);
                border: 1px solid rgba(15,60,120,0.12);
                border-radius: 8px; padding: 6px 10px;
                font-size: 12.5px; color: var(--text);
                transition: border-color .2s;
            }
            select:focus, input[type=text]:focus, input[type=time]:focus {
                outline: none;
                border-color: var(--teal);
                box-shadow: 0 0 0 3px rgba(15,212,200,.1);
            }
            select option { background: #e8edf5; }
            
            /* Buttons */
            .btn-primary {
                background: linear-gradient(135deg, var(--teal), #0891b2);
                color: #fff; font-weight: 600; font-size: 12px;
                padding: 8px 18px; border-radius: 8px;
                border: none; cursor: pointer; transition: opacity .2s, transform .15s;
            }
            .btn-primary:hover { opacity: .9; transform: translateY(-1px); }
            .btn-clinica {
                background: linear-gradient(135deg, #6366f1, #4f46e5);
                color: #fff; font-weight: 600; font-size: 12px;
                padding: 8px 18px; border-radius: 8px;
                border: none; cursor: pointer; transition: opacity .2s;
            }
            .btn-clinica:hover { opacity: .9; }
            .btn-ghost {
                background: rgba(15,60,120,0.06);
                border: 1px solid var(--border);
                color: var(--muted); font-size: 12px; font-weight: 500;
                padding: 8px 14px; border-radius: 8px; cursor: pointer; transition: all .2s;
            }
            .btn-ghost:hover { background: rgba(15,60,120,0.15); color: var(--text); }
            
            /* Chart area */
            .chart-wrap { position: relative; }
            
            /* Animations */
            @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
            .fade-up { animation: fadeUp .35s ease forwards; }
            @keyframes pulse-dot { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.5);opacity:.6} }
            .live-dot { width:7px;height:7px;border-radius:50%;background:var(--green);animation:pulse-dot 1.6s infinite; }
            
            /* Header */
            .topbar {
                background: rgba(255,255,255,0.92);
                backdrop-filter: blur(20px);
                border-bottom: 1px solid var(--border);
            }
            
            /* Notification form */
            .notif-form input[type=text] { width: 100%; margin-bottom: 10px; }
            
            /* Scrollbar */
            ::-webkit-scrollbar { width: 5px; height: 5px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: rgba(15,60,120,0.15); border-radius: 10px; }

            /* Pagination */
            .pg-btn {
                display:inline-flex;align-items:center;justify-content:center;
                min-width:30px;height:30px;padding:0 8px;border-radius:7px;
                font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;
                border: 1px solid var(--border); background: transparent; color: var(--muted);
            }
            .pg-btn.active { background: var(--teal); color: #fff; border-color: var(--teal); }
            .pg-btn:hover:not(.active):not(:disabled) { background: rgba(15,60,120,0.07); color: var(--text); }
            .pg-btn:disabled { opacity: .3; cursor: not-allowed; }
        </style>
    </head>
    <body class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="sidebar hidden md:flex flex-col p-5 sticky top-0 h-screen">
        <div class="mb-8 px-2">
            <div class="brand-font text-2xl text-white"><span style="color:var(--teal)">Vida</span></div>
            <div style="color:#ffffff;font-size:11px;margin-top:2px;letter-spacing:.08em;text-transform:uppercase;font-weight:600;opacity:0.85;">Centro de Saúde</div>
        </div>
        <nav class="flex-1 space-y-1">
            <a href="recepcionista.php" class="nav-item active">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Painel
            </a>
            <a href="historico.php" class="nav-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Histórico
            </a>
        </nav>
        <div style="border-top:1px solid rgba(255,255,255,0.18);padding-top:16px;">
            <p style="color:#ffffff;font-size:14px;font-weight:600;"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
            <p style="color:rgba(255,255,255,0.75);font-size:11.5px;margin-top:3px;font-weight:500;letter-spacing:.03em;">Recepcionista</p>
            <a href="logout.php" style="color:#fca5a5;font-size:12.5px;font-weight:600;margin-top:8px;display:inline-block;transition:color .2s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#fca5a5'">→ Terminar Sessão</a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 overflow-x-hidden flex flex-col">
        <!-- TOPBAR -->
        <header class="topbar px-6 py-4 flex justify-between items-center sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <div class="live-dot"></div>
                <div>
                    <h1 class="font-semibold text-base" style="color:var(--text)">Gestão de Atendimento</h1>
                    <p style="color:var(--muted);font-size:11.5px;"><?= date('l, d \d\e F \d\e Y') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3" id="topbar-feedback">
                <?php if (isset($_GET['ok'])): ?>
                <span class="badge badge-concluido fade-up">
                    <?= $_GET['ok'] == 1 ? '✓ Guardado!' : '✓ Notificação enviada!' ?>
                </span>
                <?php endif; ?>
                <a href="logout.php" class="md:hidden" style="color:#f87171;font-size:13px;">Sair</a>
            </div>
        </header>

        <main class="p-6 space-y-6 fade-up">

            <!-- STATS GRID -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <?php
                $stat_items = [
                    [$stats['total_hoje'], 'Marcações Hoje',    'var(--teal)',   'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    [$stats['pendentes'],  'Pendentes',         'var(--gold)',   'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    [$stats['novos'],      'Novos Pacientes',   'var(--green)',  'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                    [$stats['urgentes'],   'Urgentes',          'var(--red)',    'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                ];
                foreach ($stat_items as $s): ?>
                <div class="stat-card">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:rgba(15,60,120,0.06);display:flex;align-items:center;justify-content:center;">
                            <svg width="18" height="18" fill="none" stroke="<?= $s[2] ?>" stroke-width="1.8" viewBox="0 0 24 24"><path d="<?= $s[3] ?>"/></svg>
                        </div>
                    </div>
                    <div style="font-size:32px;font-weight:700;color:var(--text);line-height:1;"><?= $s[0] ?></div>
                    <div style="color:var(--muted);font-size:12px;margin-top:6px;"><?= $s[1] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- CHART + NOTIFICATION -->
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="card p-6">
                    <h3 style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:20px;">Fluxo — Últimos 7 Dias</h3>
                    <div class="chart-wrap">
                        <canvas id="chartSemanal" height="140"></canvas>
                    </div>
                </div>
                <div class="card p-6">
                    <h3 style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:20px;">Notificação de Cancelamento</h3>
                    <form method="POST" class="notif-form" data-notif>
                        <input type="hidden" name="action" value="notificar">
                        <div style="margin-bottom:12px;">
                            <label style="display:block;font-size:11px;font-weight:600;color:var(--muted);margin-bottom:5px;letter-spacing:.04em;text-transform:uppercase;">Mensagem (PT)</label>
                            <input type="text" name="msg_pt" value="<?= htmlspecialchars($notif_atual['mensagem_pt']) ?>" placeholder="Ex: Consultas suspensas a 20/01...">
                        </div>
                        <div style="margin-bottom:12px;">
                            <label style="display:block;font-size:11px;font-weight:600;color:var(--muted);margin-bottom:5px;letter-spacing:.04em;text-transform:uppercase;">Message (EN)</label>
                            <input type="text" name="msg_en" value="<?= htmlspecialchars($notif_atual['mensagem_en']) ?>" placeholder="Ex: Consultations on 20/01 cancelled">
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                            <label style="font-size:12px;color:var(--muted);font-weight:500;">Activa?</label>
                            <select name="ativa">
                                <option value="1" <?= $notif_atual['ativa'] ? 'selected' : '' ?>>Sim</option>
                                <option value="0" <?= !$notif_atual['ativa'] ? 'selected' : '' ?>>Não</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary">Guardar Notificação</button>
                    </form>
                </div>
            </div>

            <!-- TODAY TABLE -->
            <div class="card overflow-hidden">
                <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h3 style="font-size:15px;font-weight:600;color:var(--text);">Tickets de Hoje</h3>
                        <p style="color:var(--muted);font-size:12px;margin-top:2px;"><?= date('d/m/Y') ?></p>
                    </div>
                    <span class="badge badge-novo"><?= count($marcacoes_hoje) ?> marcações</span>
                </div>
                <div style="padding:10px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <span id="infoRecep" style="font-size:11.5px;color:var(--muted);"></span>
                    <div id="botoesRecep" style="display:flex;gap:4px;"></div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Nº Processo</th>
                                <th>Estado</th>
                                <th>Acção</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyRecep">
                        <?php if (empty($marcacoes_hoje)): ?>
                            <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--muted);">Nenhuma marcação para hoje.</td></tr>
                        <?php else: ?>
                        <?php foreach ($marcacoes_hoje as $m):
                            $med_saved   = $m['medico'] ?? '';
                            $estado_real = $m['estado'];
                            // Situação A: terminada (Concluido ou Cancelado) — linha fechada
                            $terminada   = in_array($estado_real, ['Concluido','Cancelado']);
                            // Situação B: médico interno atribuído e pendente — aguarda médico
                            $aguarda_int = !$terminada
                                           && !empty($med_saved)
                                           && is_medico_clinica($med_saved)
                                           && in_array($estado_real, ['Pendente','Em atendimento']);
                            // Situação C: médico externo — recepcionista pode editar até concluir
                            $ext_editavel = !$terminada
                                            && !empty($med_saved)
                                            && is_medico_externo($med_saved);
                            // Situação D: ainda sem médico — recepcionista atribui
                            $por_atribuir = !$terminada && !$aguarda_int && !$ext_editavel;

                            $estado_safe = $estado_real === 'Em atendimento' ? 'Pendente' : $estado_real;

                            // classe CSS da linha
                            if ($terminada && $estado_real === 'Concluido') $tr_class = 'tr-concluido';
                            elseif ($terminada && $estado_real === 'Cancelado') $tr_class = 'tr-cancelado';
                            elseif ($aguarda_int) $tr_class = 'tr-aguarda';
                            else $tr_class = '';
                        ?>
                        <tr class="<?= $tr_class ?>" data-ticket="<?= htmlspecialchars($m['ticket']) ?>">

                            <td><span class="ticket-pill <?= $terminada ? 'done' : '' ?>"><?= htmlspecialchars($m['ticket']) ?></span></td>

                            <!-- HORA -->
                            <td>
                                <?php if ($terminada || $aguarda_int): ?>
                                    <span style="font-family:monospace;font-size:12.5px;color:var(--text);"><?= htmlspecialchars(formatar_hora($m['hora']) ?: '—') ?></span>
                                <?php else: ?>
                                    <input type="time" name="hora" class="inp-hora" style="min-width:100px;"
                                           value="<?= htmlspecialchars(formatar_hora($m['hora'])) ?>">
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= $m['urgencia'] === 'urgente'
                                    ? '<span class="badge badge-urgent">URGENTE</span>'
                                    : '<span class="badge badge-normal">Normal</span>' ?>
                            </td>
                            <td>
                                <?= $m['cliente'] === 'novo'
                                    ? '<span class="badge badge-novo">Novo</span>'
                                    : '<span class="badge badge-antigo">Antigo</span>' ?>
                            </td>

                            <!-- MÉDICO -->
                            <td>
                                <?php if ($terminada || $aguarda_int): ?>
                                    <span style="font-size:12.5px;font-weight:500;color:var(--text);"><?= htmlspecialchars($med_saved ?: '—') ?></span>
                                    <?php if ($aguarda_int): ?>
                                    <br><small style="color:var(--muted);font-size:10.5px;">A aguardar médico</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <select name="medico" class="sel-medico" style="min-width:170px;"
                                            data-saved="<?= htmlspecialchars($med_saved) ?>">
                                        <option value="">— Atribuir médico —</option>
                                        <?php foreach ($medicos_lista as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $med_saved === $opt ? 'selected' : '' ?>>
                                            <?= $opt ?><?= $opt === MEDICO_CLINICA ? ' (Clínica)' : ' (Ext.)' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>

                            <!-- PROCESSO -->
                            <td>
                                <?php if ($terminada || $aguarda_int): ?>
                                    <span style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($m['processo'] ?: '—') ?></span>
                                <?php elseif (!empty($m['processo'])): ?>
                                    <span style="font-family:monospace;font-size:12px;padding:3px 8px;background:rgba(15,60,120,0.06);border-radius:6px;color:var(--text);"><?= htmlspecialchars($m['processo']) ?></span>
                                    <!-- processo já atribuído, passa via JS do data-proc -->
                                <?php elseif ($m['cliente'] === 'novo'): ?>
                                    <select name="processo" style="min-width:130px;border-color:rgba(245,158,11,.4);">
                                        <option value="">— Atribuir nº —</option>
                                        <?php foreach ($pool_processos as $pnum): ?>
                                        <option value="<?= $pnum ?>"><?= $pnum ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <select name="processo" style="min-width:130px;">
                                        <option value="">— Atribuir —</option>
                                        <?php foreach ($pool_processos as $pnum): ?>
                                        <option value="<?= $pnum ?>"><?= $pnum ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <!-- guarda o processo já existente para o JS usar -->
                                <span class="d-none" data-proc="<?= htmlspecialchars($m['processo'] ?? '') ?>" style="display:none"></span>
                            </td>

                            <!-- ESTADO -->
                            <td>
                                <?php if ($terminada): ?>
                                    <?php
                                    $ebadge = $estado_real === 'Concluido' ? 'badge-concluido' : 'badge-cancelado';
                                    $elabel = $estado_real === 'Concluido' ? 'Concluído' : 'Cancelado';
                                    echo "<span class='badge $ebadge'>$elabel</span>";
                                    ?>
                                <?php elseif ($aguarda_int): ?>
                                    <span class="badge badge-pendente">Pendente</span>
                                <?php else: ?>
                                    <select name="estado" class="sel-estado"
                                            data-saved-estado="<?= htmlspecialchars($estado_safe) ?>">
                                    </select>
                                <?php endif; ?>
                            </td>

                            <!-- ACÇÃO -->
                            <td>
                                <?php if ($terminada || $aguarda_int): ?>
                                    <span style="color:var(--muted);font-size:11.5px;">
                                        <?= $aguarda_int ? '🔒 Bloqueado' : '✓ Fechado' ?>
                                    </span>
                                <?php else: ?>
                                    <button type="button" class="btn-acao"
                                            onclick="submeterLinha(this)"></button>
                                <?php endif; ?>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        const MEDICO_CLINICA   = <?= json_encode(MEDICO_CLINICA) ?>;
        const MEDICOS_EXTERNOS = <?= json_encode(array_values($medicos_externos)) ?>;

        function mostrarToast(msg, ok) {
            let t = document.getElementById('toast-g');
            if (!t) {
                t = Object.assign(document.createElement('div'), { id: 'toast-g' });
                t.style.cssText = 'position:fixed;top:20px;right:24px;z-index:9999;padding:10px 22px;border-radius:10px;font-size:13px;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,.4);transition:opacity .3s;border:1px solid;';
                document.body.appendChild(t);
            }
            t.textContent = msg;
            if (ok) { t.style.background='rgba(16,185,129,.15)'; t.style.color='#6ee7b7'; t.style.borderColor='rgba(16,185,129,.3)'; }
            else { t.style.background='rgba(239,68,68,.15)'; t.style.color='#fca5a5'; t.style.borderColor='rgba(239,68,68,.3)'; }
            t.style.opacity = '1';
            clearTimeout(t._h);
            t._h = setTimeout(() => t.style.opacity = '0', 3500);
        }

        function spinner(show) {
            let o = document.getElementById('sp');
            if (!o && show) {
                o = document.createElement('div'); o.id = 'sp';
                o.style.cssText = 'position:fixed;inset:0;background:rgba(240,244,248,0.85);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9998;';
                o.innerHTML = '<div style="background:rgba(15,60,120,0.06);border:1px solid rgba(15,60,120,0.12);border-radius:16px;padding:28px 36px;text-align:center"><div style="width:36px;height:36px;border:3px solid rgba(255,255,255,.1);border-top-color:#0fd4c8;border-radius:50%;animation:sp .7s linear infinite;margin:0 auto 12px"></div><p style="font-size:13px;font-weight:600;color:#1a2540">A guardar…</p></div><style>@keyframes sp{to{transform:rotate(360deg)}}</style>';
                document.body.appendChild(o);
            }
            if (o) o.style.display = show ? 'flex' : 'none';
        }

        // ── Submeter linha da tabela (recolhe dados do TR directamente) ──────────
        function submeterLinha(btn) {
            const tr      = btn.closest('tr');
            const ticket  = tr.dataset.ticket;
            const medico  = (tr.querySelector('[name=medico]')  || {value:''}).value;
            // processo: select se existir, senão o data-proc já guardado
            const selProc = tr.querySelector('[name=processo]');
            const dataProc= (tr.querySelector('[data-proc]') || {dataset:{proc:''}}).dataset.proc;
            const processo = selProc ? selProc.value : dataProc;
            const estado  = (tr.querySelector('[name=estado]')  || {value:'Pendente'}).value;
            const hora    = (tr.querySelector('[name=hora]')    || {value:''}).value;

            if (!medico && !hora) { mostrarToast('Seleccione um médico ou defina a hora primeiro.', false); return; }

            const fd = new FormData();
            fd.append('action',   'update');
            fd.append('ticket',   ticket);
            fd.append('medico',   medico);
            fd.append('processo', processo);
            fd.append('estado',   estado);
            fd.append('hora',     hora);

            spinner(true);
            const ini = Date.now();
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(r => r.json())
            .then(res => {
                const rest = Math.max(0, 900 - (Date.now() - ini));
                setTimeout(() => {
                    spinner(false);
                    mostrarToast('✓ Guardado com sucesso!', res.ok);
                }, rest);
            })
            .catch(() => { spinner(false); mostrarToast('Erro de ligação.', false); });
        }

        // ── Submeter form de notificação ──────────────────────────────────────────
        function ajaxForm(form) {
            const fd = new FormData(form);
            spinner(true);
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(r => r.json())
            .then(res => {
                spinner(false);
                mostrarToast('✓ Notificação guardada!', res.ok);
            })
            .catch(() => { spinner(false); mostrarToast('Erro de ligação.', false); });
        }

        // ── Actualizar selects + botão ao escolher médico ─────────────────────────
        function actualizarLinha(sel) {
            const tr     = sel.closest('tr');
            const medico  = sel.value;
            const selEst  = tr.querySelector('.sel-estado');
            const btn     = tr.querySelector('.btn-acao');
            const inpHora = tr.querySelector('.inp-hora');
            if (!selEst || !btn) return;
            const saved = selEst.dataset.savedEstado || 'Pendente';
            const isClinica = medico === MEDICO_CLINICA;
            const isExterno = MEDICOS_EXTERNOS.includes(medico);
            const opcoes = isExterno
                ? [['Pendente','Pendente'],['Concluido','Concluído'],['Cancelado','Cancelado']]
                : [['Pendente','Pendente'],['Cancelado','Cancelado']];
            selEst.innerHTML = '';
            opcoes.forEach(([v, l]) => {
                const o = document.createElement('option');
                o.value = v; o.textContent = l;
                if (v === saved) o.selected = true;
                selEst.appendChild(o);
            });
            if (!selEst.value) selEst.value = 'Pendente';
            btn.disabled = false;
            if (isClinica) {
                btn.textContent = 'Mandar Consulta';
                btn.className = 'btn-clinica btn-acao';
            } else if (isExterno) {
                btn.textContent = 'Guardar';
                btn.className = 'btn-primary btn-acao';
            } else {
                btn.textContent = 'Guardar';
                btn.className = 'btn-ghost btn-acao';
                // permite guardar só a hora antes de atribuir médico
                btn.disabled = !(inpHora && inpHora.value);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Inicializar cada linha
            document.querySelectorAll('.sel-medico').forEach(s => {
                actualizarLinha(s);
                s.addEventListener('change', () => actualizarLinha(s));
            });
            // Hora: ao preencher antes de atribuir médico, reavalia se já pode guardar
            document.querySelectorAll('.inp-hora').forEach(inp => {
                inp.addEventListener('input', () => {
                    const selMed = inp.closest('tr').querySelector('.sel-medico');
                    if (selMed) actualizarLinha(selMed);
                });
            });
            // Form de notificação (único form real na página)
            const fNotif = document.querySelector('form[data-notif]');
            if (fNotif) {
                fNotif.addEventListener('submit', e => { e.preventDefault(); ajaxForm(fNotif); });
            }
        });

        // Chart
        const chartCtx = document.getElementById('chartSemanal');
        new Chart(chartCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_vals) ?>,
                    backgroundColor: 'rgba(15,212,200,0.12)',
                    borderColor: '#0fd4c8',
                    borderWidth: 2,
                    borderRadius: 8,
                    hoverBackgroundColor: 'rgba(15,212,200,0.2)'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero:true, ticks:{ stepSize:1, color:'#6b7a99', font:{size:11} }, grid:{color:'rgba(255,255,255,.05)'}, border:{color:'transparent'} },
                    x: { ticks:{ color:'#6b7a99', font:{size:11} }, grid:{display:false}, border:{color:'rgba(255,255,255,.05)'} }
                }
            }
        });

        // Pagination
        (function(){
            const PER=10, tbody=document.getElementById('tbodyRecep'),
                info=document.getElementById('infoRecep'), btns=document.getElementById('botoesRecep');
            const rows=Array.from(tbody.querySelectorAll('tr')); let pg=1;
            function tp(){ return Math.max(1,Math.ceil(rows.length/PER)); }
            function render(){
                const s=(pg-1)*PER, e=s+PER;
                rows.forEach((r,i)=>r.style.display=(i>=s&&i<e)?'':' none');
                info.textContent=rows.length===0?'Sem registos':`A mostrar ${Math.min(s+1,rows.length)}–${Math.min(e,rows.length)} de ${rows.length}`;
                btns.innerHTML='';
                const add=(l,p,d)=>{const b=document.createElement('button');b.innerHTML=l;b.className='pg-btn'+(p===pg?' active':'');b.disabled=d;b.onclick=()=>{if(!d){pg=p;render();}};btns.appendChild(b);};
                add('&laquo;',1,pg===1);add('&lsaquo;',pg-1,pg===1);
                let s2=Math.max(1,pg-2),e2=Math.min(tp(),s2+4);s2=Math.max(1,e2-4);
                for(let p=s2;p<=e2;p++)add(p,p,false);
                add('&rsaquo;',pg+1,pg===tp());add('&raquo;',tp(),pg===tp());
            }
            render();
        })();
    </script>
    </body>
</html>