<?php
session_start();
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] != 'recepcionista') {
    header("Location: login.php"); exit;
}

$caminho = 'data/marcacao.json';
$marcacoes = json_decode(file_get_contents($caminho), true) ?? [];

// Actualizar processo/médico via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update') {
        $ticket  = $_POST['ticket'];
        $medico  = trim($_POST['medico']);
        $processo= trim($_POST['processo']);
        $estado  = trim($_POST['estado'] ?? 'Pendente');

        foreach ($marcacoes as &$m) {
            if ($m['ticket'] === $ticket) {
                $m['medico']  = $medico;
                $m['processo']= $processo;
                $m['estado']  = $estado;
                break;
            }
        }
        file_put_contents($caminho, json_encode($marcacoes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header("Location: recepcionista.php?ok=1"); exit;
    }

    // Cancelamento / notificação
    if ($_POST['action'] == 'notificar') {
        $msg_pt = trim($_POST['msg_pt']);
        $msg_en = trim($_POST['msg_en']);
        $ativa  = (int)($_POST['ativa'] ?? 1);
        $notif  = ['ativa' => $ativa, 'mensagem_pt' => $msg_pt, 'mensagem_en' => $msg_en, 'criado_em' => date('Y-m-d H:i:s')];
        file_put_contents('data/notificacao.json', json_encode($notif, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header("Location: recepcionista.php?ok=2"); exit;
    }
}

$hoje = date('Y-m-d');
$marcacoes_hoje = array_filter($marcacoes, fn($m) => $m['data'] === $hoje);
$pendentes_hoje = array_filter($marcacoes_hoje, fn($m) => $m['estado'] == 'Pendente');
$total_geral   = count($marcacoes);
$novos_hoje    = array_filter($marcacoes_hoje, fn($m) => $m['cliente'] == 'novo');
$urgentes_hoje = array_filter($marcacoes_hoje, fn($m) => $m['urgencia'] == 'urgente');

// Dados para gráfico semanal (últimos 7 dias)
$chart_labels = [];
$chart_vals   = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($d));
    $chart_vals[]   = count(array_filter($marcacoes, fn($m) => $m['data'] == $d));
}

// Ler notificação existente
$notif_path = 'data/notificacao.json';
$notif_atual = ['ativa'=>0,'mensagem_pt'=>'','mensagem_en'=>''];
if (file_exists($notif_path)) {
    $notif_atual = json_decode(file_get_contents($notif_path), true) ?? $notif_atual;
}

$medicos_lista = ["Dr. Armando Silva", "Dr.ª Luísa Mário", "Dr. Carlos Nhaca"];
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
		   <a href="recepcionista.php" class="flex items-center gap-3 bg-white/10 text-white rounded-xl px-4 py-3 text-sm font-medium">
		       <span></span> Painel
		   </a>
		   <a href="historico.php" class="flex items-center gap-3 text-white/60 hover:text-white hover:bg-white/5 rounded-xl px-4 py-3 text-sm font-medium transition">
		       <span></span> Histórico
		   </a>
	    </nav>
	    <div class="border-t border-white/10 pt-4">
		   <p class="text-white/50 text-xs mb-1"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
		   <a href="logout.php" class="text-red-300 hover:text-red-200 text-xs transition">→ Sair</a>
	    </div>
	</aside>

	<!-- MAIN -->
	<div class="flex-1 overflow-x-hidden">
	    <!-- TOP BAR -->
	    <header class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 z-20">
		   <div>
		       <h1 class="text-xl font-bold text-gray-800">Gestão de Atendimento</h1>
		       <p class="text-sm text-gray-400"><?= date('l, d \d\e F \d\e Y') ?></p>
		   </div>
		   <div class="flex items-center gap-3">
		       <?php if (isset($_GET['ok'])): ?>
		       <span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full fade-in">
		           <?= $_GET['ok'] == 1 ? '✅ Guardado!' : '🔔 Notificação enviada!' ?>
		       </span>
		       <?php endif; ?>
		       <a href="logout.php" class="md:hidden text-sm text-red-500 font-medium">Sair</a>
		   </div>
	    </header>

	    <main class="p-6 space-y-6">

		   <!-- STATS -->
		   <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
		       <?php
		       $stats = [
		           ['', count($marcacoes_hoje), 'Hoje', 'bg-blue-600'],
		           ['', count($pendentes_hoje), 'Pendentes', 'bg-amber-500'],
		           ['', count($novos_hoje), 'Novos Pacientes', 'bg-emerald-500'],
		           ['', count($urgentes_hoje), 'Urgentes', 'bg-red-500'],
		       ];
		       foreach($stats as $s): ?>
		       <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
		           <div class="text-3xl mb-2"><?= $s[0] ?></div>
		           <div class="text-3xl font-bold text-gray-800"><?= $s[1] ?></div>
		           <div class="text-sm text-gray-400 mt-1"><?= $s[2] ?></div>
		       </div>
		       <?php endforeach; ?>
		   </div>

		   <!-- GRÁFICO + NOTIFICAÇÃO -->
		   <div class="grid lg:grid-cols-2 gap-6">
		       <!-- Gráfico -->
		       <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
		           <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">Fluxo – Últimos 7 Dias</h3>
		           <canvas id="chartSemanal" height="120"></canvas>
		       </div>

		       <!-- Painel Notificação -->
		       <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
		           <h3 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">🔔 Notificação de Cancelamento</h3>
		           <form method="POST">
		               <input type="hidden" name="action" value="notificar">
		               <div class="mb-3">
		                   <label class="text-xs font-semibold text-gray-500 block mb-1">Mensagem (PT)</label>
		                   <input type="text" name="msg_pt" value="<?= htmlspecialchars($notif_atual['mensagem_pt'] ?? '') ?>"
		                       class="w-full" placeholder="Ex: Consultas suspensas a 20/01 por cancelamento médico">
		               </div>
		               <div class="mb-3">
		                   <label class="text-xs font-semibold text-gray-500 block mb-1">Message (EN)</label>
		                   <input type="text" name="msg_en" value="<?= htmlspecialchars($notif_atual['mensagem_en'] ?? '') ?>"
		                       class="w-full" placeholder="Ex: Consultations on 20/01 cancelled">
		               </div>
		               <div class="flex items-center gap-4 mb-4">
		                   <label class="text-xs font-semibold text-gray-500">Notificação activa?</label>
		                   <select name="ativa" class="text-sm">
		                       <option value="1" <?= ($notif_atual['ativa'] ?? 0) ? 'selected' : '' ?>>Sim</option>
		                       <option value="0" <?= !($notif_atual['ativa'] ?? 0) ? 'selected' : '' ?>>Não</option>
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
		                   <?php foreach ($marcacoes_hoje as $m): ?>
		                   <tr class="transition-colors">
		                       <form method="POST">
		                       <input type="hidden" name="action" value="update">
		                       <input type="hidden" name="ticket" value="<?= htmlspecialchars($m['ticket']) ?>">
		                       <td class="px-5 py-3">
		                           <span class="font-mono font-bold text-blue-800 bg-blue-50 px-2 py-1 rounded"><?= htmlspecialchars($m['ticket']) ?></span>
		                       </td>
		                       <td class="px-5 py-3">
		                           <?php if ($m['urgencia'] == 'urgente'): ?>
		                               <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded-full">🚨 URGENTE</span>
		                           <?php else: ?>
		                               <span class="bg-gray-100 text-gray-500 text-xs font-medium px-2 py-1 rounded-full">Normal</span>
		                           <?php endif; ?>
		                       </td>
		                       <td class="px-5 py-3">
		                           <?php if ($m['cliente'] == 'novo'): ?>
		                               <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-1 rounded-full">🆕 Novo</span>
		                           <?php else: ?>
		                               <span class="text-gray-500 text-xs">Antigo</span>
		                           <?php endif; ?>
		                       </td>
		                       <td class="px-5 py-3">
		                           <select name="medico" class="text-xs min-w-[160px]">
		                               <option value="">— Atribuir médico —</option>
		                               <?php foreach ($medicos_lista as $med): ?>
		                               <option value="<?= $med ?>" <?= $m['medico'] == $med ? 'selected' : '' ?>><?= $med ?></option>
		                               <?php endforeach; ?>
		                           </select>
		                       </td>
		                       <td class="px-5 py-3">
		                           <input type="text" name="processo" value="<?= htmlspecialchars($m['processo']) ?>"
		                               placeholder="<?= $m['cliente'] == 'novo' ? 'Abrir processo...' : 'Opcional' ?>"
		                               class="text-xs w-36 <?= $m['cliente'] == 'novo' && !$m['processo'] ? 'border-amber-400' : '' ?>">
		                       </td>
		                       <td class="px-5 py-3">
		                           <select name="estado" class="text-xs">
		                               <option value="Pendente" <?= $m['estado']=='Pendente'?'selected':'' ?>> Pendente</option>
		                               <option value="Em atendimento" <?= $m['estado']=='Em atendimento'?'selected':'' ?>> Em atendimento</option>
		                               <option value="Concluido" <?= $m['estado']=='Concluido'?'selected':'' ?>> Concluído</option>
		                               <option value="Cancelado" <?= $m['estado']=='Cancelado'?'selected':'' ?>> Cancelado</option>
		                           </select>
		                       </td>
		                       <td class="px-5 py-3">
		                           <button type="submit"
		                               class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
		                               Guardar
		                           </button>
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
	new Chart(document.getElementById('chartSemanal'), {
	    type: 'bar',
	    data: {
		   labels: <?= json_encode($chart_labels) ?>,
		   datasets: [{
		       label: 'Marcações',
		       data: <?= json_encode($chart_vals) ?>,
		       backgroundColor: 'rgba(21,101,192,0.15)',
		       borderColor: '#1565c0',
		       borderWidth: 2,
		       borderRadius: 8
		   }]
	    },
	    options: {
		   plugins: { legend: { display: false } },
		   scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
	    }
	});

	// ── PAGINAÇÃO RECEPCIONISTA ──────────────────────────────────
	(function(){
	    const PER_PAGE = 10;
	    const tbody    = document.getElementById('tbodyRecep');
	    const infoEl   = document.getElementById('infoRecep');
	    const botoesEl = document.getElementById('botoesRecep');
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

		   // Botões
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

		   // Janela de páginas
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
