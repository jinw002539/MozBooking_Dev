<?php
session_start();
if (!isset($_SESSION['usuario_nome'])) { header("Location: login.php"); exit; }

$caminho = 'data/marcacao.json';
$historico = file_exists($caminho) ? array_reverse(json_decode(file_get_contents($caminho), true) ?? []) : [];

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_tipo   = $_GET['tipo'] ?? '';
$filtro_data   = $_GET['data'] ?? '';
$q             = $_GET['q'] ?? '';

$filtrado = array_filter($historico, function($h) use ($filtro_estado, $filtro_tipo, $filtro_data, $q) {
    if ($filtro_estado && $h['estado'] != $filtro_estado) return false;
    if ($filtro_tipo   && $h['urgencia'] != $filtro_tipo) return false;
    if ($filtro_data   && $h['data'] != $filtro_data) return false;
    if ($q && stripos($h['ticket'], $q) === false && stripos($h['medico'], $q) === false) return false;
    return true;
});

$is_medico = $_SESSION['usuario_tipo'] == 'medico';
$back_url  = $is_medico ? 'medico.php' : 'recepcionista.php';
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
    <a href="<?= $back_url ?>" class="bg-blue-600 text-white text-sm px-4 py-2 rounded-xl font-semibold hover:bg-blue-700 transition">
        Dashboard
    </a>
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
                    <option value="Pendente" <?= $filtro_estado=='Pendente'?'selected':'' ?>> Pendente</option>
                    <option value="Em atendimento" <?= $filtro_estado=='Em atendimento'?'selected':'' ?>> Em atendimento</option>
                    <option value="Concluido" <?= $filtro_estado=='Concluido'?'selected':'' ?>> Concluído</option>
                    <option value="Cancelado" <?= $filtro_estado=='Cancelado'?'selected':'' ?>> Cancelado</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-500 block mb-1">Tipo</label>
                <select name="tipo" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <option value="">Todos</option>
                    <option value="normal" <?= $filtro_tipo=='normal'?'selected':'' ?>>Normal</option>
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
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">#</th>
                        <th class="px-5 py-3 text-left">Data</th>
                        <th class="px-5 py-3 text-left">Ticket</th>
                        <th class="px-5 py-3 text-left">Paciente</th>
                        <th class="px-5 py-3 text-left">Tipo</th>
                        <th class="px-5 py-3 text-left">Médico</th>
                        <th class="px-5 py-3 text-left">Processo</th>
                        <th class="px-5 py-3 text-left">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                <?php if (empty($filtrado)): ?>
                    <tr><td colspan="8" class="px-5 py-12 text-center text-gray-400">Nenhum registo encontrado.</td></tr>
                <?php else: ?>
                    <?php $i = count($filtrado); foreach ($filtrado as $h): ?>
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
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 text-xs text-gray-400 text-right">
            A mostrar <?= count($filtrado) ?> resultado(s)
        </div>
    </div>
</main>
</body>
</html>
