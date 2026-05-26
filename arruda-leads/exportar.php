<?php
session_start();
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['arruda_auth'])) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $leads = $pdo->query("SELECT id, nome, telefone, empreendimento, status, data_cadastro FROM leads ORDER BY data_cadastro DESC")->fetchAll();

} catch (PDOException $e) {
    die('Erro ao exportar: ' . $e->getMessage());
}

$nome_arquivo = 'arruda-leads-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel

fputcsv($out, ['ID', 'Nome', 'Telefone', 'Empreendimento', 'Status', 'Data de Cadastro'], ';');

foreach ($leads as $l) {
    fputcsv($out, [
        $l['id'],
        $l['nome'],
        $l['telefone'],
        $l['empreendimento'],
        ucfirst($l['status']),
        date('d/m/Y H:i', strtotime($l['data_cadastro'])),
    ], ';');
}

fclose($out);
