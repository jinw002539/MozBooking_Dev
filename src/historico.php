<?php
    session_start();
    require_once __DIR__ . '/db.php';

    if (!isset($_SESSION['usuario_nome'])) { header('Location: login.php'); exit; }

    $pdo              = db();
    $is_recepcionista = ($_SESSION['usuario_tipo'] === 'recepcionista');
    $is_medico        = ($_SESSION['usuario_tipo'] === 'medico');
    $back_url         = $is_medico ? 'medico.php' : 'recepcionista.php';
    $medicos_externos = $GLOBALS['MEDICOS_EXTERNOS'];

    // ── Edição de registo (só recepcionista, só médicos externos) ─────────────────
    if ($is_recepcionista && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['edit_ticket'])) {
        $ticket   = trim($_POST['edit_ticket']);
        $medico   = trim($_POST['edit_medico']   ?? '');
        $processo = trim($_POST['edit_processo'] ?? '');
        $estado   = trim($_POST['edit_estado']   ?? 'Pendente');

        if (!in_array($medico, $medicos_externos, true)) {
            header('Location: historico.php?erro=1'); exit;
        }
        if ($estado === 'Em atendimento') $estado = 'Pendente';

        // Só actualiza se o médico actual for externo
        $pdo->prepare("
            UPDATE marcacoes SET medico=?, processo=?, estado=?
            WHERE ticket=? AND medico = ANY(?::text[])
        ")->execute([
            $medico, $processo, $estado, $ticket,
            '{' . implode(',', array_map(fn($m) => '"'.addslashes($m).'"', $medicos_externos)) . '}'
        ]);

        $qs = http_build_query(array_filter([
            'estado' => $_POST['f_estado'] ?? '',
            'tipo'   => $_POST['f_tipo']   ?? '',
            'q'      => $_POST['f_q']      ?? '',
        ]));
        header('Location: historico.php' . ($qs ? "?$qs&" : '?') . 'editok=1');
        exit;
    }

    // ── Filtros ───────────────────────────────────────────────────────────────────
    $filtro_estado = $_GET['estado'] ?? '';
    $filtro_tipo   = $_GET['tipo']   ?? '';
    $q             = $_GET['q']      ?? '';

    // Construir WHERE dinâmico
    $where  = ['1=1'];
    $params = [];

    if ($filtro_estado) { $where[] = 'estado = ?'; $params[] = $filtro_estado; }
    if ($filtro_tipo)   { $where[] = 'urgencia = ?'; $params[] = $filtro_tipo; }
    if ($q)             { $where[] = '(ticket ILIKE ? OR medico ILIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

    $sql = 'SELECT * FROM marcacoes WHERE ' . implode(' AND ', $where) . ' ORDER BY criado_em DESC, id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filtrado = $stmt->fetchAll();

    $total_geral = (int)$pdo->query("SELECT COUNT(*) FROM marcacoes")->fetchColumn();

    $medicos_lista = $GLOBALS['MEDICOS_LISTA'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico | Vida Centro de Saúde</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; margin:0; padding:0; }
        body { background: #1a2f5a; min-height: 100vh; }
        .brand-font { font-family: 'DM Serif Display', serif; }

        /* ── TOPBAR ── */
        .topbar {
            background: #0f1f3d;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            position: sticky; top: 0; z-index: 30;
        }

        /* ── BADGES ── */
        .badge { display:inline-flex; align-items:center; padding:4px 11px; border-radius:20px; font-size:11.5px; font-weight:700; letter-spacing:.02em; }
        .badge-urgent   { background:#ef4444; color:#fff; }
        .badge-normal   { background:#e2e8f0; color:#334155; }
        .badge-novo     { background:#0ea5e9; color:#fff; }
        .badge-antigo   { background:#cbd5e1; color:#475569; }
        /* estados — sólidos e bem visíveis */
        .badge-pendente  { background:#f59e0b; color:#fff; }
        .badge-concluido { background:#22c55e; color:#fff; }
        .badge-cancelado { background:#ef4444; color:#fff; }

        /* ── TICKET PILL ── */
        .ticket-pill {
            font-family: 'Courier New', monospace;
            font-size: 12px; font-weight: 700; letter-spacing:.06em;
            padding: 5px 11px; border-radius: 7px;
            background: #1e40af; color: #93c5fd;
            border: 1px solid rgba(147,197,253,0.3);
        }

        /* ── CARD ── */
        .hist-card {
            background: #ffffff;
            border-radius: 12px;
            display: grid;
            grid-template-columns: 72px 1fr auto;
            gap: 0;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.18);
            transition: transform .2s, box-shadow .2s;
        }
        .hist-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.22); }
        .hist-card.done { opacity: .6; }

        /* Coluna da data */
        .hist-date-col {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 18px 8px;
            border-right: 1px solid #e2e8f0;
            background: #f8faff;
        }
        .hist-date-day   { font-size: 26px; font-weight: 800; color: #1e3a8a; line-height: 1; }
        .hist-date-month { font-size: 11px; font-weight: 700; color: #3b82f6; text-transform: uppercase; letter-spacing:.06em; margin-top: 2px; }
        .hist-date-year  { font-size: 10px; color: #94a3b8; margin-top: 2px; }

        /* Coluna do corpo */
        .hist-body-col {
            padding: 14px 18px;
            display: flex; flex-direction: column; gap: 8px;
            border-left: 4px solid transparent; /* cor por estado */
        }
        .hist-card.estado-pendente  .hist-body-col { border-left-color: #f59e0b; }
        .hist-card.estado-concluido .hist-body-col { border-left-color: #22c55e; }
        .hist-card.estado-cancelado .hist-body-col { border-left-color: #ef4444; }

        .hist-top   { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
        .hist-meta  { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .hist-meta-item { display:flex; align-items:center; gap:5px; font-size:12.5px; color:#475569; }
        .hist-meta-item strong { color:#1e293b; font-weight:600; }
        .hist-seq { font-size:11px; color:#cbd5e1; font-weight:600; }

        /* Coluna direita */
        .hist-right-col {
            display: flex; flex-direction: column;
            align-items: flex-end; justify-content: center;
            gap: 8px; padding: 14px 18px;
            border-left: 1px solid #f1f5f9;
            min-width: 110px;
        }

        /* ── BOTÃO EDITAR ── */
        .btn-edit {
            background: #fff7ed; color: #d97706;
            border: 1px solid #fcd34d;
            font-size: 11.5px; font-weight: 700;
            padding: 5px 13px; border-radius: 7px;
            cursor: pointer; transition: background .2s;
            white-space: nowrap;
        }
        .btn-edit:hover { background: #fef3c7; }

        /* ── FILTROS ── */
        .filter-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 20px 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            margin-bottom: 20px;
        }
        .filter-label {
            display: block; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .07em;
            color: #64748b; margin-bottom: 5px;
        }
        .filter-input {
            width: 100%;
            background: #f8faff; border: 1.5px solid #e2e8f0;
            border-radius: 8px; padding: 8px 12px;
            font-size: 13px; color: #1e293b;
            transition: border-color .2s;
        }
        .filter-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
        .filter-input option { background: #fff; }
        .btn-filter {
            background: #1d4ed8; color: #fff;
            font-weight: 700; font-size: 13px;
            padding: 9px 20px; border-radius: 8px;
            border: none; cursor: pointer; transition: background .2s;
            white-space: nowrap;
        }
        .btn-filter:hover { background: #1e40af; }
        .btn-clear {
            background: #f1f5f9; color: #64748b;
            border: 1.5px solid #e2e8f0;
            font-size: 13px; font-weight: 600;
            padding: 9px 14px; border-radius: 8px;
            cursor: pointer; transition: all .2s;
            text-decoration: none; display: inline-flex; align-items: center;
        }
        .btn-clear:hover { background: #e2e8f0; color: #334155; }

        /* ── PAGINAÇÃO ── */
        .pg-btn {
            display:inline-flex; align-items:center; justify-content:center;
            min-width:32px; height:32px; padding:0 8px;
            border-radius:7px; font-size:12.5px; font-weight:600;
            cursor:pointer; transition:all .15s;
            border: 1.5px solid rgba(255,255,255,0.15);
            background: transparent; color: rgba(255,255,255,0.7);
        }
        .pg-btn.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
        .pg-btn:hover:not(.active):not(:disabled) { background: rgba(255,255,255,0.1); color: #fff; }
        .pg-btn:disabled { opacity: .3; cursor: not-allowed; }

        /* ── PAINEL DE CONTAGEM ── */
        .count-bar {
            display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
        }
        .count-chip {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px; padding: 10px 18px;
            display: flex; flex-direction: column; gap: 2px;
        }
        .count-chip-num  { font-size: 22px; font-weight: 800; color: #fff; line-height: 1; }
        .count-chip-label{ font-size: 11px; font-weight: 500; color: rgba(255,255,255,0.6); }
        .count-chip.c-yellow .count-chip-num { color: #fcd34d; }
        .count-chip.c-green  .count-chip-num { color: #86efac; }
        .count-chip.c-red    .count-chip-num { color: #fca5a5; }

        /* ── MODAL ── */
        #editModal { display:none; }
        #editModal.open { display:flex; }
        .modal-overlay { background: rgba(10,20,50,0.75); backdrop-filter: blur(5px); }
        .modal-box {
            background: #fff; border-radius: 16px;
            padding: 28px; width: 100%; max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
        }
        .modal-label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.07em; margin-bottom:5px; }
        .modal-input {
            width: 100%; background: #f8faff;
            border: 1.5px solid #e2e8f0; border-radius: 8px;
            padding: 9px 12px; font-size: 13px; color: #1e293b;
        }
        .modal-input:focus { outline:none; border-color:#3b82f6; }
        .modal-input option { background:#fff; }

        /* ── MISC ── */
        @keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
        .fade-up { animation: fadeUp .3s ease forwards; }
        ::-webkit-scrollbar { width:5px; }
        ::-webkit-scrollbar-thumb { background:rgba(255,255,255,.15); border-radius:10px; }

        /* ── LISTA WRAPPER ── */
        .list-wrapper {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            overflow: hidden;
        }
        .list-header {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; justify-content: space-between; align-items: center;
        }

        @media(max-width:600px){
            .hist-card { grid-template-columns: 60px 1fr; }
            .hist-right-col { grid-column: 1 / -1; flex-direction: row; justify-content: space-between; border-left:none; border-top:1px solid #f1f5f9; padding:10px 14px; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-4">
        <button onclick="history.back()" style="color:rgba(255,255,255,0.75);font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;background:none;border:none;cursor:pointer;transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.75)'">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Voltar
        </button>
        <div style="width:1px;height:26px;background:rgba(255,255,255,0.12);"></div>
        <div>
            <span class="brand-font" style="font-size:19px;color:#fff;font-weight:700;">
                <span style="color:#60a5fa;">Vida</span> — Histórico
            </span>
            <p style="color:rgba(255,255,255,0.5);font-size:11px;margin-top:1px;"><?= count($filtrado) ?> resultado(s) · <?= $total_geral ?> total</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <?php if (isset($_GET['editok'])): ?>
        <span class="badge badge-concluido fade-up">✓ Editado com sucesso!</span>
        <?php endif; ?>
        <?php if (isset($_GET['erro'])): ?>
        <span class="badge badge-cancelado fade-up">Operação não permitida.</span>
        <?php endif; ?>
        <a href="<?= $back_url ?>" style="background:#1d4ed8;color:#fff;padding:8px 18px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;transition:background .2s;" onmouseover="this.style.background='#1e40af'" onmouseout="this.style.background='#1d4ed8'">
            ← Dashboard
        </a>
    </div>
</header>

<main style="max-width:1020px;margin:0 auto;padding:24px;" class="fade-up">

    <!-- CONTADORES -->
    <?php
    $n_pend  = count(array_filter($filtrado, fn($r) => $r['estado'] === 'Pendente'));
    $n_conc  = count(array_filter($filtrado, fn($r) => $r['estado'] === 'Concluido'));
    $n_canc  = count(array_filter($filtrado, fn($r) => $r['estado'] === 'Cancelado'));
    ?>
    <div class="count-bar">
        <div class="count-chip">
            <span class="count-chip-num"><?= count($filtrado) ?></span>
            <span class="count-chip-label">Resultados</span>
        </div>
        <div class="count-chip c-yellow">
            <span class="count-chip-num"><?= $n_pend ?></span>
            <span class="count-chip-label">Pendentes</span>
        </div>
        <div class="count-chip c-green">
            <span class="count-chip-num"><?= $n_conc ?></span>
            <span class="count-chip-label">Concluídos</span>
        </div>
        <div class="count-chip c-red">
            <span class="count-chip-num"><?= $n_canc ?></span>
            <span class="count-chip-label">Cancelados</span>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="filter-card">
        <form method="GET">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:14px;align-items:end;">
                <div>
                    <label class="filter-label">Pesquisar</label>
                    <input type="text" name="q" class="filter-input" value="<?= htmlspecialchars($q) ?>" placeholder="Ticket ou médico…">
                </div>
                <div>
                    <label class="filter-label">Estado</label>
                    <select name="estado" class="filter-input">
                        <option value="">Todos os estados</option>
                        <option value="Pendente"  <?= $filtro_estado==='Pendente'  ? 'selected' : '' ?>>Pendente</option>
                        <option value="Concluido" <?= $filtro_estado==='Concluido' ? 'selected' : '' ?>>Concluído</option>
                        <option value="Cancelado" <?= $filtro_estado==='Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label">Tipo</label>
                    <select name="tipo" class="filter-input">
                        <option value="">Todos os tipos</option>
                        <option value="normal"  <?= $filtro_tipo==='normal'  ? 'selected' : '' ?>>Normal</option>
                        <option value="urgente" <?= $filtro_tipo==='urgente' ? 'selected' : '' ?>>Urgente</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn-filter">Filtrar</button>
                    <a href="historico.php" class="btn-clear">✕</a>
                </div>
            </div>
        </form>
    </div>

    <!-- LISTA -->
    <div class="list-wrapper">
        <div class="list-header">
            <span id="infoHistorico" style="font-size:12px;color:rgba(255,255,255,0.6);font-weight:500;"></span>
            <div id="botoesHistorico" style="display:flex;gap:4px;"></div>
        </div>

        <div style="padding:14px;" id="histListWrap">
            <?php if (empty($filtrado)): ?>
            <div style="padding:52px;text-align:center;color:rgba(255,255,255,0.4);">
                <div style="font-size:36px;margin-bottom:12px;">🗂</div>
                <p style="font-size:14px;font-weight:500;">Nenhum registo encontrado.</p>
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:10px;" id="histGrid">
            <?php $seq = count($filtrado); foreach ($filtrado as $h):
                $pode_editar = $is_recepcionista && in_array($h['medico'] ?? '', $medicos_externos, true);
                $est_lower   = strtolower($h['estado']);
                $est_lbl     = ['Pendente'=>'Pendente','Concluido'=>'Concluído','Cancelado'=>'Cancelado'][$h['estado']] ?? $h['estado'];
                $done        = in_array($h['estado'], ['Concluido','Cancelado']);
                $day         = date('d', strtotime($h['data']));
                $mon         = strtoupper(date('M', strtotime($h['data'])));
                $yr          = date('Y', strtotime($h['data']));
            ?>
            <div class="hist-card estado-<?= $est_lower ?><?= $done ? ' done' : '' ?>" data-hist>

                <!-- DATA -->
                <div class="hist-date-col">
                    <span class="hist-date-day"><?= $day ?></span>
                    <span class="hist-date-month"><?= $mon ?></span>
                    <span class="hist-date-year"><?= $yr ?></span>
                </div>

                <!-- CORPO -->
                <div class="hist-body-col">
                    <div class="hist-top">
                        <span class="ticket-pill"><?= htmlspecialchars($h['ticket']) ?></span>
                        <?= $h['urgencia'] === 'urgente'
                            ? '<span class="badge badge-urgent">URGENTE</span>'
                            : '<span class="badge badge-normal">Normal</span>' ?>
                        <?= $h['cliente'] === 'novo'
                            ? '<span class="badge badge-novo">Novo Paciente</span>'
                            : '<span class="badge badge-antigo">Antigo</span>' ?>
                        <span class="hist-seq">#<?= $seq-- ?></span>
                    </div>
                    <div class="hist-meta">
                        <div class="hist-meta-item">
                            <svg width="13" height="13" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <strong><?= htmlspecialchars($h['medico'] ?: '—') ?></strong>
                        </div>
                        <?php if (!empty($h['hora'])): ?>
                        <div class="hist-meta-item">
                            <svg width="13" height="13" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/></svg>
                            <strong><?= htmlspecialchars(formatar_hora($h['hora'])) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($h['processo'])): ?>
                        <div class="hist-meta-item">
                            <svg width="13" height="13" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span>Proc. <strong><?= htmlspecialchars($h['processo']) ?></strong></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- DIREITA -->
                <div class="hist-right-col">
                    <span class="badge badge-<?= $est_lower ?>"><?= $est_lbl ?></span>
                    <?php if ($pode_editar): ?>
                    <button type="button"
                        onclick="abrirEditar(
                            '<?= htmlspecialchars(addslashes($h['ticket'])) ?>',
                            '<?= htmlspecialchars(addslashes($h['medico'] ?? '')) ?>',
                            '<?= htmlspecialchars(addslashes($h['processo'] ?? '')) ?>',
                            '<?= htmlspecialchars(addslashes($h['estado'])) ?>'
                        )"
                        class="btn-edit">Editar</button>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</main>

<?php if ($is_recepcionista): ?>
<!-- MODAL EDIÇÃO -->
<div id="editModal" class="fixed inset-0 z-50 items-center justify-center p-4 modal-overlay">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:22px;">
            <div>
                <h2 style="font-size:18px;font-weight:700;color:#0f172a;">Editar Consulta</h2>
                <p style="font-size:12px;color:#64748b;margin-top:3px;">Apenas médicos externos</p>
            </div>
            <button onclick="fecharEditar()" style="background:#f1f5f9;border:1.5px solid #e2e8f0;color:#64748b;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:background .2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="edit_ticket" id="edit_ticket">
            <input type="hidden" name="f_estado" value="<?= htmlspecialchars($filtro_estado) ?>">
            <input type="hidden" name="f_tipo"   value="<?= htmlspecialchars($filtro_tipo) ?>">
            <input type="hidden" name="f_q"      value="<?= htmlspecialchars($q) ?>">
            <div style="margin-bottom:14px;">
                <label class="modal-label">Senha</label>
                <input type="text" id="edit_ticket_display" class="modal-input" readonly style="font-family:monospace;color:#1e40af;font-weight:700;">
            </div>
            <div style="margin-bottom:14px;">
                <label class="modal-label">Médico</label>
                <select name="edit_medico" id="edit_medico" class="modal-input">
                    <?php foreach ($medicos_externos as $me): ?>
                    <option value="<?= $me ?>"><?= $me ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label class="modal-label">Nº Processo</label>
                <input type="text" name="edit_processo" id="edit_processo" class="modal-input" placeholder="Número de processo">
            </div>
            <div style="margin-bottom:24px;">
                <label class="modal-label">Estado</label>
                <select name="edit_estado" id="edit_estado" class="modal-input">
                    <option value="Pendente">Pendente</option>
                    <option value="Concluido">Concluído</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" style="flex:1;background:#1d4ed8;color:#fff;font-weight:700;font-size:13.5px;padding:11px;border-radius:9px;border:none;cursor:pointer;transition:background .2s;" onmouseover="this.style.background='#1e40af'" onmouseout="this.style.background='#1d4ed8'">Guardar Alterações</button>
                <button type="button" onclick="fecharEditar()" class="btn-clear">Cancelar</button>
            </div>
        </form>
    </div>
</div>
<script>
function abrirEditar(ticket, medico, processo, estado) {
    document.getElementById('edit_ticket').value         = ticket;
    document.getElementById('edit_ticket_display').value = ticket;
    document.getElementById('edit_processo').value       = processo;
    const selM = document.getElementById('edit_medico');
    for (let o of selM.options) o.selected = (o.value === medico);
    const selE = document.getElementById('edit_estado');
    const est  = (estado === 'Em atendimento') ? 'Pendente' : estado;
    for (let o of selE.options) o.selected = (o.value === est);
    document.getElementById('editModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function fecharEditar() {
    document.getElementById('editModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('editModal').addEventListener('click', e => { if(e.target===e.currentTarget) fecharEditar(); });
</script>
<?php endif; ?>

<script>
(function(){
    const PER   = 10;
    const grid  = document.getElementById('histGrid');
    if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('[data-hist]'));
    const info  = document.getElementById('infoHistorico');
    const btns  = document.getElementById('botoesHistorico');
    let pg = 1;
    function tp() { return Math.max(1, Math.ceil(cards.length / PER)); }
    function render() {
        const s = (pg-1)*PER, e = s+PER;
        cards.forEach((c,i) => c.style.display = (i>=s && i<e) ? '' : 'none');
        info.textContent = cards.length === 0
            ? 'Sem registos'
            : `A mostrar ${Math.min(s+1,cards.length)}–${Math.min(e,cards.length)} de ${cards.length}`;
        btns.innerHTML = '';
        const add = (l, p, d) => {
            const b = document.createElement('button');
            b.innerHTML = l;
            b.className = 'pg-btn' + (p===pg ? ' active' : '');
            b.disabled = d;
            b.onclick = () => { if (!d) { pg=p; render(); window.scrollTo({top:0,behavior:'smooth'}); } };
            btns.appendChild(b);
        };
        add('«', 1, pg===1);
        add('‹', pg-1, pg===1);
        let s2=Math.max(1,pg-2), e2=Math.min(tp(),s2+4); s2=Math.max(1,e2-4);
        for (let p=s2; p<=e2; p++) add(p, p, false);
        add('›', pg+1, pg===tp());
        add('»', tp(), pg===tp());
    }
    render();
})();
</script>
</body>
</html>