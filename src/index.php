<?php
    $mostrarModal = false;
    $ticketGerado = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $caminho = 'data/marcacao.json';
        $marcacoes = json_decode(file_get_contents($caminho), true) ?? [];
        $ticketGerado = "#V-" . rand(1000, 9999);

        $nova = [
            "ticket" => $ticketGerado,
            "data" => $_POST['data_consulta'],
            "cliente" => $_POST['cliente_novo'],
            "urgencia" => $_POST['urgencia'],
            "estado" => "Pendente",
            "medico" => "",
            "processo" => ""
        ];

        $marcacoes[] = $nova;
        file_put_contents($caminho, json_encode($marcacoes, JSON_PRETTY_PRINT));
        $mostrarModal = true; // Ativa o modal
    }
?>
<!DOCTYPE html>
<html lang="pt-pt">
    <head>
        <meta charset="UTF-8">
        <title>Vida Centro de Saúde | Marcações</title>
        <link rel="stylesheet" href="css/index.css">
        <style>
            /* CSS DO MODAL */
            .modal-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0, 43, 92, 0.85);
                z-index: 2000;
                justify-content: center;
                align-items: center;
            }
            .modal-caixa {
                background: white;
                padding: 40px;
                border-radius: 15px;
                text-align: center;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                animation: subir 0.3s ease;
            }
            @keyframes subir { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

            .modal-caixa h2 { color: #28a745; margin-top: 0; }
            .ticket-destaque {
                font-size: 32px;
                color: #002b5c;
                background: #eef2f7;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                border: 2px dashed #00a8ff;
                display: block;
            }
            .btn-fechar {
                background: #00a8ff;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 25px;
                cursor: pointer;
                font-weight: bold;
                transition: 0.3s;
            }
            .btn-fechar:hover { background: #002b5c; }
        </style>
    </head>
    <body>

        <div id="modalSucesso" class="modal-overlay" style="<?php echo $mostrarModal ? 'display: flex;' : ''; ?>">
            <div class="modal-caixa">
                <div style="font-size: 50px; color: #28a745;">✓</div>
                <h2>Marcação Enviada!</h2>
                <p>O seu agendamento foi recebido com sucesso. Por favor, guarde o seu número de ticket:</p>
                <span class="ticket-destaque"><?php echo $ticketGerado; ?></span>
                <p><small>Apresente este código na receção da clínica.</small></p>
                <button class="btn-fechar" onclick="fecharModal()">Entendi, obrigado!</button>
            </div>
        </div>

        <nav class="barra-navegacao">
            <div class="logo">Vida Centro de Saúde</div>
            <ul>
                <li><a href="#servicos">Serviços</a></li>
                <li><a href="#marcar">Consulta</a></li>
            </ul>
        </nav>

        <header class="cabecalho-principal">
            <h1>Vida Centro de Saúde</h1>
            <p>Excelência no atendimento médico e diagnóstico especializado.</p>
            <a href="#marcar" class="botao-marcar">Marcar Consulta</a>
        </header>

        <section id="servicos" class="seccao-servicos">
            <h2>Os Nossos Serviços</h2>
            <div class="grelha-servicos">
                <div class="cartao-servico"><h3>ECG</h3><p>Eletrocardiogramas detalhados.</p></div>
                <div class="cartao-servico"><h3>Ecografias</h3><p>Imagem de alta precisão.</p></div>
                <div class="cartao-servico"><h3>Medicina Geral</h3><p>Rotina para a família.</p></div>
                <div class="cartao-servico"><h3>Pediatria</h3><p>Saúde infantil dedicada.</p></div>
            </div>
        </section>

        <section id="marcar" class="seccao-marcacao">
            <div class="container-form">
                <h2>Agendamento Online</h2>
                <p>O seu ticket será gerado automaticamente.</p>
                <form method="POST" class="formulario-marcacao">
                    <label>É um cliente novo?</label>
                    <select name="cliente_novo" class="campo-entrada" required>
                        <option value="sim">Sim</option>
                        <option value="nao">Não</option>
                    </select>

                    <label>Data da Consulta</label>
                    <input type="date" name="data_consulta" class="campo-entrada" required min="<?php echo date('Y-m-d'); ?>">

                    <label>Tipo de Consulta</label>
                    <select name="urgencia" class="campo-entrada">
                        <option value="normal">Normal</option>
                        <option value="urgente">Urgente (Taxa adicional)</option>
                    </select>

                    <button type="submit" class="botao-enviar">Solicitar Marcação</button>
                </form>
            </div>
        </section>

        <footer class="rodape">
            <p>&copy; 2026 Vida Centro de Saúde.</p>
            <a href="login.php" class="acesso-staff">Acesso Interno</a>
        </footer>

        <script>
            function fecharModal() {
                document.getElementById('modalSucesso').style.display = 'none';
            }

            // Fecha o modal se clicar fora da caixa branca
            window.onclick = function(event) {
                let modal = document.getElementById('modalSucesso');
                if (event.target == modal) {
                    fecharModal();
                }
            }
        </script>
    </body>
</html>
