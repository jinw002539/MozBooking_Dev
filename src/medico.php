<?php
    session_start();
    if(!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] != 'medico') header("Location: login.php");

    $dados = json_decode(file_get_contents('data/marcacao.json'), true) ?? [];

    $meses = ["01"=>0, "02"=>0, "03"=>0, "04"=>0, "05"=>0, "06"=>0];
    foreach($dados as $d) {
        $m = date('m', strtotime($d['data']));
        if(isset($meses[$m])) $meses[$m]++;
    }
    $valores = implode(',', array_values($meses));
?>
<!DOCTYPE html>
<html lang="pt-pt">
    <head>
        <meta charset="UTF-8">
        <title>Direção | Vida</title>
        <link rel="stylesheet" href="css/medico.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body>
        <div class="layout-medico">
            <aside class="menu-lateral">
                <div class="perfil">
                    <h3><?php echo $_SESSION['usuario_nome']; ?></h3>
                    <small>Diretor Clínico</small>
                </div>
                <ul>
                    <li class="ativo">Painel Estatístico</li>
                    <li onclick="location.href='historico.php'">Histórico de Consultas</li>
                    <li><a href="logout.php" style="color:white; text-decoration:none;">Sair</a></li>
                </ul>
            </aside>

            <main class="conteudo-principal">
                <h1>Desempenho de Marcações por Mês</h1>

                <div style="display: flex; gap: 20px;">
                    <div style="flex: 2; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                        <canvas id="graficoDiretor"></canvas>
                    </div>

                    <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                        <div style="background: white; padding: 20px; border-radius: 12px; border-left: 5px solid #00a8ff;">
                            <small>Total de Registos</small>
                            <h2><?php echo count($dados); ?></h2>
                        </div>
                        <button onclick="location.href='historico.php'" class="btn-concluir" style="height: 100px; font-size: 18px;">
                            Ver Lista Completa
                        </button>
                    </div>
                </div>
            </main>
        </div>

        <script>
            new Chart(document.getElementById('graficoDiretor'), {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Marcações Confirmadas',
                        data: [<?php echo $valores; ?>],
                        backgroundColor: '#002b5c',
                        borderRadius: 5
                    }]
                }
            });
        </script>
    </body>
</html>
