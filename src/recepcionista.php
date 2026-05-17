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

            // Regras de negócio
            if ($estado === 'Em atendimento') $estado = 'Pendente';
            if (is_medico_clinica($medico) && $estado === 'Concluido') $estado = 'Pendente';
            if (empty($medico) && $estado === 'Concluido') $estado = 'Pendente';

            $stmt = $pdo->prepare("
                UPDATE marcacoes
                SET medico = ?, processo = ?, estado = ?
                WHERE ticket = ?
            ");
            $stmt->execute([$medico, $processo, $estado, $ticket]);

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
        <title>Receção | Vida</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
            * { font-family: 'Inter', sans-serif; }
            .brand { font-family: 'Playfair Display', serif; }
            .sidebar { background: linear-gradient(180deg, #0a1f44 0%, #0d2a5e 100%); min-height: 100vh; }
            select, input[type=text] { border: 2px solid #e5e7eb; border-radius: 8px; padding: 6px 10px; font-size: 13px; }
            select:focus, input:focus { border-color: #1565c0; outline: none; }
            @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
            .fade-in { animation: fadeIn .3s ease forwards; }
            tr:hover td { background: #f0f7ff; }
        </style>
    </head>
    <body class="bg-gray-50 flex">

    <!-- SIDEBAR -->
    <aside class="sidebar w-56 flex-shrink-0 hidden md:flex flex-col p-6 sticky top-0 h-screen">
        <div class="mb-8">
            <div class="brand text-white text-xl"><span class="text-cyan-400">Vida</span></div>
            <div class="text-blue-200 text-xs mt-1">Receção</div>
        </div>
        <nav class="flex-1 space-y-1">
            <a href="recepcionista.php" class="flex items-center gap-3 bg-white/10 text-white rounded-xl px-4 py-3 text-sm font-medium">Painel</a>
            <a href="historico.php" class="flex items-center gap-3 text-white/60 hover:text-white hover:bg-white/5 rounded-xl px-4 py-3 text-sm font-medium transition">Histórico</a>
        </nav>
        <div class="border-t border-white/10 pt-4">
            <p class="text-white/50 text-xs mb-1"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
            <a href="logout.php" class="text-red-300 hover:text-red-200 text-xs transition">→ Sair</a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="flex-1 overflow-x-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 z-20">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Gestão de Atendimento</h1>
                <p class="text-sm text-gray-400"><?= date('l, d \d\e F \d\e Y') ?></p>
            </div>
            <div class="flex items-center gap-3" id="topbar-feedback">
                <?php if (isset($_GET['ok'])): ?>
                <span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full fade-in">
                    <?= $_GET['ok'] == 1 ? 'Guardado!' : 'Notificação enviada!' ?>
                </span>
                <?php endif; ?>
                <a href="logout.php" class="md:hidden text-sm text-red-500 font-medium">Sair</a>
            </div>
        </header>

        <main class="p-6 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ([
                    [$stats['total_hoje'], 'Hoje',            'border-blue-500'],
                    [$stats['pendentes'],  'Pendentes',       'border-amber-500'],
                    [$stats['novos'],      'Novos Pacientes', 'border-emerald-500'],
                    [$stats['urgentes'],   'Urgentes',        'border-red-500'],
                ] as $s): ?>
                <div class="bg-white rounded-2xl p-5 shadow-sm border-l-4 <?= $s[2] ?>">
                    <div class="text-3xl font-bold text-gray-800"><?= $s[0] ?></div>
                    <div class="text-sm text-gray-400 mt-1"><?= $s[1] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- GRÁFICO + NOTIFICAÇÃO -->
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">Fluxo – Últimos 7 Dias</h3>
                    <canvas id="chartSemanal" height="120"></canvas>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">Notificação de Cancelamento</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="notificar">
                        <div class="mb-3">
                            <label class="text-xs font-semibold text-gray-500 block mb-1">Mensagem (PT)</label>
                            <input type="text" name="msg_pt" class="w-full"
                                value="<?= htmlspecialchars($notif_atual['mensagem_pt']) ?>"
                                placeholder="Ex: Consultas suspensas a 20/01...">
                        </div>
                        <div class="mb-3">
                            <label class="text-xs font-semibold text-gray-500 block mb-1">Message (EN)</label>
                            <input type="text" name="msg_en" class="w-full"
                                value="<?= htmlspecialchars($notif_atual['mensagem_en']) ?>"
                                placeholder="Ex: Consultations on 20/01 cancelled">
                        </div>
                        <div class="flex items-center gap-4 mb-4">
                            <label class="text-xs font-semibold text-gray-500">Activa?</label>
                            <select name="ativa" class="text-sm">
                                <option value="1" <?= $notif_atual['ativa'] ? 'selected' : '' ?>>Sim</option>
                                <option value="0" <?= !$notif_atual['ativa'] ? 'selected' : '' ?>>Não</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-5 py-2 rounded-xl transition">
                            Guardar Notificação
                        </button>
                    </form>
                </div>
            </div>

            <!-- TABELA DE HOJE -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Tickets de Hoje — <?= date('d/m/Y') ?></h3>
                    <span class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full font-semibold">
                        <?= count($marcacoes_hoje) ?> marcações
                    </span>
                </div>
                <!-- PAGINAÇÃO (topo) -->
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                    <span id="infoRecep" class="text-xs text-gray-400"></span>
                    <div class="flex items-center gap-1" id="botoesRecep"></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3 text-left">Ticket</th>
                                <th class="px-5 py-3 text-left">Tipo</th>
                                <th class="px-5 py-3 text-left">Paciente</th>
                                <th class="px-5 py-3 text-left">Médico</th>
                                <th class="px-5 py-3 text-left">Nº Processo</th>
                                <th class="px-5 py-3 text-left">Estado</th>
                                <th class="px-5 py-3 text-left">Acção</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyRecep" class="divide-y divide-gray-100">
                        <?php if (empty($marcacoes_hoje)): ?>
                            <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">Nenhuma marcação para hoje.</td></tr>
                        <?php else: ?>
                        <?php foreach ($marcacoes_hoje as $m):
                            $med_saved = $m['medico'] ?? '';
                            $concluida = in_array($m['estado'], ['Concluido', 'Cancelado']);
                            $estado_safe = $m['estado'] === 'Em atendimento' ? 'Pendente' : $m['estado'];
                        ?>
                        <tr class="transition-colors" data-ticket="<?= htmlspecialchars($m['ticket']) ?>"
                            <?= $concluida ? 'style="background:#f3f4f6;opacity:0.65;"' : '' ?>>
                            <form method="POST" class="contents">
                            <input type="hidden" name="action"  value="update">
                            <input type="hidden" name="ticket"  value="<?= htmlspecialchars($m['ticket']) ?>">

                            <td class="px-5 py-3">
                                <span class="font-mono font-bold <?= $concluida ? 'text-gray-400 bg-gray-200' : 'text-blue-800 bg-blue-50' ?> px-2 py-1 rounded">
                                    <?= htmlspecialchars($m['ticket']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <?= $m['urgencia'] === 'urgente'
                                    ? '<span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded-full">URGENTE</span>'
                                    : '<span class="bg-gray-100 text-gray-500 text-xs font-medium px-2 py-1 rounded-full">Normal</span>' ?>
                            </td>
                            <td class="px-5 py-3">
                                <?= $m['cliente'] === 'novo'
                                    ? '<span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-1 rounded-full">Novo</span>'
                                    : '<span class="text-gray-500 text-xs">Antigo</span>' ?>
                            </td>

                            <!-- MÉDICO -->
                            <td class="px-5 py-3">
                                <?php if ($concluida): ?>
                                    <span class="text-gray-400 text-xs"><?= htmlspecialchars($med_saved ?: '—') ?></span>
                                <?php else: ?>
                                    <select name="medico" class="sel-medico text-xs min-w-[170px]"
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
                            <td class="px-5 py-3">
                                <?php if ($concluida): ?>
                                    <span class="text-gray-400 text-xs"><?= htmlspecialchars($m['processo'] ?: '—') ?></span>
                                <?php elseif (!empty($m['processo'])): ?>
                                    <input type="hidden" name="processo" value="<?= htmlspecialchars($m['processo']) ?>">
                                    <span class="text-xs font-mono text-gray-700 bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($m['processo']) ?></span>
                                <?php elseif ($m['cliente'] === 'novo'): ?>
                                    <select name="processo" class="text-xs w-36" style="border-color:#f59e0b;">
                                        <option value="">— Atribuir nº —</option>
                                        <?php foreach ($pool_processos as $pnum): ?>
                                        <option value="<?= $pnum ?>"><?= $pnum ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <select name="processo" class="text-xs w-36">
                                        <option value="">— Opcional —</option>
                                        <?php foreach ($pool_processos as $pnum): ?>
                                        <option value="<?= $pnum ?>"><?= $pnum ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>

                            <!-- ESTADO -->
                            <td class="px-5 py-3">
                                <?php if ($concluida): ?>
                                    <span class="text-xs px-2 py-1 rounded-full font-medium <?= $m['estado'] === 'Concluido' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-500' ?>">
                                        <?= $m['estado'] === 'Concluido' ? 'Concluído' : 'Cancelado' ?>
                                    </span>
                                <?php else: ?>
                                    <select name="estado" class="sel-estado text-xs"
                                            data-saved-estado="<?= htmlspecialchars($estado_safe) ?>">
                                        <!-- opções injectadas pelo JS -->
                                    </select>
                                <?php endif; ?>
                            </td>

                            <!-- ACÇÃO -->
                            <td class="px-5 py-3">
                                <?php if ($concluida): ?>
                                    <span class="text-xs text-gray-400 italic">—</span>
                                <?php else: ?>
                                    <button type="submit" class="btn-acao text-xs font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap"></button>
                                <?php endif; ?>
                            </td>
                            </form>
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

        // ── Toast global ─────────────────────────────────────────────────────────────
        function mostrarToast(msg, ok) {
            let t = document.getElementById('toast-g');
            if (!t) {
                t = Object.assign(document.createElement('div'), { id: 'toast-g' });
                t.style.cssText = 'position:fixed;top:20px;right:24px;z-index:9999;padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.15);transition:opacity .3s;';
                document.body.appendChild(t);
            }
            t.textContent = msg;
            t.style.background = ok ? '#d1fae5' : '#fee2e2';
            t.style.color       = ok ? '#065f46' : '#991b1b';
            t.style.opacity     = '1';
            clearTimeout(t._h);
            t._h = setTimeout(() => t.style.opacity = '0', 3500);
        }

        // ── Spinner ───────────────────────────────────────────────────────────────────
        function spinner(show) {
            let o = document.getElementById('sp');
            if (!o && show) {
                o = document.createElement('div');
                o.id = 'sp';
                o.style.cssText = 'position:fixed;inset:0;background:rgba(10,31,68,.4);display:flex;align-items:center;justify-content:center;z-index:9998;';
                o.innerHTML = '<div style="background:#fff;border-radius:14px;padding:28px 36px;text-align:center"><div style="width:40px;height:40px;border:4px solid #e5e7eb;border-top-color:#1565c0;border-radius:50%;animation:sp .7s linear infinite;margin:0 auto 12px"></div><p style="font-size:13px;font-weight:600;color:#0a1f44">A guardar…</p></div><style>@keyframes sp{to{transform:rotate(360deg)}}</style>';
                document.body.appendChild(o);
            }
            if (o) o.style.display = show ? 'flex' : 'none';
        }

        // ── AJAX submit ───────────────────────────────────────────────────────────────
        function ajaxSubmit(form) {
            spinner(true);
            const ini = Date.now();
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            })
            .then(r => r.json())
            .then(res => {
                const rest = Math.max(0, 1800 - (Date.now() - ini));
                setTimeout(() => {
                    spinner(false);
                    mostrarToast(res.type === 1 ? 'Guardado com sucesso!' : 'Notificação enviada!', res.ok);
                }, rest);
            })
            .catch(() => { spinner(false); mostrarToast('Erro de ligação.', false); });
        }

        // ── Lógica reactiva por linha ─────────────────────────────────────────────────
        function actualizarLinha(sel) {
            const tr     = sel.closest('tr');
            const medico = sel.value;
            const selEst = tr.querySelector('.sel-estado');
            const btn    = tr.querySelector('.btn-acao');
            if (!selEst || !btn) return;

            const saved      = selEst.dataset.savedEstado || 'Pendente';
            const isClinica  = medico === MEDICO_CLINICA;
            const isExterno  = MEDICOS_EXTERNOS.includes(medico);

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

            if (isClinica) {
                btn.textContent = 'Mandar Consulta';
                btn.className = 'btn-acao text-xs font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap bg-indigo-600 hover:bg-indigo-700 text-white';
            } else if (isExterno) {
                btn.textContent = 'Guardar';
                btn.className = 'btn-acao text-xs font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap bg-blue-600 hover:bg-blue-700 text-white';
            } else {
                btn.textContent = 'Guardar';
                btn.className = 'btn-acao text-xs font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap bg-gray-300 text-gray-500 cursor-not-allowed';
            }
        }

        // ── Init ──────────────────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.sel-medico').forEach(s => {
                actualizarLinha(s);
                s.addEventListener('change', () => actualizarLinha(s));
            });
            document.querySelectorAll('form').forEach(f => {
                f.addEventListener('submit', e => { e.preventDefault(); ajaxSubmit(f); });
            });
        });

        // ── Gráfico ───────────────────────────────────────────────────────────────────
        new Chart(document.getElementById('chartSemanal'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_vals) ?>,
                    backgroundColor: 'rgba(21,101,192,0.15)',
                    borderColor: '#1565c0', borderWidth: 2, borderRadius: 8
                }]
            },
            options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
        });

        // ── Paginação ─────────────────────────────────────────────────────────────────
        (function(){
            const PER=10, tbody=document.getElementById('tbodyRecep'),
                info=document.getElementById('infoRecep'), btns=document.getElementById('botoesRecep');
            const rows=Array.from(tbody.querySelectorAll('tr'));
            let pg=1;
            function tp(){ return Math.max(1,Math.ceil(rows.length/PER)); }
            function render(){
                const s=(pg-1)*PER, e=s+PER;
                rows.forEach((r,i)=>r.style.display=(i>=s&&i<e)?'':'none');
                info.textContent=rows.length===0?'Sem registos':`A mostrar ${Math.min(s+1,rows.length)}–${Math.min(e,rows.length)} de ${rows.length}`;
                btns.innerHTML='';
                const st=a=>`display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;border-radius:8px;font-size:13px;font-weight:600;border:1.5px solid ${a?'#1565c0':'#e5e7eb'};background:${a?'#1565c0':'#fff'};color:${a?'#fff':'#374151'};cursor:pointer;`;
                const add=(l,p,d)=>{const b=document.createElement('button');b.innerHTML=l;b.style.cssText=st(p===pg);b.disabled=d;if(d)b.style.opacity='.35';b.onclick=()=>{if(!d){pg=p;render();}};btns.appendChild(b);};
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
