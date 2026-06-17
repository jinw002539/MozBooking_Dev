<?php
    session_start();
    require_once __DIR__ . '/db.php';

    $erro = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['id']    ?? '');
        $chave    = trim($_POST['chave'] ?? '');

        if ($username && $chave) {
            $stmt = db()->prepare("SELECT * FROM staff WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($chave, $user['chave'])) {
                session_regenerate_id(true);
                $_SESSION['usuario_id']   = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_tipo'] = $user['tipo'];
                $_SESSION['clinica']      = (bool)$user['clinica'];

                header('Location: ' . ($user['tipo'] === 'medico' ? 'medico.php' : 'recepcionista.php'));
                exit;
            }
        }
        $erro = 'Credenciais inválidas. Tente novamente.';
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
            .bg-login { background: linear-gradient(135deg, #0a1f44 0%, #1565c0 100%); min-height: 100vh; }
            @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
            .fade-up { animation: fadeUp .4s ease forwards; }
            input:focus { border-color: #1565c0; box-shadow: 0 0 0 3px rgba(21,101,192,0.15); }
        </style>
    </head>
    <body class="bg-login flex items-center justify-center p-4">
        <div class="w-full max-w-sm fade-up">
            <div class="text-center mb-8">
                <a href="index.php" class="brand text-white text-3xl">
                    <span class="text-cyan-400">Vida</span> Centro de Saúde
                </a>
                <p class="text-blue-200 text-sm mt-2">Portal de Acesso Interno</p>
            </div>
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
                             value="<?= htmlspecialchars($_POST['id'] ?? '') ?>">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-600 mb-2">Chave de Acesso</label>
                        <div class="relative">
                            <input type="password" name="chave" id="campoChave" required autocomplete="current-password"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 pr-12 text-gray-700 focus:outline-none transition"
                                placeholder="••••••••">
                            <button type="button" id="btnOlho"
                                onclick="toggleSenha()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition p-1"
                                tabindex="-1">
                                <!-- olho fechado (senha oculta) -->
                                <svg id="iconOlhoFechado" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                                <!-- olho aberto (senha visível) -->
                                <svg id="iconOlhoAberto" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit"
                        class="w-full py-3 rounded-xl text-white font-bold text-base transition hover:opacity-90"
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
        <script>
    function toggleSenha() {
        const campo = document.getElementById('campoChave');
        const iconFechado = document.getElementById('iconOlhoFechado');
        const iconAberto  = document.getElementById('iconOlhoAberto');
        if (campo.type === 'password') {
            campo.type = 'text';
            iconFechado.style.display = 'none';
            iconAberto.style.display  = 'block';
        } else {
            campo.type = 'password';
            iconFechado.style.display = 'block';
            iconAberto.style.display  = 'none';
        }
    }
    </script>
</body>
</html>