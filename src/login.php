<?php
    session_start();
    $erro = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $chave = $_POST['chave'];

        $usuarios = json_decode(file_get_contents('data/utilizadores.json'), true);

        foreach ($usuarios['staff'] as $u) {
            if ($u['id'] === $id && $u['chave'] === $chave) {
                $_SESSION['usuario_nome'] = $u['nome'];
                $_SESSION['usuario_tipo'] = $u['tipo'];

                if ($u['tipo'] == 'medico') header("Location: medico.php");
                else header("Location: recepcionista.php");
                exit;
            }
        }
        $erro = "Credenciais inválidas!";
    }
?>
<!DOCTYPE html>
<html lang="pt-pt">
    <head>
        <meta charset="UTF-8">
        <title>Acesso Interno | Vida</title>
        <style>
            body { background: #002b5c; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; }
            .caixa-login { background: white; padding: 40px; border-radius: 8px; width: 300px; text-align: center; }
            input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; box-sizing: border-box; }
            button { width: 100%; padding: 12px; background: #00a8ff; color: white; border: none; cursor: pointer; font-weight: bold; }
            .erro { color: red; font-size: 13px; }
        </style>
    </head>
    <body>
        <div class="caixa-login">
            <h3>Vida - Acesso Staff</h3>
            <?php if($erro) echo "<p class='erro'>$erro</p>"; ?>
            <form method="POST">
                <input type="text" name="id" placeholder="ID de Utilizador" required>
                <input type="password" name="chave" placeholder="Chave de Acesso" required>
                <button type="submit">Entrar no Sistema</button>
            </form>
        </div>
    </body>
</html>
