<?php
    session_start();
    if(!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] != 'recepcionista') header("Location: login.php");

    $dados = json_decode(file_get_contents('data/marcacao.json'), true) ?? [];

    $hoje = date('Y-m-d');
    $total_hoje = 0;
    $total_sistema = count($dados);
    $pendentes = 0;

    foreach($dados as $d) {
        if($d['data'] == $hoje) $total_hoje++;
        if($d['estado'] == 'Pendente') $pendentes++;
    }
?>
<!DOCTYPE html>
<html lang="pt-pt">
    <head>
        <meta charset="UTF-8">
        <title>Receção | Vida</title>
        <link rel="stylesheet" href="css/recepcionista.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body>
        <header class="topo-admin">
            <h2>Vida - Gestão de Atendimento</h2>
            <div><?php echo $_SESSION['usuario_nome']; ?> | <a href="logout.php" style="color:white;">Sair</a></div>
        </header>

        <main class="container">
            <section class="bloco" style="display: flex; gap: 20px; align-items: stretch;">
                <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                    <div style="background: #002b5c; color: white; padding: 20px; border-radius: 8px; text-align: center;">
                        <small>Hoje</small>
                        <h2><?php echo $total_hoje; ?></h2>
                    </div>
                    <div style="background: #eef2f7; padding: 20px; border-radius: 8px; text-align: center;">
                        <small>Pendentes</small>
                        <h2 style="color: #d9534f;"><?php echo $pendentes; ?></h2>
                    </div>
                </div>

                <div style="flex: 2; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <canvas id="chartRececao" height="120"></canvas>
                </div>

                <div style="flex: 1;">
                    <button onclick="location.href='historico.php'" class="btn-confirmar" style="width:100%; height:100%; font-size: 16px; cursor:pointer;">
                        📂 VER HISTÓRICO<br><small>(<?php echo $total_sistema; ?> Registos)</small>
                    </button>
                </div>
            </section>

            <section class="bloco">
                <h3>Tickets para Processar</h3>
                <table class="tabela-estilizada">
                    <thead>
                        <tr><th>Ticket</th><th>Data</th><th>Urgência</th><th>Médico</th><th>Processo</th><th>Ação</th></tr>
                    </thead>
                    <tbody>
                        <?php $count = 0; foreach($dados as $m): if($m['estado'] == 'Pendente'): $count++; ?>
                        <tr>
                            <td><strong><?php echo $m['ticket']; ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($m['data'])); ?></td>
                            <td><span class="tag <?php echo ($m['urgencia']=='urgente'?'novo':'antigo'); ?>"><?php echo strtoupper($m['urgencia']); ?></span></td>
                            <td>
                                <select class="input-processo" style="width: 150px;">
                                    <option>Dr. Armando Silva</option>
                                    <option>Dr.ª Luísa Mário</option>
                                </select>
                            </td>
                            <td><input type="text" placeholder="Papel nr..." class="input-processo"></td>
                            <td><button class="btn-confirmar">Confirmar</button></td>
                        </tr>
                        <?php endif; endforeach; ?>
                        <?php if($count == 0) echo "<tr><td colspan='6' style='text-align:center;'>Nenhuma marcação pendente.</td></tr>"; ?>
                    </tbody>
                </table>
            </section>
        </main>

        <script>
            new Chart(document.getElementById('chartRececao'), {
                type: 'line',
                data: {
                    labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'],
                    datasets: [{
                        label: 'Fluxo de Pacientes',
                        data: [5, 12, 8, 15, 10, 20],
                        borderColor: '#00a8ff',
                        backgroundColor: 'rgba(0, 168, 255, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { plugins: { legend: { display: false } } }
            });
        </script>
    </body>
</html>
