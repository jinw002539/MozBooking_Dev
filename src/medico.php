<?php
	session_start();
	if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] != 'medico') {
	    header("Location: login.php"); exit;
	}

	$caminho = 'data/marcacao.json';
	$dados = json_decode(file_get_contents($caminho), true) ?? [];

	$hoje = date('Y-m-d');
	$semana_inicio = date('Y-m-d', strtotime('monday this week'));
	$semana_fim    = date('Y-m-d', strtotime('sunday this week'));
	$mes_atual     = date('Y-m');

	$hoje_list    = array_filter($dados, fn($d) => $d['data'] == $hoje);
	$semana_list  = array_filter($dados, fn($d) => $d['data'] >= $semana_inicio && $d['data'] <= $semana_fim);
	$mes_list     = array_filter($dados, fn($d) => str_starts_with($d['data'], $mes_atual));
	$urgentes     = array_filter($dados, fn($d) => $d['urgencia'] == 'urgente');
	$novos        = array_filter($dados, fn($d) => $d['cliente'] == 'novo');
	$concluidos   = array_filter($dados, fn($d) => $d['estado'] == 'Concluido');

	// Dados gráfico mensal (6 meses)
	$meses_labels = [];
	$meses_vals   = [];
	for ($i = 5; $i >= 0; $i--) {
	    $m = date('Y-m', strtotime("-$i months"));
	    $meses_labels[] = date('M', strtotime("$m-01"));
	    $meses_vals[]   = count(array_filter($dados, fn($d) => str_starts_with($d['data'], $m)));
	}

	// Dados gráfico semanal (7 dias)
	$dias_labels = [];
	$dias_vals   = [];
	for ($i = 6; $i >= 0; $i--) {
	    $d = date('Y-m-d', strtotime("-$i days"));
	    $dias_labels[] = date('d/m', strtotime($d));
	    $dias_vals[]   = count(array_filter($dados, fn($x) => $x['data'] == $d));
	}

	// Por tipo
	$urgentes_count = count($urgentes);
	$normais_count  = count($dados) - $urgentes_count;
	$medicos_lista  = ["Dr. Armando Silva", "Dr.ª Luísa Mário", "Dr. Carlos Nhaca"];
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
		   .tab-btn { cursor:pointer; transition: all .2s; }
		   .tab-btn.active { border-color:#1565c0; color:#1565c0; background:#eff6ff; font-weight:600; }
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
		   <a href="medico.php" class="flex items-center gap-3 bg-white/10 text-white rounded-xl px-4 py-3 text-sm font-medium">
		       <span>📊</span> Painel
		   </a>
		   <a href="medico.php?tab=hoje" class="flex items-center gap-3 text-white/60 hover:text-white hover:bg-white/5 rounded-xl px-4 py-3 text-sm font-medium transition">
		       <span>📋</span> Agenda de Hoje
		   </a>
		   <a href="historico.php" class="flex items-center gap-3 text-white/60 hover:text-white hover:bg-white/5 rounded-xl px-4 py-3 text-sm font-medium transition">
		       <span>📂</span> Histórico Geral
		   </a>
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
		   <div class="flex gap-2">
		       <a href="historico.php" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-xl transition font-semibold">Ver Histórico Completo</a>
		   </div>
	    </header>

	    <main class="p-6 space-y-6 fade-in">

		   <!-- CARDS KPI -->
		   <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
		       <?php
		       $kpis = [
		           ['', count($hoje_list),    'Hoje',           'border-l-4 border-blue-500'],
		           ['', count($semana_list),  'Esta Semana',    'border-l-4 border-cyan-500'],
		           ['', count($mes_list),    'Este Mês',       'border-l-4 border-purple-500'],
		           ['', count($dados),        'Total Geral',    'border-l-4 border-gray-400'],
		       ];
		       foreach($kpis as $k): ?>
		       <div class="bg-white rounded-2xl p-5 shadow-sm <?= $k[3] ?>">
		           <div class="text-3xl mb-2"><?= $k[0] ?></div>
		           <div class="text-3xl font-bold text-gray-800"><?= $k[1] ?></div>
		           <div class="text-sm text-gray-400"><?= $k[2] ?></div>
		       </div>
		       <?php endforeach; ?>
		   </div>

		   <!-- SEGUNDA LINHA KPIs -->
		   <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
		       <div class="bg-white rounded-2xl p-5 shadow-sm border-l-4 border-red-400">
		           <div class="text-2xl mb-1"></div>
		           <div class="text-2xl font-bold"><?= $urgentes_count ?></div>
		           <div class="text-sm text-gray-400">Urgentes (total)</div>
		       </div>
		       <div class="bg-white rounded-2xl p-5 shadow-sm border-l-4 border-emerald-400">
		           <div class="text-2xl mb-1"></div>
		           <div class="text-2xl font-bold"><?= count($novos) ?></div>
		           <div class="text-sm text-gray-400">Novos Pacientes</div>
		       </div>
		       <div class="bg-white rounded-2xl p-5 shadow-sm border-l-4 border-green-400">
		           <div class="text-2xl mb-1"></div>
		           <div class="text-2xl font-bold"><?= count($concluidos) ?></div>
		           <div class="text-sm text-gray-400">Consultas Concluídas</div>
		       </div>
		   </div>

		   <!-- GRÁFICOS -->
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

		   <!-- Semanal -->
		   <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
		       <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">Fluxo Diário – Última Semana</h3>
		       <canvas id="graficoSemanal" height="70"></canvas>
		   </div>

		   <!-- TABELA DE HOJE -->
		   <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
		       <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
		           <h3 class="font-bold text-gray-800">Agenda de Hoje — <?= date('d/m/Y') ?></h3>
		           <span class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full font-semibold">
		               <?= count($hoje_list) ?> paciente(s)
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
		                       <th class="px-5 py-3 text-left">Ticket</th>
		                       <th class="px-5 py-3 text-left">Tipo</th>
		                       <th class="px-5 py-3 text-left">Paciente</th>
		                       <th class="px-5 py-3 text-left">Médico</th>
		                       <th class="px-5 py-3 text-left">Processo</th>
		                       <th class="px-5 py-3 text-left">Estado</th>
		                   </tr>
		               </thead>
		               <tbody id="tbodyMedico" class="divide-y divide-gray-100">
		               <?php if (empty($hoje_list)): ?>
		                   <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">Nenhuma marcação para hoje.</td></tr>
		               <?php else: ?>
		                   <?php foreach ($hoje_list as $m): ?>
		                   <tr class="transition-colors">
		                       <td class="px-5 py-3"><span class="font-mono font-bold text-blue-800 bg-blue-50 px-2 py-1 rounded text-xs"><?= htmlspecialchars($m['ticket']) ?></span></td>
		                       <td class="px-5 py-3">
		                           <?= $m['urgencia']=='urgente'
		                               ? '<span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded-full">🚨 URGENTE</span>'
		                               : '<span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">Normal</span>' ?>
		                       </td>
		                       <td class="px-5 py-3">
		                           <?= $m['cliente']=='novo'
		                               ? '<span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-1 rounded-full">🆕 Novo</span>'
		                               : '<span class="text-gray-500 text-xs">Antigo</span>' ?>
		                       </td>
		                       <td class="px-5 py-3 text-gray-700"><?= htmlspecialchars($m['medico'] ?: '—') ?></td>
		                       <td class="px-5 py-3 text-gray-700"><?= htmlspecialchars($m['processo'] ?: '—') ?></td>
		                       <td class="px-5 py-3">
		                           <?php
		                           $s_colors = ['Pendente'=>'amber','Em atendimento'=>'blue','Concluido'=>'green','Cancelado'=>'red'];
		                           $cor = $s_colors[$m['estado']] ?? 'gray';
		                           $dots = ['Pendente'=>'⏳','Em atendimento'=>'🔵','Concluido'=>'✅','Cancelado'=>'❌'];
		                           echo "<span class='bg-{$cor}-100 text-{$cor}-700 text-xs px-2 py-1 rounded-full font-medium'>{$dots[$m['estado']]} {$m['estado']}</span>";
		                           ?>
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
	// Mensal
	new Chart(document.getElementById('graficoMensal'), {
	    type: 'bar',
	    data: {
		   labels: <?= json_encode($meses_labels) ?>,
		   datasets: [{
		       label: 'Marcações',
		       data: <?= json_encode($meses_vals) ?>,
		       backgroundColor: 'rgba(21,101,192,0.15)',
		       borderColor: '#1565c0',
		       borderWidth: 2,
		       borderRadius: 8
		   }]
	    },
	    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
	});

	// Pizza
	new Chart(document.getElementById('graficoPizza'), {
	    type: 'doughnut',
	    data: {
		   labels: ['Normal', 'Urgente'],
		   datasets: [{ data: [<?= $normais_count ?>, <?= $urgentes_count ?>], backgroundColor: ['#1565c0','#ef4444'], borderWidth: 0 }]
	    },
	    options: { plugins: { legend: { display: false } }, cutout: '65%' }
	});

	// Semanal
	new Chart(document.getElementById('graficoSemanal'), {
	    type: 'line',
	    data: {
		   labels: <?= json_encode($dias_labels) ?>,
		   datasets: [{
		       label: 'Marcações',
		       data: <?= json_encode($dias_vals) ?>,
		       borderColor: '#00b4d8',
		       backgroundColor: 'rgba(0,180,216,0.08)',
		       fill: true,
		       tension: 0.4,
		       pointBackgroundColor: '#00b4d8',
		       pointRadius: 5
		   }]
	    },
	    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
	});

	// ── PAGINAÇÃO MÉDICO ────────────────────────────────────────
	(function(){
	    const PER_PAGE = 10;
	    const tbody    = document.getElementById('tbodyMedico');
	    const infoEl   = document.getElementById('infoMedico');
	    const botoesEl = document.getElementById('botoesMedico');
	    const linhas   = Array.from(tbody.querySelectorAll('tr'));
	    let paginaAtual = 1;

	    function totalPaginas(){ return Math.max(1, Math.ceil(linhas.length / PER_PAGE)); }

	    function render(){
		   const total = totalPaginas();
		   const inicio = (paginaAtual - 1) * PER_PAGE;
		   const fim    = inicio + PER_PAGE;

		   linhas.forEach((tr, i) => {
		       tr.style.display = (i >= inicio && i < fim) ? '' : 'none';
		   });

		   infoEl.textContent = linhas.length === 0
		       ? 'Sem registos'
		       : `A mostrar ${Math.min(inicio + 1, linhas.length)}–${Math.min(fim, linhas.length)} de ${linhas.length}`;

		   botoesEl.innerHTML = '';
		   const btnStyle = (active) =>
		       `display:inline-flex;align-items:center;justify-content:center;` +
		       `min-width:32px;height:32px;padding:0 8px;border-radius:8px;font-size:13px;font-weight:600;` +
		       `border:1.5px solid ${active ? '#1565c0' : '#e5e7eb'};` +
		       `background:${active ? '#1565c0' : '#fff'};` +
		       `color:${active ? '#fff' : '#374151'};cursor:pointer;transition:all .15s;`;

		   const addBtn = (label, page, disabled) => {
		       const b = document.createElement('button');
		       b.innerHTML = label;
		       b.style.cssText = btnStyle(page === paginaAtual);
		       b.disabled = disabled;
		       if(disabled) b.style.opacity = '0.35';
		       b.onclick = () => { if(!disabled){ paginaAtual = page; render(); }};
		       botoesEl.appendChild(b);
		   };

		   addBtn('&laquo;', 1, paginaAtual === 1);
		   addBtn('&lsaquo;', paginaAtual - 1, paginaAtual === 1);

		   let s = Math.max(1, paginaAtual - 2);
		   let e = Math.min(total, s + 4);
		   s = Math.max(1, e - 4);
		   for(let p = s; p <= e; p++) addBtn(p, p, false);

		   addBtn('&rsaquo;', paginaAtual + 1, paginaAtual === total);
		   addBtn('&raquo;', total, paginaAtual === total);
	    }

	    render();
	})();
	</script>
	</body>
</html>
