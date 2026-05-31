<?php
/**
 * db.php — Ligação PDO + helpers globais
 * Incluir em todos os ficheiros com: require_once __DIR__ . '/db.php';
 */

// ── FUSO HORÁRIO (Moçambique = UTC+2) ────────────────────────────────────────
// Garante que date() e strtotime() usam sempre a hora local correcta
date_default_timezone_set('Africa/Maputo');

// ── CONFIGURAÇÃO ─────────────────────────────────────────────────────────────
// Altere apenas estas constantes para o seu ambiente
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'clinica_vida');
define('DB_USER', 'kali');
define('DB_PASS', 'kalilinux');      // <-- altere para a sua senha

// ── MÉDICOS (configuração central) ───────────────────────────────────────────
define('MEDICO_CLINICA', 'Dr. Armando Silva');
$MEDICOS_LISTA    = ['Dr. Armando Silva', 'Dr.ª Luísa Mário', 'Dr. Carlos Nhaca'];
$MEDICOS_EXTERNOS = ['Dr.ª Luísa Mário', 'Dr. Carlos Nhaca'];

// ── LIGAÇÃO PDO (singleton) ───────────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET NAMES 'UTF8'");
        } catch (PDOException $e) {
            http_response_code(500);
            die('<div style="font-family:monospace;color:red;padding:2rem">
                <b>Erro de ligação à base de dados:</b><br>' .
                htmlspecialchars($e->getMessage()) . '</div>');
        }
    }
    return $pdo;
}

// ── HELPERS ───────────────────────────────────────────────────────────────────

/** Verifica se o médico é o médico interno da clínica */
function is_medico_clinica(string $nome): bool {
    return trim($nome) === MEDICO_CLINICA;
}

/** Verifica se o médico é externo (não pertence à clínica) */
function is_medico_externo(string $nome): bool {
    global $MEDICOS_EXTERNOS;
    return in_array(trim($nome), $MEDICOS_EXTERNOS, true);
}

/** Retorna a notificação activa (ou null) */
function get_notificacao(): ?array {
    $row = db()->query("SELECT * FROM notificacoes ORDER BY id DESC LIMIT 1")->fetch();
    return $row ?: null;
}

/** Gera um ticket único no formato V-XXXXXX */
function gerar_ticket(): string {
    do {
        $ticket = 'V-' . strtoupper(bin2hex(random_bytes(3)));
        $existe = db()->prepare("SELECT 1 FROM marcacoes WHERE ticket = ?");
        $existe->execute([$ticket]);
    } while ($existe->fetch());
    return $ticket;
}

/** Gera pool de nºs de processo disponíveis (não usados) */
function pool_processos(int $n = 30): array {
    $usados_stmt = db()->query("SELECT processo FROM marcacoes WHERE processo <> '' AND processo IS NOT NULL");
    $usados = array_column($usados_stmt->fetchAll(), 'processo');
    $pool = [];
    $tries = 0;
    while (count($pool) < $n && $tries < 500) {
        $num = 'P-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        if (!in_array($num, $usados, true) && !in_array($num, $pool, true)) {
            $pool[] = $num;
        }
        $tries++;
    }
    sort($pool);
    return $pool;
}