<?php
    session_start();
    if (!isset($_SESSION['usuario_nome'])) header("Location: login.php");

    // LER DADOS REAIS DO JSON
    $caminho_json = 'data/marcacao.json';
    $historico = [];

    if (file_exists($caminho_json)) {
        $conteudo = file_get_contents($caminho_json);
        $historico = json_decode($conteudo, true) ?? [];
    }

    // Inverter a ordem para mostrar os mais recentes primeiro
    $historico = array_reverse($historico);
?>
<!DOCTYPE html>
<html lang="pt-pt">
    <head>
        <meta charset="UTF-8">
        <title>Histórico | Vida</title>
        <link rel="stylesheet" href="css/medico.css">
    </head>
    <body style="background: #f4f7f6;">
        <div style="padding: 40px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Histórico Geral de Atendimentos (<?php echo count($historico); ?> registos)</h2>
                <button onclick="history.back()" class="btn-concluir">Voltar</button>
            </div>

            <table class="tabela-medico" style="margin-top: 30px;">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ticket</th>
                        <th>Médico</th>
                        <th>Processo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr><td colspan="5">Nenhum registo encontrado no JSON.</td></tr>
                    <?php else: ?>
                        <?php foreach($historico as $h): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($h['data']); ?></td>
                            <td><strong><?php echo htmlspecialchars($h['ticket']); ?></strong></td>
                            <td><?php echo htmlspecialchars($h['medico'] ?: '---'); ?></td>
                            <td><?php echo htmlspecialchars($h['processo'] ?: '---'); ?></td>
                            <td>
                                <span style="color: <?php echo ($h['estado'] == 'Concluido' ? 'green' : 'orange'); ?>;">
                                    ● <?php echo htmlspecialchars($h['estado']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </body>
</html>
