<?php
require_once __DIR__ . '/config.php';

/* ── CORS ── */
$origin = CORS_ORIGIN === '*' ? '*' : CORS_ORIGIN;
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

/* ── Aceita POST form-encoded ou JSON ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
    exit;
}

$body = file_get_contents('php://input');
if (!empty($body) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $dados = json_decode($body, true) ?? [];
} else {
    $dados = $_POST;
}

$nome        = trim($dados['nome']        ?? '');
$telefone    = trim($dados['telefone']    ?? '');
$empreend    = trim($dados['empreendimento'] ?? 'Alameda dos Ipês');

if ($nome === '' || $telefone === '') {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Nome e telefone são obrigatórios']);
    exit;
}

/* ── Sanitize ── */
$nome     = mb_substr(strip_tags($nome), 0, 255, 'UTF-8');
$telefone = mb_substr(preg_replace('/[^0-9\s\(\)\-\+]/', '', $telefone), 0, 25);
$empreend = mb_substr(strip_tags($empreend), 0, 150, 'UTF-8');
$ip       = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ua       = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300, 'UTF-8');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $stmt = $pdo->prepare("
        INSERT INTO leads (nome, telefone, empreendimento, ip, user_agent)
        VALUES (:nome, :telefone, :empreend, :ip, :ua)
    ");
    $stmt->execute([
        ':nome'     => $nome,
        ':telefone' => $telefone,
        ':empreend' => $empreend,
        ':ip'       => $ip,
        ':ua'       => $ua,
    ]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Cadastro realizado!']);

} catch (PDOException $e) {
    error_log('[ARRUDA-LEADS] Erro DB: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro interno. Tente novamente.']);
}
