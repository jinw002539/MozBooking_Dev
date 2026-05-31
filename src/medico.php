<?php
    session_start();
    require_once __DIR__ . '/db.php';

    if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'medico') {
        header('Location: login.php'); exit;
    }

    $pdo = db();

    // ── Concluir consulta ─────────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['concluir_ticket'])) {
        $ticket = trim($_POST['concluir_ticket']);
        $pdo->prepare("
            UPDATE marcacoes SET estado = 'Concluido'
            WHERE ticket = ? AND medico = ?
        ")->execute([$ticket, MEDICO_CLINICA]);
        header('Location: medico.php?ok=1'); exit;
    }

    // ── Dados gerais ──────────────────────────────────────────────────────────────
    $hoje          = date('Y-m-d');
    $semana_inicio = date('Y-m-d', strtotime('monday this week'));
    $semana_fim    = date('Y-m-d', strtotime('sunday this week'));
    $mes_atual     = date('Y-m');

    $kpi = $pdo->query("
        SELECT
            COUNT(*) FILTER (WHERE data = CURRENT_DATE)                               AS hoje,
            COUNT(*) FILTER (WHERE data BETWEEN '{$semana_inicio}' AND '{$semana_fim}') AS semana,
            COUNT(*) FILTER (WHERE TO_CHAR(data,'YYYY-MM') = '{$mes_atual}')           AS mes,
            COUNT(*)                                                                   AS total,
            COUNT(*) FILTER (WHERE urgencia = 'urgente')                              AS urgentes,
            COUNT(*) FILTER (WHERE cliente  = 'novo')                                 AS novos,
            COUNT(*) FILTER (WHERE estado   = 'Concluido')                            AS concluidos
        FROM marcacoes
    ")->fetch();

    // Gráfico mensal (6 meses)
    $meses_labels = [];
    $meses_vals   = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $meses_labels[] = date('M y', strtotime("$m-01"));
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM marcacoes WHERE TO_CHAR(data,'YYYY-MM') = ?");
        $cnt->execute([$m]);
        $meses_vals[] = (int)$cnt->fetchColumn();
    }

    // Gráfico semanal (7 dias)
    $dias_labels = [];
    $dias_vals   = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $dias_labels[] = date('d/m', strtotime($d));
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM marcacoes WHERE data = ?");
        $cnt->execute([$d]);
        $dias_vals[] = (int)$cnt->fetchColumn();
    }

    $normais_count  = $kpi['total'] - $kpi['urgentes'];
    $urgentes_count = $kpi['urgentes'];

    // Agenda de hoje: só pacientes do Dr. Armando — activos primeiro, concluídos depois
    $agenda = $pdo->prepare("
        SELECT *,
            CASE WHEN estado IN ('Concluido','Cancelado') THEN 1 ELSE 0 END AS ordem
        FROM marcacoes
        WHERE data = ? AND medico = ?
        ORDER BY ordem ASC, criado_em ASC
    ");
    $agenda->execute([$hoje, MEDICO_CLINICA]);
    $hoje_list = $agenda->fetchAll();
    $activos_count = count(array_filter($hoje_list, fn($r) => !in_array($r['estado'], ['Concluido','Cancelado'])));
?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Direção | Vida Centro de Saúde</title>
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
                --accent: #3b82f6;
                --gold: #f59e0b;
                --red: #ef4444;
                --green: #10b981;
                --purple: #8b5cf6;
                --text: #0d1117;
                --muted: #4a5568;
            }
            * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
            body { background: var(--navy); color: var(--text); min-height: 100vh; }
            .brand-font { font-family: 'DM Serif Display', serif; }
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
            .nav-item:hover { background: rgba(255,255,255,0.12); color: #ffffff; }
            .nav-item.active { background: rgba(15,212,200,.12); color: var(--teal); }
            .card {
                background: #ffffff;
                border: 1px solid var(--border);
                border-radius: 16px;
                backdrop-filter: blur(8px);
            }
            .kpi-card {
                background: #ffffff;
                border: 1px solid var(--border);
                border-radius: 14px; padding: 20px 22px;
                transition: transform .2s, border-color .2s;
                position: relative; overflow: hidden;
            }
            .kpi-card::before {
                content:''; position:absolute; top:0; left:0; right:0; height:2px;
            }
            .kpi-card.c-teal::before { background: linear-gradient(90deg, var(--teal), transparent); }
            .kpi-card.c-gold::before { background: linear-gradient(90deg, var(--gold), transparent); }
            .kpi-card.c-purple::before { background: linear-gradient(90deg, var(--purple), transparent); }
            .kpi-card.c-muted::before { background: linear-gradient(90deg, var(--muted), transparent); }
            .kpi-card.c-red::before { background: linear-gradient(90deg, var(--red), transparent); }
            .kpi-card.c-green::before { background: linear-gradient(90deg, var(--green), transparent); }
            .kpi-card.c-accent::before { background: linear-gradient(90deg, var(--accent), transparent); }
            .kpi-card:hover { transform: translateY(-2px); border-color: rgba(255,255,255,.12); }

            .data-table { width: 100%; border-collapse: collapse; }
            .data-table thead th {
                padding: 11px 16px; text-align: left;
                font-size: 11px; font-weight: 600; letter-spacing: .08em;
                text-transform: uppercase; color: var(--muted);
                border-bottom: 1px solid var(--border);
            }
            .data-table tbody tr { border-bottom: 1px solid rgba(255,255,255,.04); transition: background .15s; }
            .data-table tbody tr:hover { background: #ffffff; }
            .data-table tbody td { padding: 13px 16px; font-size: 13.5px; vertical-align: middle; }

            .badge { display:inline-flex; align-items:center; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
            .badge-urgent  { background: rgba(239,68,68,.15);  color: #fca5a5; border:1px solid rgba(239,68,68,.25); }
            .badge-normal  { background: rgba(107,122,153,.12); color: var(--muted); border:1px solid rgba(107,122,153,.2); }
            .badge-novo    { background: rgba(16,185,129,.12); color: #6ee7b7; border:1px solid rgba(16,185,129,.2); }
            .badge-antigo  { background: rgba(107,122,153,.08); color: #9ca3af; border:1px solid rgba(107,122,153,.15); }
            .badge-pendente { background: rgba(245,158,11,.12); color: #fcd34d; border:1px solid rgba(245,158,11,.2); }
            .badge-concluido{ background: rgba(16,185,129,.12); color: #6ee7b7; border:1px solid rgba(16,185,129,.2); }
            .badge-cancelado{ background: rgba(239,68,68,.12);  color: #fca5a5; border:1px solid rgba(239,68,68,.2); }

            .ticket-pill {
                font-family: monospace; font-size: 11.5px; font-weight: 700;
                padding: 4px 10px; border-radius: 7px;
                background: rgba(15,212,200,.1); color: var(--teal);
                border: 1px solid rgba(15,212,200,.2); letter-spacing:.05em;
            }
            .ticket-pill.done { background: rgba(107,122,153,.1); color: var(--muted); border-color: rgba(107,122,153,.2); }

            .btn-concluir {
                background: linear-gradient(135deg, #10b981, #059669);
                color: #fff; font-weight: 600; font-size: 12px;
                padding: 8px 18px; border-radius: 8px; border: none;
                cursor: pointer; transition: opacity .2s, transform .15s;
                white-space: nowrap;
            }
            .btn-concluir:hover { opacity: .88; transform: translateY(-1px); }

            .topbar {
                background: rgba(255,255,255,0.92);
                backdrop-filter: blur(20px);
                border-bottom: 1px solid var(--border);
            }

            @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
            .fade-up { animation: fadeUp .35s ease forwards; }
            @keyframes pulse-dot { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.5);opacity:.6} }
            .live-dot { width:7px;height:7px;border-radius:50%;background:var(--green);animation:pulse-dot 1.6s infinite; }

            ::-webkit-scrollbar { width: 5px; height: 5px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: rgba(15,60,120,0.15); border-radius: 10px; }

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
            <a href="medico.php" class="nav-item active">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Painel
            </a>
            <a href="historico.php" class="nav-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Histórico Geral
            </a>
        </nav>
        <div style="border-top:1px solid rgba(255,255,255,0.18);padding-top:16px;">
            <p style="color:#ffffff;font-size:14px;font-weight:600;"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
            <p style="color:rgba(255,255,255,0.75);font-size:11.5px;margin-top:3px;font-weight:500;letter-spacing:.03em;">Diretor Clínico</p>
            <a href="logout.php" style="color:#fca5a5;font-size:12.5px;font-weight:600;margin-top:8px;display:inline-block;transition:color .2s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#fca5a5'">→ Terminar Sessão</a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="flex-1 overflow-x-hidden flex flex-col">
        <header class="topbar px-6 py-4 flex justify-between items-center sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <div class="live-dot"></div>
                <div>
                    <h1 class="font-semibold text-base" style="color:var(--text)">Painel de Direção Clínica</h1>
                    <p style="color:var(--muted);font-size:11.5px;"><?= date('l, d \d\e F \d\e Y') ?></p>
                </div>
            </div>
            <div class="flex gap-3 items-center">
                <?php if (isset($_GET['ok'])): ?>
                <span class="badge badge-concluido fade-up">✓ Consulta concluída!</span>
                <?php endif; ?>
                <a href="historico.php" style="background:rgba(15,212,200,.12);color:var(--teal);border:1px solid rgba(15,212,200,.2);padding:7px 16px;border-radius:8px;font-size:12.5px;font-weight:600;text-decoration:none;transition:all .2s;" onmouseover="this.style.background='rgba(15,212,200,.2)'" onmouseout="this.style.background='rgba(15,212,200,.12)'">
                    Histórico Completo
                </a>
            </div>
        </header>

        <main class="p-6 space-y-6 fade-up">

            <!-- KPIs row 1 -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ([
                    [$kpi['hoje'],   'Marcações Hoje',  'c-teal',   'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    [$kpi['semana'], 'Esta Semana',     'c-gold',   'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    [$kpi['mes'],    'Este Mês',        'c-purple', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    [$kpi['total'],  'Total Geral',     'c-muted',  'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0'],
                ] as $k): ?>
                <div class="kpi-card <?= $k[2] ?>">
                    <svg style="opacity:.4;margin-bottom:12px" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="<?= $k[3] ?>"/></svg>
                    <div style="font-size:34px;font-weight:700;color:var(--text);line-height:1;"><?= $k[0] ?></div>
                    <div style="color:var(--muted);font-size:12px;margin-top:6px;"><?= $k[1] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- KPIs row 2 -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ([
                    [$urgentes_count,    'Urgentes (total)',    'c-red'],
                    [$kpi['novos'],      'Novos Pacientes',     'c-green'],
                    [$kpi['concluidos'], 'Consultas Concluídas','c-accent'],
                ] as $k): ?>
                <div class="kpi-card <?= $k[2] ?>">
                    <div style="font-size:28px;font-weight:700;color:var(--text);line-height:1;"><?= $k[0] ?></div>
                    <div style="color:var(--muted);font-size:12px;margin-top:6px;"><?= $k[1] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Charts -->
            <div class="grid lg:grid-cols-3 gap-6">
                <div class="card p-6 lg:col-span-2">
                    <h3 style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:20px;">Marcações — Últimos 6 Meses</h3>
                    <canvas id="graficoMensal" height="90"></canvas>
                </div>
                <div class="card p-6">
                    <h3 style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:20px;">Tipo de Consulta</h3>
                    <canvas id="graficoPizza" height="140"></canvas>
                    <div style="display:flex;justify-content:center;gap:20px;margin-top:16px;">
                        <span style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#3b82f6;display:inline-block;"></span>Normal</span>
                        <span style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>Urgente</span>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <h3 style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:20px;">Fluxo Diário — Última Semana</h3>
                <canvas id="graficoSemanal" height="60"></canvas>
            </div>

            <!-- Agenda hoje -->
            <div class="card overflow-hidden">
                <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h3 style="font-size:15px;font-weight:600;color:var(--text);">Agenda de Hoje</h3>
                        <p style="color:var(--muted);font-size:12px;margin-top:2px;"><?= date('d/m/Y') ?> · <?= MEDICO_CLINICA ?></p>
                    </div>
                    <span class="badge badge-pendente"><?= $activos_count ?> activo(s)</span>
                </div>
                <div style="padding:10px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <span id="infoMedico" style="font-size:11.5px;color:var(--muted);"></span>
                    <div id="botoesMedico" style="display:flex;gap:4px;"></div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Senha</th>
                                <th>Tipo</th>
                                <th>Paciente</th>
                                <th>Processo</th>
                                <th>Estado</th>
                                <th>Acção</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyMedico">
                        <?php if (empty($hoje_list)): ?>
                            <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--muted);">Nenhuma marcação para hoje.</td></tr>
                        <?php else: ?>
                        <?php foreach ($hoje_list as $m):
                            $concluida = in_array($m['estado'], ['Concluido','Cancelado']);
                        ?>
                        <tr <?= $concluida ? 'style="opacity:0.4;"' : '' ?>>
                            <td><span class="ticket-pill <?= $concluida ? 'done' : '' ?>"><?= htmlspecialchars($m['ticket']) ?></span></td>
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
                            <td style="font-family:monospace;font-size:12px;color:var(--muted);"><?= htmlspecialchars($m['processo'] ?: '—') ?></td>
                            <td>
                                <?php
                                $badge_map = ['Pendente'=>'badge-pendente','Concluido'=>'badge-concluido','Cancelado'=>'badge-cancelado'];
                                $label_map = ['Pendente'=>'Pendente','Concluido'=>'Concluído','Cancelado'=>'Cancelado'];
                                $bcls = $badge_map[$m['estado']] ?? 'badge-normal';
                                $blbl = $label_map[$m['estado']] ?? $m['estado'];
                                echo "<span class='badge $bcls'>$blbl</span>";
                                ?>
                            </td>
                            <td>
                                <?php if ($concluida): ?>
                                    <span style="color:var(--muted);font-size:12px;">—</span>
                                <?php else: ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="concluir_ticket" value="<?= htmlspecialchars($m['ticket']) ?>">
                                        <button type="submit" class="btn-concluir"
                                            onclick="return confirm('Concluir consulta <?= htmlspecialchars($m['ticket']) ?>?')">
                                            ✓ Concluir
                                        </button>
                                    </form>
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
        const chartOpts = {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero:true, ticks:{ stepSize:1, color:'#6b7a99', font:{size:11} }, grid:{color:'rgba(255,255,255,.05)'}, border:{color:'transparent'} },
                x: { ticks:{ color:'#6b7a99', font:{size:11} }, grid:{display:false}, border:{color:'rgba(255,255,255,.05)'} }
            }
        };
        new Chart(document.getElementById('graficoMensal'), {
            type:'bar', data:{ labels:<?= json_encode($meses_labels) ?>, datasets:[{ data:<?= json_encode($meses_vals) ?>, backgroundColor:'rgba(59,130,246,.15)', borderColor:'#3b82f6', borderWidth:2, borderRadius:8 }] },
            options: chartOpts
        });
        new Chart(document.getElementById('graficoPizza'), {
            type:'doughnut', data:{ labels:['Normal','Urgente'], datasets:[{ data:[<?= $normais_count ?>,<?= $urgentes_count ?>], backgroundColor:['#3b82f6','#ef4444'], borderWidth:0, hoverOffset:4 }] },
            options:{ plugins:{legend:{display:false}}, cutout:'65%' }
        });
        new Chart(document.getElementById('graficoSemanal'), {
            type:'line', data:{ labels:<?= json_encode($dias_labels) ?>, datasets:[{ data:<?= json_encode($dias_vals) ?>, borderColor:'#0fd4c8', backgroundColor:'rgba(15,212,200,.08)', fill:true, tension:0.4, pointBackgroundColor:'#0fd4c8', pointRadius:4 }] },
            options: chartOpts
        });

        (function(){
            const PER=10, tbody=document.getElementById('tbodyMedico'),
                info=document.getElementById('infoMedico'), btns=document.getElementById('botoesMedico');
            const rows=Array.from(tbody.querySelectorAll('tr')); let pg=1;
            function tp(){ return Math.max(1,Math.ceil(rows.length/PER)); }
            function render(){
                const s=(pg-1)*PER,e=s+PER;
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