<?php
    // Fuso horário de Moçambique (UTC+2) — deve ser a primeira instrução
    date_default_timezone_set('Africa/Maputo');

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
            'modal_pdf_btn' => 'Descarregar Comprovativo (PDF)',
            'modal_pdf_aviso' => 'Guarde este comprovativo — vai precisar do ticket para saber a hora da sua consulta.',
            'modal_btn' => 'Entendi, obrigado!',
            'ver_consulta' => 'Ver Consulta',
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
            'modal_pdf_btn' => 'Download Receipt (PDF)',
            'modal_pdf_aviso' => 'Keep this receipt — you will need the ticket to check your appointment time.',
            'modal_btn' => 'Got it, thank you!',
            'ver_consulta' => 'Check Appointment',
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

    // Datas geradas no browser via JavaScript (usa o relógio local do dispositivo)
    $datas_disponiveis = []; // preenchido pelo JS

    // Ler notificação de cancelamento via base de dados
    $notificacao = null;
    try {
        require_once __DIR__ . '/db.php';
        $notif_row = db()->query("SELECT * FROM notificacoes ORDER BY id DESC LIMIT 1")->fetch();
        if ($notif_row && $notif_row['ativa']) {
            $notificacao = $notif_row;
        }
    } catch (Exception $e) {
        // BD indisponível — ignora notificação
    }

    // Hora local via cookie (definido pelo JS no browser)
    $hora_local = 0;
    if (isset($_COOKIE['local_date'])) {
        // Usar hora do servidor mas com timezone correcto já definido
        $hora_local = (int)date('H');
    }

    $fora_de_horas = ($hora_local >= 16); // após as 16h não aceitar marcações

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        require_once __DIR__ . '/db.php';

        // Validação server-side: verificar hora limite
        $hora_servidor = (int)date('H');
        $data_escolhida = $_POST['data_consulta'] ?? '';
        $hoje_servidor  = date('Y-m-d');

        // Se a data escolhida for hoje E já passou das 16h → rejeitar
        if ($data_escolhida === $hoje_servidor && $hora_servidor >= 16) {
            $fora_de_horas = true;
        } else {
            $ticketGerado = gerar_ticket();

            db()->prepare("
                INSERT INTO marcacoes (ticket, data, cliente, urgencia, estado, medico, processo, criado_em)
                VALUES (?, ?, ?, ?, 'Pendente', '', '', NOW())
            ")->execute([
                $ticketGerado,
                $data_escolhida,
                $_POST['cliente_novo'],
                $_POST['urgencia'],
            ]);

            $mostrarModal = true;
        }
    }
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" style="scroll-behavior: smooth;">
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
                            url('https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=1600&q=80') center/cover no-repeat;
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
        <script src="js/localdate.js"></script>
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
            <div class="flex items-center gap-5">
                <a href="#servicos" class="text-white/80 hover:text-cyan-400 text-sm font-medium transition hidden md:block"><?= $t['servicos'] ?></a>
                <a href="#sobre" class="text-white/80 hover:text-cyan-400 text-sm font-medium transition hidden md:block"><?= $t['sobre'] ?></a>
                <a href="consulta.php?lang=<?= $lang ?>" class="text-white/80 hover:text-cyan-400 text-sm font-medium transition hidden md:block"><?= $t['ver_consulta'] ?></a>
                <a href="#marcar" class="btn-primary text-white px-5 py-2 rounded-full text-sm font-semibold"><?= $t['btn_marcar'] ?></a>
                <a href="login.php" class="flex items-center gap-1.5 border border-white/50 hover:border-cyan-400 hover:text-cyan-400 text-white px-4 py-2 rounded-full text-sm font-semibold transition">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    <span class="hidden sm:inline"><?= $t['acesso_staff'] ?></span>
                </a>
            </div>
        </div>
    </nav>

    <!-- SELECTOR DE IDIOMA — fora da navbar, flutuante e sempre visível -->
    <a href="?lang=<?= $outro_lang ?>" id="lang-float"
        class="fixed z-30 bottom-5 right-5 bg-white shadow-lg hover:shadow-xl text-xs font-bold px-4 py-2.5 rounded-full flex items-center gap-2 transition"
        style="color:#0a1f44;">
        <svg width="14" height="14" fill="none" stroke="#0a1f44" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
        <?= $outro_lang_label ?>
    </a>

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
    <section id="servicos" class="py-24 px-6" style="background:#f8faff;">
        <div class="max-w-6xl mx-auto">

            <!-- Header -->
            <div class="text-center mb-16">
                <span style="background:rgba(21,101,192,0.08);color:#1565c0;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:6px 18px;border-radius:20px;display:inline-block;margin-bottom:14px;">
                    <?= $lang=='pt' ? 'O que oferecemos' : 'What we offer' ?>
                </span>
                <h2 class="text-4xl font-bold mb-4" style="color:#0a1f44"><?= $t['servicos'] ?></h2>
                <p class="text-gray-500 text-lg max-w-xl mx-auto"><?= $lang=='pt' ? 'Cuidados completos para toda a família, com tecnologia moderna e atenção humana.' : 'Complete care for the whole family, with modern technology and human attention.' ?></p>
            </div>

            <?php
            $servicos_data = [
                [
                    'titulo'       => $t['serv1_t'],
                    'desc'         => $t['serv1_d'],
                    'detalhe'      => $lang=='pt' ? 'Registo da actividade eléctrica do coração em repouso. Essencial para rastrear arritmias, isquemia e outras condições cardíacas.' : 'Records the electrical activity of the heart at rest. Essential for detecting arrhythmias, ischaemia and other cardiac conditions.',
                    'disponivel'   => $lang=='pt' ? 'Seg – Sex' : 'Mon – Fri',
                    'duracao'      => '20 min',
                    'cor'          => '#e8f0fe',
                    'cor_icon'     => '#1565c0',
                    'svg'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12h3l2-7 4 14 3-7h6"/>',
                ],
                [
                    'titulo'       => $t['serv2_t'],
                    'desc'         => $t['serv2_d'],
                    'detalhe'      => $lang=='pt' ? 'Ecografia abdominal, pélvica e obstétrica com equipamento de alta resolução. Resultados entregues na hora.' : 'Abdominal, pelvic and obstetric ultrasound with high-resolution equipment. Results delivered immediately.',
                    'disponivel'   => $lang=='pt' ? 'Seg – Sex' : 'Mon – Fri',
                    'duracao'      => '30 min',
                    'cor'          => '#e0f7fa',
                    'cor_icon'     => '#00838f',
                    'svg'          => '<circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 8v6M8 11h6"/>',
                ],
                [
                    'titulo'       => $t['serv3_t'],
                    'desc'         => $t['serv3_d'],
                    'detalhe'      => $lang=='pt' ? 'Avaliação clínica geral, acompanhamento de doenças crónicas, emissão de receitas e certificados médicos.' : 'General clinical assessment, chronic disease follow-up, prescriptions and medical certificates.',
                    'disponivel'   => $lang=='pt' ? 'Seg – Sáb' : 'Mon – Sat',
                    'duracao'      => '30 min',
                    'cor'          => '#f3e8ff',
                    'cor_icon'     => '#7c3aed',
                    'svg'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                ],
                [
                    'titulo'       => $t['serv4_t'],
                    'desc'         => $t['serv4_d'],
                    'detalhe'      => $lang=='pt' ? 'Consultas pediátricas desde o recém-nascido até à adolescência. Vacinação, desenvolvimento e acompanhamento nutricional.' : 'Paediatric consultations from newborns to adolescents. Vaccination, development and nutritional follow-up.',
                    'disponivel'   => $lang=='pt' ? 'Ter, Qui, Sáb' : 'Tue, Thu, Sat',
                    'duracao'      => '30 min',
                    'cor'          => '#fef3c7',
                    'cor_icon'     => '#d97706',
                    'svg'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/>',
                ],
                [
                    'titulo'       => $t['serv5_t'],
                    'desc'         => $t['serv5_d'],
                    'detalhe'      => $lang=='pt' ? 'Triagem e atendimento prioritário fora do horário normal. Disponível das 7h–9h e após as 16h, com taxa adicional.' : 'Triage and priority care outside normal hours. Available 7–9 AM and after 4 PM, with additional fee.',
                    'disponivel'   => $lang=='pt' ? 'Todos os dias' : 'Every day',
                    'duracao'      => $lang=='pt' ? 'Imediato' : 'Immediate',
                    'cor'          => '#fee2e2',
                    'cor_icon'     => '#dc2626',
                    'svg'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                ],
                [
                    'titulo'       => $t['serv6_t'],
                    'desc'         => $t['serv6_d'],
                    'detalhe'      => $lang=='pt' ? 'Hemograma completo, glicémia, perfil lipídico, função renal e hepática, entre outros. Resultados em 1–2 horas.' : 'Full blood count, blood glucose, lipid profile, renal and hepatic function, and more. Results in 1–2 hours.',
                    'disponivel'   => $lang=='pt' ? 'Seg – Sáb, 7h–12h' : 'Mon – Sat, 7–12 AM',
                    'duracao'      => '1–2 h',
                    'cor'          => '#dcfce7',
                    'cor_icon'     => '#16a34a',
                    'svg'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
                ],
            ];
            ?>

            <!-- Grid de serviços -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;" class="serv-grid">
            <?php foreach ($servicos_data as $i => $sv): ?>
                <div class="serv-card card-hover" style="
                    background:#ffffff;
                    border:1px solid #e8edf5;
                    border-radius:20px;
                    overflow:hidden;
                    display:flex;flex-direction:column;
                    box-shadow:0 2px 12px rgba(10,31,68,0.06);
                    transition:transform .25s, box-shadow .25s;
                ">
                    <!-- Barra de cor no topo -->
                    <div style="height:4px;background:<?= $sv['cor_icon'] ?>;"></div>

                    <div style="padding:28px 28px 20px;">
                        <!-- Ícone + Título -->
                        <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:16px;">
                            <div style="
                                width:48px;height:48px;border-radius:14px;
                                background:<?= $sv['cor'] ?>;
                                display:flex;align-items:center;justify-content:center;
                                flex-shrink:0;
                            ">
                                <svg width="22" height="22" fill="none" stroke="<?= $sv['cor_icon'] ?>" stroke-width="2" viewBox="0 0 24 24">
                                    <?= $sv['svg'] ?>
                                </svg>
                            </div>
                            <div>
                                <h3 style="font-size:17px;font-weight:700;color:#0a1f44;margin:0 0 4px;"><?= $sv['titulo'] ?></h3>
                                <p style="font-size:13px;color:#6b7a99;margin:0;"><?= $sv['desc'] ?></p>
                            </div>
                        </div>

                        <!-- Detalhe -->
                        <p style="font-size:13px;color:#4a5568;line-height:1.65;margin-bottom:20px;padding-top:12px;border-top:1px solid #f0f4f8;">
                            <?= $sv['detalhe'] ?>
                        </p>

                        <!-- Tags de disponibilidade + duração -->
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                            <span style="
                                display:inline-flex;align-items:center;gap:5px;
                                background:<?= $sv['cor'] ?>;color:<?= $sv['cor_icon'] ?>;
                                font-size:11.5px;font-weight:600;padding:4px 11px;border-radius:20px;
                            ">
                                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                <?= $sv['disponivel'] ?>
                            </span>
                            <span style="
                                display:inline-flex;align-items:center;gap:5px;
                                background:#f0f4f8;color:#4a5568;
                                font-size:11.5px;font-weight:600;padding:4px 11px;border-radius:20px;
                            ">
                                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/></svg>
                                <?= $sv['duracao'] ?>
                            </span>
                        </div>
                    </div>

                    <!-- CTA -->
                    <div style="margin-top:auto;padding:0 28px 24px;">
                        <a href="#marcar" style="
                            display:block;text-align:center;
                            background:<?= $sv['cor'] ?>;color:<?= $sv['cor_icon'] ?>;
                            font-size:13px;font-weight:700;
                            padding:10px;border-radius:10px;
                            text-decoration:none;
                            transition:filter .2s;
                        " onmouseover="this.style.filter='brightness(0.93)'" onmouseout="this.style.filter='none'">
                            <?= $lang=='pt' ? 'Marcar consulta →' : 'Book now →' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

        </div>
    </section>

    <style>
        @media(max-width:900px){ .serv-grid{ grid-template-columns:repeat(2,1fr) !important; } }
        @media(max-width:580px){ .serv-grid{ grid-template-columns:1fr !important; } }
    </style>

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
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl overflow-hidden shadow-xl">
                    <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=600&q=80"
                        alt="Médico" class="w-full h-52 object-cover object-top">
                </div>
                <div class="rounded-2xl overflow-hidden shadow-xl mt-6">
                    <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=600&q=80"
                        alt="Equipa" class="w-full h-52 object-cover object-top">
                </div>
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
                        <select id="sel-data" name="data_consulta" required class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-gray-700 focus:outline-none transition">
                            <option value=""><?= $lang == 'pt' ? '— Escolha uma data —' : '— Choose a date —' ?></option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">
                            <?= $lang == 'pt' ? 'Apenas dias úteis. Marcações até às 16h.' : 'Weekdays only. Bookings accepted until 4 PM.' ?>
                        </p>
                        <!-- Aviso após as 16h — mostrado pelo JS -->
                        <div id="aviso-hora" style="display:none" class="mt-2 bg-amber-50 border border-amber-300 text-amber-700 rounded-xl px-4 py-3 text-sm font-medium">
                            <?= $lang == 'pt'
                                ? 'O horário de marcações terminou às 16h. A primeira data disponível é amanhã.'
                                : 'Booking hours ended at 4 PM. The first available date is tomorrow.' ?>
                        </div>
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
            <a href="recibo.php?ticket=<?= urlencode($ticketGerado) ?>" target="_blank"
                class="btn-primary w-full text-white py-3 rounded-full font-bold mb-3 inline-flex items-center justify-center gap-2">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                <?= $t['modal_pdf_btn'] ?>
            </a>
            <p class="text-xs text-gray-400 mb-5"><?= $t['modal_pdf_aviso'] ?></p>
            <button onclick="document.getElementById('modalSucesso').remove()"
                class="text-gray-500 hover:text-gray-700 px-10 py-2 rounded-full font-semibold text-sm transition">
                <?= $t['modal_btn'] ?>
            </button>
        </div>
    </div>
    <script>
        // Descarrega automaticamente o comprovativo em PDF (sem abrir nova aba/popup)
        (function() {
            try {
                var a = document.createElement('a');
                a.href = 'recibo.php?ticket=<?= rawurlencode($ticketGerado) ?>';
                a.download = 'comprovativo_<?= rawurlencode($ticketGerado) ?>.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            } catch (e) { /* ignora — o botão manual fica sempre disponível */ }
        })();
    </script>
    <?php endif; ?>


    <script>
    // Gera datas usando o relógio LOCAL do browser — independente do servidor
    (function() {
        const lang    = <?= json_encode($lang) ?>;
        const isPt    = (lang === 'pt');
        const dias_pt = ['','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
        const dias_en = ['','Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        const sel     = document.getElementById('sel-data');
        const aviso   = document.getElementById('aviso-hora');
        if (!sel) return;

        const agora      = new Date();
        const hora        = agora.getHours(); // hora local do browser
        const HORA_LIMITE = 16;               // após as 16h não se aceitam marcações para hoje
        const passouLimite = hora >= HORA_LIMITE;

        // Se já passou das 16h, começa a partir de amanhã; caso contrário começa hoje
        // Hoje só aparece se for dia útil (Seg-Sex) e antes das 16h
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);

        // Mostrar aviso se passou das 16h
        if (passouLimite && aviso) {
            aviso.style.display = 'block';
        }

        let adicionados = 0;
        let offset      = passouLimite ? 1 : 0; // 0 = hoje, 1 = amanhã

        while (adicionados < 15) {
            const d = new Date(hoje);
            d.setDate(hoje.getDate() + offset);
            const diaSemana = d.getDay(); // 0=Dom, 6=Sáb

            if (diaSemana !== 0 && diaSemana !== 6) { // só dias úteis
                const yyyy = d.getFullYear();
                const mm   = String(d.getMonth() + 1).padStart(2, '0');
                const dd   = String(d.getDate()).padStart(2, '0');
                const val  = `${yyyy}-${mm}-${dd}`;

                const nIdx   = diaSemana === 0 ? 7 : diaSemana;
                const nomeDia = isPt ? dias_pt[nIdx] : dias_en[nIdx];

                // Etiqueta: se for hoje, indicar
                let label = `${dd}/${mm}/${yyyy} (${nomeDia})`;
                if (offset === 0) {
                    label += isPt ? ' — Hoje' : ' — Today';
                }

                const opt = document.createElement('option');
                opt.value = val;
                opt.textContent = label;
                sel.appendChild(opt);
                adicionados++;
            }
            offset++;
        }
    })();
    </script>
    </body>
</html>