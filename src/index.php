<?php
session_start();
$mostrarModal = false;
$ticketGerado = "";
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'pt';

$txt = [
    'pt' => [
        'title' => 'Vida Centro de Saúde',
        'tagline' => 'Excelência médica ao seu serviço.',
        'desc' => 'Consultas de qualidade, atendimento humano e tecnologia moderna para cuidar da sua saúde.',
        'btn_marcar' => 'Marcar Consulta',
        'servicos' => 'Os Nossos Serviços',
        'serv1_t' => 'ECG', 'serv1_d' => 'Eletrocardiogramas detalhados com tecnologia de ponta.',
        'serv2_t' => 'Ecografias', 'serv2_d' => 'Imagem de alta precisão para diagnóstico rigoroso.',
        'serv3_t' => 'Medicina Geral', 'serv3_d' => 'Consultas de rotina para toda a família.',
        'serv4_t' => 'Pediatria', 'serv4_d' => 'Cuidados especializados para a saúde infantil.',
        'serv5_t' => 'Urgências', 'serv5_d' => 'Atendimento urgente das 7h–9h e após 16h.',
        'serv6_t' => 'Análises', 'serv6_d' => 'Exames laboratoriais rápidos e precisos.',
        'agendar' => 'Agendar Consulta',
        'agendar_sub' => 'Sem necessidade de dados pessoais. A sua senha é a sua referência.',
        'cliente_label' => 'É paciente novo?',
        'opt_novo' => 'Sim, sou paciente novo',
        'opt_antigo' => 'Não, já sou paciente',
        'data_label' => 'Data da Consulta',
        'tipo_label' => 'Tipo de Consulta',
        'normal' => 'Normal',
        'urgente' => 'Urgente (Taxa adicional · das 7–9h ou após 16h)',
        'btn_submit' => 'Solicitar Agendamento',
        'modal_titulo' => 'Consulta Agendada!',
        'modal_msg' => 'Guarde o seu codigo que vem na senha. Apresente-o na receção.',
        'modal_btn' => 'Entendi, obrigado!',
        'acesso_staff' => 'Acesso Interno',
        'footer_direitos' => '© 2026 Vida Centro de Saúde. Todos os direitos reservados.',
        'sobre' => 'Sobre Nós',
        'sobre_desc' => 'Com mais de uma década de experiência, o Vida Centro de Saúde é referência em atendimento médico de qualidade, aliando tecnologia de ponta ao cuidado humano e personalizado.',
        'contato' => 'Contacto',
        'notif_cancel' => '',
    ],
    'en' => [
        'title' => 'Vida Health Centre',
        'tagline' => 'Medical excellence at your service.',
        'desc' => 'Quality consultations, caring staff and modern technology to look after your health.',
        'btn_marcar' => 'Book Appointment',
        'servicos' => 'Our Services',
        'serv1_t' => 'ECG', 'serv1_d' => 'Detailed electrocardiograms with cutting-edge technology.',
        'serv2_t' => 'Ultrasound', 'serv2_d' => 'High-precision imaging for accurate diagnosis.',
        'serv3_t' => 'General Medicine', 'serv3_d' => 'Routine consultations for the whole family.',
        'serv4_t' => 'Paediatrics', 'serv4_d' => 'Specialised care for children\'s health.',
        'serv5_t' => 'Urgent Care', 'serv5_d' => 'Urgent consultations: 7–9 AM and after 4 PM.',
        'serv6_t' => 'Lab Tests', 'serv6_d' => 'Fast and accurate laboratory examinations.',
        'agendar' => 'Book Appointment',
        'agendar_sub' => 'No personal data required. Your ticket is your reference.',
        'cliente_label' => 'Are you a new patient?',
        'opt_novo' => 'Yes, I am a new patient',
        'opt_antigo' => 'No, I am an existing patient',
        'data_label' => 'Appointment Date',
        'tipo_label' => 'Appointment Type',
        'normal' => 'Normal',
        'urgente' => 'Urgent (Additional fee · 7–9 AM or after 4 PM)',
        'btn_submit' => 'Request Appointment',
        'modal_titulo' => 'Appointment Booked!',
        'modal_msg' => 'Please save your ticket number and present it at reception.',
        'modal_btn' => 'Got it, thank you!',
        'acesso_staff' => 'Staff Access',
        'footer_direitos' => '© 2026 Vida Health Centre. All rights reserved.',
        'sobre' => 'About Us',
        'sobre_desc' => 'With over a decade of experience, Vida Health Centre is a reference in quality medical care, combining cutting-edge technology with personalised human attention.',
        'contato' => 'Contact',
        'notif_cancel' => '',
    ]
];

$t = $txt[$lang];
$outro_lang = $lang == 'pt' ? 'en' : 'pt';
$outro_lang_label = $lang == 'pt' ? 'English' : 'Português';

// Gerar datas disponíveis (15 dias a partir de amanhã)
$datas_disponiveis = [];
for ($i = 1; $i <= 15; $i++) {
    $data = date('Y-m-d', strtotime("+$i days"));
    $dia_semana = date('N', strtotime($data)); // 6=sab, 7=dom
    if ($dia_semana < 6) { // Sem fins de semana
        $datas_disponiveis[] = $data;
    }
}

// Ler notificação de cancelamento
$notif_path = 'data/notificacao.json';
$notificacao = null;
if (file_exists($notif_path)) {
    $notif_data = json_decode(file_get_contents($notif_path), true);
    if ($notif_data && $notif_data['ativa']) {
        $notificacao = $notif_data;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $caminho = 'data/marcacao.json';
    $marcacoes = json_decode(file_get_contents($caminho), true) ?? [];
    $ticketGerado = "V-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

    $nova = [
        "ticket"  => $ticketGerado,
        "data"    => $_POST['data_consulta'],
        "cliente" => $_POST['cliente_novo'],
        "urgencia"=> $_POST['urgencia'],
        "estado"  => "Pendente",
        "medico"  => "",
        "processo"=> "",
        "criado_em" => date('Y-m-d H:i:s')
    ];

    $marcacoes[] = $nova;
    file_put_contents($caminho, json_encode($marcacoes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $mostrarModal = true;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0a1f44;
            --blue: #1565c0;
            --accent: #00b4d8;
            --light: #f0f7ff;
        }
        * { font-family: 'Inter', sans-serif; }
        h1, h2, .brand { font-family: 'Playfair Display', serif; }
        .hero-bg {
            background: linear-gradient(135deg, rgba(10,31,68,0.92) 0%, rgba(21,101,192,0.85) 100%),
                        url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1600&q=80') center/cover no-repeat;
        }
        .card-hover { transition: transform 0.25s, box-shadow 0.25s; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(10,31,68,0.15); }
        .btn-primary { background: linear-gradient(135deg, var(--blue), var(--accent)); transition: opacity 0.2s, transform 0.2s; }
        .btn-primary:hover { opacity: 0.9; transform: scale(1.02); }
        .nav-blur { backdrop-filter: blur(16px); background: rgba(10,31,68,0.95); }
        select option { color: #0a1f44; }
        .form-input:focus { border-color: #1565c0; box-shadow: 0 0 0 3px rgba(21,101,192,0.15); }
        @keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .fade-up { animation: fadeUp 0.5s ease forwards; }
        @keyframes pulse-ring { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.1);opacity:0.7} }
        .notif-pulse { animation: pulse-ring 2s infinite; }
    </style>
</head>
<body class="bg-gray-50">

<?php if ($notificacao): ?>
<div id="notifBanner" class="bg-amber-500 text-white py-3 px-6 flex items-center justify-between z-50 relative">
    <div class="flex items-center gap-3">
        <span class="text-2xl notif-pulse">⚠️</span>
        <strong><?= htmlspecialchars($notificacao['mensagem_'.$lang] ?? $notificacao['mensagem_pt']) ?></strong>
    </div>
    <button onclick="document.getElementById('notifBanner').remove()" class="text-white opacity-70 hover:opacity-100 text-xl font-bold">✕</button>
</div>
<?php endif; ?>

<!-- NAV -->
<nav class="nav-blur fixed w-full top-0 z-40 <?= $notificacao ? 'mt-0' : '' ?>">
    <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
        <a href="index.php?lang=<?= $lang ?>" class="brand text-white text-2xl tracking-wide">
            <span class="text-cyan-400">Vida</span> Centro de Saúde
        </a>
        <div class="flex items-center gap-6">
            <a href="#servicos" class="text-white/80 hover:text-cyan-400 text-sm font-medium transition hidden md:block"><?= $t['servicos'] ?></a>
            <a href="#sobre" class="text-white/80 hover:text-cyan-400 text-sm font-medium transition hidden md:block"><?= $t['sobre'] ?></a>
            <a href="#marcar" class="btn-primary text-white px-5 py-2 rounded-full text-sm font-semibold"><?= $t['btn_marcar'] ?></a>
            <a href="?lang=<?= $outro_lang ?>" class="text-white/60 hover:text-white text-xs border border-white/20 px-3 py-1 rounded-full transition"><?= $outro_lang_label ?></a>
        </div>
    </div>
</nav>

<!-- HERO -->
<header class="hero-bg min-h-screen flex items-center justify-center text-center text-white pt-20">
    <div class="max-w-4xl px-6 fade-up">
        <div class="inline-block bg-cyan-400/20 border border-cyan-400/40 text-cyan-300 text-sm px-4 py-1 rounded-full mb-6">
            <?= $lang == 'pt' ? 'Saúde de confiança · Maputo' : 'Trusted Healthcare · Maputo' ?>
        </div>
        <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
            <?= $lang == 'pt' ? 'A sua saúde,<br><span class="text-cyan-400">a nossa missão</span>' : 'Your health,<br><span class="text-cyan-400">our mission</span>' ?>
        </h1>
        <p class="text-xl text-white/75 max-w-2xl mx-auto mb-10"><?= $t['desc'] ?></p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="#marcar" class="btn-primary px-8 py-4 rounded-full font-bold text-lg"><?= $t['btn_marcar'] ?></a>
            <a href="#servicos" class="border border-white/30 hover:border-white px-8 py-4 rounded-full font-semibold text-lg transition">
                <?= $lang == 'pt' ? 'Ver Serviços' : 'View Services' ?>
            </a>
        </div>
    </div>
</header>

<!-- STATS BAR -->
<div class="bg-white border-b border-gray-100 py-8">
    <div class="max-w-5xl mx-auto px-6 grid grid-cols-3 gap-6 text-center">
        <div>
            <div class="text-3xl font-bold text-blue-700">10+</div>
            <div class="text-sm text-gray-500 mt-1"><?= $lang == 'pt' ? 'Anos de Experiência' : 'Years Experience' ?></div>
        </div>
        <div>
            <div class="text-3xl font-bold text-blue-700">5.000+</div>
            <div class="text-sm text-gray-500 mt-1"><?= $lang == 'pt' ? 'Pacientes Atendidos' : 'Patients Served' ?></div>
        </div>
        <div>
            <div class="text-3xl font-bold text-blue-700">6</div>
            <div class="text-sm text-gray-500 mt-1"><?= $lang == 'pt' ? 'Especialidades' : 'Specialties' ?></div>
        </div>
    </div>
</div>

<!-- SERVIÇOS -->
<section id="servicos" class="py-24 px-6">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-navy-900 mb-4" style="color:#0a1f44"><?= $t['servicos'] ?></h2>
            <p class="text-gray-500 text-lg"><?= $lang == 'pt' ? 'Cuidados completos para toda a família' : 'Complete care for the whole family' ?></p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $icons = ['','','','','',''];
            $servs = [
                [$t['serv1_t'],$t['serv1_d']],[$t['serv2_t'],$t['serv2_d']],
                [$t['serv3_t'],$t['serv3_d']],[$t['serv4_t'],$t['serv4_d']],
                [$t['serv5_t'],$t['serv5_d']],[$t['serv6_t'],$t['serv6_d']]
            ];
            foreach($servs as $i => $s): ?>
            <div class="card-hover bg-white rounded-2xl p-8 border border-gray-100 shadow-sm">
                <div class="text-4xl mb-4"><?= $icons[$i] ?></div>
                <h3 class="text-xl font-bold mb-2" style="color:#0a1f44"><?= $s[0] ?></h3>
                <p class="text-gray-500 text-sm leading-relaxed"><?= $s[1] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SOBRE NÓS -->
<section id="sobre" class="py-20 px-6" style="background:var(--light)">
    <div class="max-w-5xl mx-auto grid md:grid-cols-2 gap-12 items-center">
        <div>
            <h2 class="text-4xl font-bold mb-6" style="color:#0a1f44"><?= $t['sobre'] ?></h2>
            <p class="text-gray-600 text-lg leading-relaxed mb-6"><?= $t['sobre_desc'] ?></p>
            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-3 text-gray-700">
                    <span class="text-cyan-500 text-xl">✓</span>
                    <?= $lang == 'pt' ? 'Equipa médica certificada e experiente' : 'Certified and experienced medical team' ?>
                </div>
                <div class="flex items-center gap-3 text-gray-700">
                    <span class="text-cyan-500 text-xl">✓</span>
                    <?= $lang == 'pt' ? 'Equipamentos modernos de diagnóstico' : 'Modern diagnostic equipment' ?>
                </div>
                <div class="flex items-center gap-3 text-gray-700">
                    <span class="text-cyan-500 text-xl">✓</span>
                    <?= $lang == 'pt' ? 'Atendimento humanizado e personalizado' : 'Humanised and personalised care' ?>
                </div>
            </div>
        </div>
        <div class="rounded-3xl overflow-hidden shadow-2xl">
            <img src="https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=800&q=80"
                 alt="Clínica" class="w-full h-72 object-cover">
        </div>
    </div>
</section>

<!-- MARCAÇÃO -->
<section id="marcar" class="py-24 px-6" style="background: linear-gradient(135deg, #0a1f44 0%, #1565c0 100%)">
    <div class="max-w-lg mx-auto">
        <div class="text-center mb-10">
            <h2 class="text-4xl font-bold text-white mb-3"><?= $t['agendar'] ?></h2>
            <p class="text-blue-200"><?= $t['agendar_sub'] ?></p>
        </div>
        <div class="bg-white rounded-3xl p-8 shadow-2xl">
            <form method="POST" action="#marcar">
                <!-- Cliente novo/antigo -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2"><?= $t['cliente_label'] ?></label>
                    <select name="cliente_novo" required class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-gray-700 focus:outline-none transition">
                        <option value="novo"><?= $t['opt_novo'] ?></option>
                        <option value="antigo"><?= $t['opt_antigo'] ?></option>
                    </select>
                </div>

                <!-- Data -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2"><?= $t['data_label'] ?></label>
                    <select name="data_consulta" required class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-gray-700 focus:outline-none transition">
                        <option value=""><?= $lang == 'pt' ? '— Escolha uma data —' : '— Choose a date —' ?></option>
                        <?php foreach($datas_disponiveis as $d):
                            $label_pt = date('d/m/Y', strtotime($d)) . ' (' . ['','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'][date('N',strtotime($d))] . ')';
                            $label_en = date('d/m/Y', strtotime($d)) . ' (' . ['','Mon','Tue','Wed','Thu','Fri','Sat','Sun'][date('N',strtotime($d))] . ')';
                        ?>
                            <option value="<?= $d ?>"><?= $lang == 'pt' ? $label_pt : $label_en ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        <?= $lang == 'pt' ? ' Apenas dias úteis disponíveis (próximos 15 dias)' : ' Weekdays only (next 15 days)' ?>
                    </p>
                </div>

                <!-- Tipo consulta -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2"><?= $t['tipo_label'] ?></label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="urgencia" value="normal" class="hidden peer" checked>
                            <div class="peer-checked:border-blue-600 peer-checked:bg-blue-50 border-2 border-gray-200 rounded-xl p-4 text-center transition">
                                <div class="text-2xl mb-1"></div>
                                <div class="font-semibold text-sm text-gray-700"><?= $t['normal'] ?></div>
                                <div class="text-xs text-gray-400"><?= $lang == 'pt' ? 'Taxa normal' : 'Standard fee' ?></div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="urgencia" value="urgente" class="hidden peer">
                            <div class="peer-checked:border-red-500 peer-checked:bg-red-50 border-2 border-gray-200 rounded-xl p-4 text-center transition">
                                <div class="text-2xl mb-1"></div>
                                <div class="font-semibold text-sm text-gray-700"><?= $lang == 'pt' ? 'Urgente' : 'Urgent' ?></div>
                                <div class="text-xs text-gray-400"><?= $lang == 'pt' ? '7–9h ou após 16h' : '7–9 AM or after 4 PM' ?></div>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full py-4 rounded-xl text-white font-bold text-lg">
                    <?= $t['btn_submit'] ?>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- CONTACTO -->
<section id="contacto" class="py-16 px-6 bg-white">
    <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-3xl font-bold mb-8" style="color:#0a1f44"><?= $t['contato'] ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-6 bg-gray-50 rounded-2xl">
                <div class="text-3xl mb-3">📍</div>
                <div class="font-semibold text-gray-800">Maputo, Moçambique</div>
                <div class="text-sm text-gray-500 mt-1"><?= $lang == 'pt' ? 'Av. Principal, nº 123' : '123 Main Avenue' ?></div>
            </div>
            <div class="p-6 bg-gray-50 rounded-2xl">
                <div class="text-3xl mb-3">📞</div>
                <div class="font-semibold text-gray-800">+258 84 000 0000</div>
                <div class="text-sm text-gray-500 mt-1"><?= $lang == 'pt' ? 'Seg–Sex, 7h–18h' : 'Mon–Fri, 7 AM–6 PM' ?></div>
            </div>
            <div class="p-6 bg-gray-50 rounded-2xl">
                <div class="text-3xl mb-3">✉️</div>
                <div class="font-semibold text-gray-800">geral@vidacentro.mz</div>
                <div class="text-sm text-gray-500 mt-1"><?= $lang == 'pt' ? 'Respondemos em 24h' : 'We reply within 24h' ?></div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="py-8 px-6 text-center" style="background:#0a1f44">
    <p class="text-white/50 text-sm"><?= $t['footer_direitos'] ?></p>
    <a href="login.php" class="text-white/20 hover:text-white/50 text-xs mt-2 inline-block transition"><?= $t['acesso_staff'] ?></a>
</footer>

<!-- MODAL DE SUCESSO -->
<?php if ($mostrarModal): ?>
<div id="modalSucesso" class="fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(10,31,68,0.85)">
    <div class="bg-white rounded-3xl p-10 max-w-md w-11/12 text-center shadow-2xl fade-up">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
            <span class="text-4xl">✅</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= $t['modal_titulo'] ?></h2>
        <p class="text-gray-500 mb-6"><?= $t['modal_msg'] ?></p>
        <div class="bg-blue-50 border-2 border-dashed border-blue-300 rounded-2xl py-5 px-8 mb-6">
            <div class="text-xs text-gray-400 uppercase tracking-widest mb-1">Ticket</div>
            <div class="text-4xl font-bold tracking-widest" style="color:#0a1f44"><?= $ticketGerado ?></div>
        </div>
        <button onclick="document.getElementById('modalSucesso').remove()"
            class="btn-primary text-white px-10 py-3 rounded-full font-bold">
            <?= $t['modal_btn'] ?>
        </button>
    </div>
</div>
<?php endif; ?>

</body>
</html>
