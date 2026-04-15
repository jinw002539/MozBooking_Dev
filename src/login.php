<?php
session_start();
$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id    = trim($_POST['id']);
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
    $erro = "Credenciais inválidas. Tente novamente.";
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Interno | Vida</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .brand { font-family: 'Playfair Display', serif; }
        .bg-login {
            background: linear-gradient(135deg, #0a1f44 0%, #1565c0 100%);
            min-height: 100vh;
        }
        @keyframes fadeUp { from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)} }
        .fade-up { animation: fadeUp .4s ease forwards; }
        input:focus { border-color: #1565c0; box-shadow: 0 0 0 3px rgba(21,101,192,0.15); }
    </style>
</head>
<body class="bg-login flex items-center justify-center p-4">
    <div class="w-full max-w-sm fade-up">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="index.php" class="brand text-white text-3xl">
                <span class="text-cyan-400">Vida</span> Centro de Saúde
            </a>
            <p class="text-blue-200 text-sm mt-2">Portal de Acesso Interno</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-3xl p-8 shadow-2xl">
            <h2 class="text-xl font-bold text-gray-800 mb-1">Bem-vindo de volta</h2>
            <p class="text-gray-400 text-sm mb-6">Introduza as suas credenciais para continuar.</p>

            <?php if ($erro): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm mb-5 flex items-center gap-2">
                <span>⚠️</span> <?= htmlspecialchars($erro) ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-600 mb-2">ID de Utilizador</label>
                    <input type="text" name="id" required autocomplete="username"
                        class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-gray-700 focus:outline-none transition"
                        placeholder="Ex: armando">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-600 mb-2">Chave de Acesso</label>
                    <input type="password" name="chave" required autocomplete="current-password"
                        class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-gray-700 focus:outline-none transition"
                        placeholder="••••••••">
                </div>
                <button type="submit"
                    class="w-full py-3 rounded-xl text-white font-bold text-base transition hover:opacity-90 active:scale-98"
                    style="background:linear-gradient(135deg,#0a1f44,#1565c0)">
                    Entrar no Sistema
                </button>
            </form>
        </div>

        <div class="text-center mt-6">
            <a href="index.php" class="text-blue-200/60 hover:text-blue-200 text-xs transition">
                ← Voltar ao site principal
            </a>
        </div>
    </div>
</body>
</html>
