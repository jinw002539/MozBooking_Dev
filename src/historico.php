<?php
	session_start();
	if (!isset($_SESSION['usuario_nome'])) { header("Location: login.php"); exit; }

	$caminho = 'data/marcacao.json';
	$medico_clinica   = "Dr. Armando Silva";
	$medicos_externos = ["Dr.ª Luísa Mário", "Dr. Carlos Nhaca"];
	$is_recepcionista = ($_SESSION['usuario_tipo'] === 'recepcionista');
	$is_medico        = ($_SESSION['usuario_tipo'] === 'medico');

	// ── EDIÇÃO (recepcionista + médico externo apenas) ─────────────────
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_ticket']) && $is_recepcionista) {
	    $dados = json_decode(file_get_contents($caminho), true) ?? [];
	    $ticket  = trim($_POST['edit_ticket']);
	    $medico  = trim($_POST['edit_medico']  ?? '');
	    $processo= trim($_POST['edit_processo'] ?? '');
	    $estado  = trim($_POST['edit_estado']  ?? 'Pendente');

	    // Só permite editar se o médico for externo
	    foreach ($dados as &$m) {
	        if ($m['ticket'] === $ticket && in_array($m['medico'], $medicos_externos)) {
	            // Não permitir "Em atendimento" via recepcionista
	            if ($estado === 'Em atendimento') $estado = 'Pendente';
	            $m['medico']   = $medico;
	            $m['processo'] = $processo;
	            $m['estado']   = $estado;
	            break;
	        }
	    }
	    unset($m);
	    file_put_contents($caminho, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	    // Redirect preserving filters
	    $qs = http_build_query(array_filter([
	        'estado' => $_POST['f_estado'] ?? '',
	        'tipo'   => $_POST['f_tipo']   ?? '',
	        'data'   => $_POST['f_data']   ?? '',
	        'q'      => $_POST['f_q']      ?? '',
	    ]));
	    header("Location: historico.php" . ($qs ? "?$qs" : '') . "&editok=1");
	    exit;
	}

	$historico = file_exists($caminho) ? array_reverse(json_decode(file_get_contents($caminho), true) ?? []) : [];

	// Filtros
	$filtro_estado = $_GET['estado'] ?? '';
	$filtro_tipo   = $_GET['tipo']   ?? '';
	$filtro_data   = $_GET['data']   ?? '';
	$q             = $_GET['q']      ?? '';

	$filtrado = array_filter($historico, function($h) use ($filtro_estado, $filtro_tipo, $filtro_data, $q) {
	    if ($filtro_estado && $h['estado'] != $filtro_estado) return false;
	    if ($filtro_tipo   && $h['urgencia'] != $filtro_tipo) return false;
	    if ($filtro_data   && $h['data'] != $filtro_data) return false;
	    if ($q && stripos($h['ticket'], $q) === false && stripos($h['medico'], $q) === false) return false;
	    return true;
	});

	$back_url = $is_medico ? 'medico.php' : 'recepcionista.php';
	$medicos_lista = ["Dr. Armando Silva", "Dr.ª Luísa Mário", "Dr. Carlos Nhaca"];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico | Vida</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .brand { font-family: 'Playfair Display', serif; }
        tr:hover td { background: #f8fbff; }
        /* Modal */
        #editModal { display:none; }
        #editModal.open { display:flex; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<header class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 z-10 shadow-sm">
    <div class="flex items-center gap-4">
        <button onclick="history.back()"
            class="text-gray-400 hover:text-gray-600 transition text-sm flex items-center gap-1 font-medium">
            ← Voltar
        </button>
        <div>
            <div class="brand text-xl"><span class="text-cyan-500">Vida</span> — Histórico</div>
            <div class="text-xs text-gray-400"><?= count($filtrado) ?> de <?= count($historico) ?> registos</div>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <?php if (isset($_GET['editok'])): ?>
        <span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full">✅ Editado com sucesso!</span>
        <?php endif; ?>
        <a href="<?= $back_url ?>" class="bg-blue-600 text-white text-sm px-4 py-2 rounded-xl font-semibold hover:bg-blue-700 transition">
            Dashboard
        </a>
    </div>
</header>

<main class="max-w-7xl mx-auto p-6">

    <!-- FILTROS -->
    <form method="GET" class="bg-white rounded-2xl p-5 mb-6 shadow-sm border border-gray-100">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="text-xs font-semibold text-gray-500 block mb-1">Pesquisar Ticket/Médico</label>
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
                    class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none transition"
                    placeholder="Ex: V-A2F3...">
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 block mb-1">Estado</label>
                <select name="estado" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <option value="">Todos</option>
                    <option value="Pendente"       <?= $filtro_estado=='Pendente'      ?'selected':'' ?>>Pendente</option>
                    <option value="Em atendimento" <?= $filtro_estado=='Em atendimento'?'selected':'' ?>>Em atendimento</option>
                    <option value="Concluido"      <?= $filtro_estado=='Concluido'     ?'selected':'' ?>>Concluído</option>
                    <option value="Cancelado"      <?= $filtro_estado=='Cancelado'     ?'selected':'' ?>>Cancelado</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 block mb-1">Tipo</label>
                <select name="tipo" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <option value="">Todos</option>
                    <option value="normal"  <?= $filtro_tipo=='normal' ?'selected':'' ?>>Normal</option>
                    <option value="urgente" <?= $filtro_tipo=='urgente'?'selected':'' ?>>Urgente</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 rounded-xl transition">
                    Filtrar
                </button>
                <a href="historico.php" class="px-4 py-2 border-2 border-gray-200 text-gray-500 text-sm rounded-xl hover:border-gray-400 transition">✕</a>
            </div>
        </div>
    </form>

    <!-- TABELA -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- PAGINAÇÃO (topo) -->
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
            <span id="infoHistorico" class="text-xs text-gray-400"></span>
            <div class="flex items-center gap-1" id="botoesHistorico"></div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">#</th>
                        <th class="px-5 py-3 text-left">Data</th>
                        <th class="px-5 py-3 text-left">Senha</th>
                        <th class="px-5 py-3 text-left">Paciente</th>
                        <th class="px-5 py-3 text-left">Tipo</th>
                        <th class="px-5 py-3 text-left">Médico</th>
                        <th class="px-5 py-3 text-left">Processo</th>
                        <th class="px-5 py-3 text-left">Estado</th>
                        <?php if ($is_recepcionista): ?>
                        <th class="px-5 py-3 text-left">Acção</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="tbodyHistorico" class="divide-y divide-gray-100">
                <?php if (empty($filtrado)): ?>
                    <tr><td colspan="<?= $is_recepcionista ? 9 : 8 ?>" class="px-5 py-12 text-center text-gray-400">Nenhum registo encontrado.</td></tr>
                <?php else: ?>
                    <?php $i = count($filtrado); foreach ($filtrado as $h): ?>
                    <?php $pode_editar = $is_recepcionista && in_array($h['medico'] ?? '', $medicos_externos); ?>
                    <tr class="transition-colors">
                        <td class="px-5 py-3 text-gray-400 text-xs"><?= $i-- ?></td>
                        <td class="px-5 py-3 text-gray-700 font-medium">
                            <?= date('d/m/Y', strtotime($h['data'])) ?>
                        </td>
                        <td class="px-5 py-3">
                            <span class="font-mono font-bold text-blue-800 bg-blue-50 px-2 py-1 rounded text-xs"><?= htmlspecialchars($h['ticket']) ?></span>
                        </td>
                        <td class="px-5 py-3">
                            <?= $h['cliente']=='novo'
                                ? '<span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-1 rounded-full">🆕 Novo</span>'
                                : '<span class="text-gray-400 text-xs">Antigo</span>' ?>
                        </td>
                        <td class="px-5 py-3">
                            <?= $h['urgencia']=='urgente'
                                ? '<span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded-full">🚨</span>'
                                : '<span class="text-gray-400 text-xs">Normal</span>' ?>
                        </td>
                        <td class="px-5 py-3 text-gray-600"><?= htmlspecialchars($h['medico'] ?: '—') ?></td>
                        <td class="px-5 py-3 text-gray-600"><?= htmlspecialchars($h['processo'] ?: '—') ?></td>
                        <td class="px-5 py-3">
                            <?php
                            $estado_cfg = [
                                'Pendente'       => 'bg-amber-100 text-amber-700',
                                'Em atendimento' => 'bg-blue-100 text-blue-700',
                                'Concluido'      => 'bg-green-100 text-green-700',
                                'Cancelado'      => 'bg-red-100 text-red-600',
                            ];
                            $cls = $estado_cfg[$h['estado']] ?? 'bg-gray-100 text-gray-500';
                            echo "<span class='$cls text-xs font-medium px-2 py-1 rounded-full'>{$h['estado']}</span>";
                            ?>
                        </td>
                        <?php if ($is_recepcionista): ?>
                        <td class="px-5 py-3">
                            <?php if ($pode_editar): ?>
                            <button type="button"
                                onclick="abrirEditar(
                                    '<?= htmlspecialchars(addslashes($h['ticket']), ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars(addslashes($h['medico'] ?? ''), ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars(addslashes($h['processo'] ?? ''), ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars(addslashes($h['estado']), ENT_QUOTES) ?>'
                                )"
                                class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                ✏️ Editar
                            </button>
                            <?php else: ?>
                            <span class="text-gray-300 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php if ($is_recepcionista): ?>
<!-- ── MODAL DE EDIÇÃO (só médicos externos) ──────────────────── -->
<div id="editModal" class="fixed inset-0 bg-black/40 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative">
        <button onclick="fecharEditar()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">✕</button>
        <h2 class="text-lg font-bold text-gray-800 mb-1">Editar Consulta</h2>
        <p class="text-xs text-gray-400 mb-5">Apenas para consultas de médicos externos</p>

        <form method="POST">
            <input type="hidden" name="edit_ticket"  id="edit_ticket">
            <!-- Preservar filtros activos -->
            <input type="hidden" name="f_estado" value="<?= htmlspecialchars($filtro_estado) ?>">
            <input type="hidden" name="f_tipo"   value="<?= htmlspecialchars($filtro_tipo) ?>">
            <input type="hidden" name="f_data"   value="<?= htmlspecialchars($filtro_data) ?>">
            <input type="hidden" name="f_q"      value="<?= htmlspecialchars($q) ?>">

            <div class="mb-4">
                <label class="text-xs font-semibold text-gray-500 block mb-1">Senha</label>
                <input type="text" id="edit_ticket_display"
                    class="w-full border-2 border-gray-100 bg-gray-50 rounded-xl px-3 py-2 text-sm text-gray-400 font-mono" readonly>
            </div>
            <div class="mb-4">
                <label class="text-xs font-semibold text-gray-500 block mb-1">Médico</label>
                <select name="edit_medico" id="edit_medico"
                    class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <?php foreach ($medicos_externos as $me): ?>
                    <option value="<?= $me ?>"><?= $me ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="text-xs font-semibold text-gray-500 block mb-1">Nº Processo</label>
                <input type="text" name="edit_processo" id="edit_processo"
                    class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                    placeholder="Número de processo">
            </div>
            <div class="mb-5">
                <label class="text-xs font-semibold text-gray-500 block mb-1">Estado</label>
                <select name="edit_estado" id="edit_estado"
                    class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <option value="Pendente">Pendente</option>
                    <option value="Concluido">Concluído</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
            </div>
            <div class="flex gap-3">
                <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-xl transition text-sm">
                    Guardar Alterações
                </button>
                <button type="button" onclick="fecharEditar()"
                    class="px-5 py-2 border-2 border-gray-200 text-gray-500 text-sm rounded-xl hover:border-gray-400 transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirEditar(ticket, medico, processo, estado) {
    document.getElementById('edit_ticket').value         = ticket;
    document.getElementById('edit_ticket_display').value = ticket;
    document.getElementById('edit_processo').value       = processo;

    // Seleccionar médico
    const selMed = document.getElementById('edit_medico');
    for (let opt of selMed.options) {
        opt.selected = (opt.value === medico);
    }

    // Seleccionar estado
    const selEst = document.getElementById('edit_estado');
    const estadoSeguro = (estado === 'Em atendimento') ? 'Pendente' : estado;
    for (let opt of selEst.options) {
        opt.selected = (opt.value === estadoSeguro);
    }

    document.getElementById('editModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function fecharEditar() {
    document.getElementById('editModal').classList.remove('open');
    document.body.style.overflow = '';
}

// Fechar clicando fora
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) fecharEditar();
});
</script>
<?php endif; ?>

<script>
(function(){
    const PER_PAGE = 10;
    const tbody    = document.getElementById('tbodyHistorico');
    const infoEl   = document.getElementById('infoHistorico');
    const botoesEl = document.getElementById('botoesHistorico');
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
            b.onclick = () => { if(!disabled){ paginaAtual = page; render(); window.scrollTo({top: 0, behavior:'smooth'}); }};
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
