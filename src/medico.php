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
        <title>Direção | Vida</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
            * { font-family: 'Inter', sans-serif; }
            .brand { font-family: 'Playfair Display', serif; }
            .sidebar { background: linear-gradient(180deg, #0a1f44 0%, #0d2a5e 100%); min-height: 100vh; }
            @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
            .fade-in { animation: fadeIn .35s ease forwards; }
            tr:hover td { background: #f8fbff; }
        </style>
    </head>
    <body class="bg-gray-50 flex">

    <!-- SIDEBAR -->
    <aside class="sidebar w-60 flex-shrink-0 hidden md:flex flex-col p-6 sticky top-0 h-screen">
        <div class="mb-8">
            <div class="brand text-white text-xl"><span class="text-cyan-400">Vida</span> Centro de Saúde</div>
            <div class="text-blue-200 text-xs mt-1">Direção Clínica</div>
        </div>
        <nav class="flex-1 space-y-1">
            <a href="medico.php" class="flex items-center gap-3 bg-white/10 text-white rounded-xl px-4 py-3 text-sm font-medium">Painel</a>
            <a href="historico.php" class="flex items-center gap-3 text-white/60 hover:text-white hover:bg-white/5 rounded-xl px-4 py-3 text-sm font-medium transition">Histórico Geral</a>
        </nav>
        <div class="border-t border-white/10 pt-4">
            <p class="text-white/70 text-sm font-medium"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
            <p class="text-white/30 text-xs">Diretor Clínico</p>
            <a href="logout.php" class="text-red-300 hover:text-red-200 text-xs mt-2 inline-block transition">→ Terminar Sessão</a>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="flex-1 overflow-x-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 z-20">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Painel de Direção</h1>
                <p class="text-sm text-gray-400"><?= date('l, d \d\e F \d\e Y') ?></p>
            </div>
            <div class="flex gap-2 items-center">
                <?php if (isset($_GET['ok'])): ?>
                <span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full fade-in">Consulta concluída!</span>
                <?php endif; ?>
                <a href="historico.php" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-xl transition font-semibold">Histórico Completo</a>
            </div>
        </header>

        <main class="p-6 space-y-6 fade-in">

            <!-- KPIs linha 1 -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ([
                    [$kpi['hoje'],   'Hoje',        'border-blue-500'],
                    [$kpi['semana'], 'Esta Semana', 'border-cyan-500'],
                    [$kpi['mes'],    'Este Mês',    'border-purple-500'],
                    [$kpi['total'],  'Total Geral', 'border-gray-400'],
                ] as $k): ?>
                <div class="bg-white rounded-2xl p-5 shadow-sm border-l-4 <?= $k[2] ?>">
                    <div class="text-3xl font-bold text-gray-800"><?= $k[0] ?></div>
                    <div class="text-sm text-gray-400"><?= $k[1] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- KPIs linha 2 -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ([
                    [$urgentes_count,      'Urgentes (total)',      'border-red-400'],
                    [$kpi['novos'],        'Novos Pacientes',       'border-emerald-400'],
                    [$kpi['concluidos'],   'Consultas Concluídas',  'border-green-400'],
                ] as $k): ?>
                <div class="bg-white rounded-2xl p-5 shadow-sm border-l-4 <?= $k[2] ?>">
                    <div class="text-2xl font-bold"><?= $k[0] ?></div>
                    <div class="text-sm text-gray-400"><?= $k[1] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Gráficos -->
            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">Marcações – Últimos 6 Meses</h3>
                    <canvas id="graficoMensal" height="90"></canvas>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">Tipo de Consulta</h3>
                    <canvas id="graficoPizza" height="140"></canvas>
                    <div class="flex justify-center gap-6 mt-4 text-xs text-gray-500">
                        <span><span class="inline-block w-3 h-3 rounded-full bg-blue-600 mr-1"></span>Normal</span>
                        <span><span class="inline-block w-3 h-3 rounded-full bg-red-400 mr-1"></span>Urgente</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">Fluxo Diário – Última Semana</h3>
                <canvas id="graficoSemanal" height="70"></canvas>
            </div>

            <!-- Agenda de hoje -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Agenda de Hoje — <?= date('d/m/Y') ?></h3>
                    <span class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full font-semibold">
                        <?= $activos_count ?> activo(s)
                    </span>
                </div>
                <!-- PAGINAÇÃO (topo) -->
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                    <span id="infoMedico" class="text-xs text-gray-400"></span>
                    <div class="flex items-center gap-1" id="botoesMedico"></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3 text-left">Senha</th>
                                <th class="px-5 py-3 text-left">Tipo</th>
                                <th class="px-5 py-3 text-left">Paciente</th>
                                <th class="px-5 py-3 text-left">Processo</th>
                                <th class="px-5 py-3 text-left">Estado</th>
                                <th class="px-5 py-3 text-left">Acção</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyMedico" class="divide-y divide-gray-100">
                        <?php if (empty($hoje_list)): ?>
                            <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">Nenhuma marcação para hoje.</td></tr>
                        <?php else: ?>
                        <?php foreach ($hoje_list as $m):
                            $concluida = in_array($m['estado'], ['Concluido','Cancelado']);
                        ?>
                        <tr class="transition-colors" <?= $concluida ? 'style="background:#f3f4f6;opacity:0.65;"' : '' ?>>
                            <td class="px-5 py-3">
                                <span class="font-mono font-bold <?= $concluida ? 'text-gray-400 bg-gray-200' : 'text-blue-800 bg-blue-50' ?> px-2 py-1 rounded text-xs">
                                    <?= htmlspecialchars($m['ticket']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <?= $m['urgencia'] === 'urgente'
                                    ? '<span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded-full">URGENTE</span>'
                                    : '<span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">Normal</span>' ?>
                            </td>
                            <td class="px-5 py-3">
                                <?= $m['cliente'] === 'novo'
                                    ? '<span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-1 rounded-full">Novo</span>'
                                    : '<span class="text-gray-500 text-xs">Antigo</span>' ?>
                            </td>
                            <td class="px-5 py-3 text-gray-700"><?= htmlspecialchars($m['processo'] ?: '—') ?></td>
                            <td class="px-5 py-3">
                                <?php
                                $ecfg = ['Pendente'=>'bg-amber-100 text-amber-700','Concluido'=>'bg-green-100 text-green-700','Cancelado'=>'bg-red-100 text-red-600'];
                                $cls = $ecfg[$m['estado']] ?? 'bg-gray-100 text-gray-500';
                                echo "<span class='$cls text-xs px-2 py-1 rounded-full font-medium'>{$m['estado']}</span>";
                                ?>
                            </td>
                            <td class="px-5 py-3">
                                <?php if ($concluida): ?>
                                    <span class="text-xs text-gray-400 italic">—</span>
                                <?php else: ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="concluir_ticket" value="<?= htmlspecialchars($m['ticket']) ?>">
                                        <button type="submit"
                                            onclick="return confirm('Concluir consulta <?= htmlspecialchars($m['ticket']) ?>?')"
                                            class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                            Concluir Consulta
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
        new Chart(document.getElementById('graficoMensal'),{type:'bar',data:{labels:<?= json_encode($meses_labels) ?>,datasets:[{data:<?= json_encode($meses_vals) ?>,backgroundColor:'rgba(21,101,192,0.15)',borderColor:'#1565c0',borderWidth:2,borderRadius:8}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
        new Chart(document.getElementById('graficoPizza'),{type:'doughnut',data:{labels:['Normal','Urgente'],datasets:[{data:[<?= $normais_count ?>,<?= $urgentes_count ?>],backgroundColor:['#1565c0','#ef4444'],borderWidth:0}]},options:{plugins:{legend:{display:false}},cutout:'65%'}});
        new Chart(document.getElementById('graficoSemanal'),{type:'line',data:{labels:<?= json_encode($dias_labels) ?>,datasets:[{data:<?= json_encode($dias_vals) ?>,borderColor:'#00b4d8',backgroundColor:'rgba(0,180,216,0.08)',fill:true,tension:0.4,pointBackgroundColor:'#00b4d8',pointRadius:5}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});

        (function(){const PER=10,tbody=document.getElementById('tbodyMedico'),info=document.getElementById('infoMedico'),btns=document.getElementById('botoesMedico');const rows=Array.from(tbody.querySelectorAll('tr'));let pg=1;function tp(){return Math.max(1,Math.ceil(rows.length/PER));}function render(){const s=(pg-1)*PER,e=s+PER;rows.forEach((r,i)=>r.style.display=(i>=s&&i<e)?'':'none');info.textContent=rows.length===0?'Sem registos':`A mostrar ${Math.min(s+1,rows.length)}–${Math.min(e,rows.length)} de ${rows.length}`;btns.innerHTML='';const st=a=>`display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;border-radius:8px;font-size:13px;font-weight:600;border:1.5px solid ${a?'#1565c0':'#e5e7eb'};background:${a?'#1565c0':'#fff'};color:${a?'#fff':'#374151'};cursor:pointer;`;const add=(l,p,d)=>{const b=document.createElement('button');b.innerHTML=l;b.style.cssText=st(p===pg);b.disabled=d;if(d)b.style.opacity='.35';b.onclick=()=>{if(!d){pg=p;render();}};btns.appendChild(b);};add('&laquo;',1,pg===1);add('&lsaquo;',pg-1,pg===1);let s2=Math.max(1,pg-2),e2=Math.min(tp(),s2+4);s2=Math.max(1,e2-4);for(let p=s2;p<=e2;p++)add(p,p,false);add('&rsaquo;',pg+1,pg===tp());add('&raquo;',tp(),pg===tp());}render();})();
    </script>
    </body>
</html>
