<?php
session_start();
require_once __DIR__ . '/../config.php';

/* ════════════════════════════════════════
   AUTH
════════════════════════════════════════ */
$erro_login = '';

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_POST['senha'])) {
    if ($_POST['senha'] === ADMIN_SENHA) {
        $_SESSION['arruda_auth'] = true;
        header('Location: index.php');
        exit;
    } else {
        $erro_login = 'Senha incorreta. Tente novamente.';
    }
}

$logado = !empty($_SESSION['arruda_auth']);

/* ════════════════════════════════════════
   AÇÕES (apenas logado)
════════════════════════════════════════ */
if ($logado) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        /* Atualizar status */
        if (!empty($_POST['atualizar_status']) && !empty($_POST['lead_id'])) {
            $s = in_array($_POST['atualizar_status'], ['novo','atendido','descartado'])
                ? $_POST['atualizar_status'] : 'novo';
            $pdo->prepare("UPDATE leads SET status=? WHERE id=?")->execute([$s, (int)$_POST['lead_id']]);
            header('Location: index.php');
            exit;
        }

        /* Excluir lead */
        if (!empty($_POST['excluir_id'])) {
            $pdo->prepare("DELETE FROM leads WHERE id=?")->execute([(int)$_POST['excluir_id']]);
            header('Location: index.php');
            exit;
        }

        /* Stats */
        $total    = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
        $hoje     = $pdo->query("SELECT COUNT(*) FROM leads WHERE DATE(data_cadastro)=CURDATE()")->fetchColumn();
        $semana   = $pdo->query("SELECT COUNT(*) FROM leads WHERE data_cadastro >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $mes      = $pdo->query("SELECT COUNT(*) FROM leads WHERE MONTH(data_cadastro)=MONTH(NOW()) AND YEAR(data_cadastro)=YEAR(NOW())")->fetchColumn();
        $novos    = $pdo->query("SELECT COUNT(*) FROM leads WHERE status='novo'")->fetchColumn();

        /* Leads */
        $busca = trim($_GET['q'] ?? '');
        $status_filtro = $_GET['status'] ?? '';
        $where = [];
        $params = [];

        if ($busca !== '') {
            $where[] = "(nome LIKE ? OR telefone LIKE ?)";
            $params[] = "%$busca%";
            $params[] = "%$busca%";
        }
        if (in_array($status_filtro, ['novo','atendido','descartado'])) {
            $where[] = "status = ?";
            $params[] = $status_filtro;
        }

        $sql = "SELECT * FROM leads" . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY data_cadastro DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $leads = $stmt->fetchAll();

    } catch (PDOException $e) {
        $erro_db = 'Erro de conexão: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Arruda Leads — Painel de Cadastros</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --orange:#FF6A00;--orange-d:#e05e00;--dark:#111111;--dark2:#1a1a1a;
  --gray:#f4f6f9;--border:#e5e7eb;--text:#1f2937;--text2:#6b7280;
  --white:#fff;--radius:10px;--shadow:0 4px 24px rgba(0,0,0,.08);
  --green:#22c55e;--yellow:#f59e0b;--red:#ef4444;
}
html{font-size:15px}
body{font-family:'Plus Jakarta Sans',-apple-system,sans-serif;background:var(--gray);color:var(--text);min-height:100vh}
a{text-decoration:none;color:inherit}
button{font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;border:none;background:none}

/* ── LOGIN ── */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--dark2)}
.login-card{width:360px;background:var(--white);border-radius:20px;padding:44px 36px;box-shadow:0 20px 60px rgba(0,0,0,.35)}
.login-logo{display:flex;align-items:center;gap:12px;margin-bottom:32px}
.login-icon{width:48px;height:48px;background:var(--orange);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.login-brand{font-size:.85rem;font-weight:800;color:var(--dark);text-transform:uppercase;letter-spacing:.04em}
.login-sub{font-size:.68rem;color:var(--text2)}
.login-title{font-size:1.4rem;font-weight:800;color:var(--dark);margin-bottom:6px;letter-spacing:-.02em}
.login-desc{font-size:.82rem;color:var(--text2);margin-bottom:28px}
.login-label{font-size:.75rem;font-weight:600;color:#444;display:block;margin-bottom:6px}
.login-input{width:100%;height:52px;border:1.5px solid var(--border);border-radius:var(--radius);padding:0 16px;font-family:'Plus Jakarta Sans',sans-serif;font-size:.95rem;color:var(--dark);outline:none;transition:border-color .2s,box-shadow .2s}
.login-input:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,106,0,.1)}
.login-btn{width:100%;height:52px;background:var(--orange);color:#fff;font-size:.88rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;border-radius:var(--radius);margin-top:16px;transition:background .2s,transform .2s;box-shadow:0 6px 20px rgba(255,106,0,.4)}
.login-btn:hover{background:var(--orange-d);transform:translateY(-1px)}
.login-erro{margin-top:12px;font-size:.78rem;color:var(--red);font-weight:500;text-align:center}

/* ── LAYOUT ── */
.sidebar{position:fixed;top:0;left:0;bottom:0;width:220px;background:var(--dark2);display:flex;flex-direction:column;padding:28px 20px;z-index:100}
.sb-logo{display:flex;align-items:center;gap:10px;margin-bottom:36px}
.sb-icon{width:42px;height:42px;background:var(--orange);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sb-brand{font-size:.78rem;font-weight:800;color:var(--white);text-transform:uppercase;letter-spacing:.04em;line-height:1.2}
.sb-sub{font-size:.62rem;color:rgba(255,255,255,.35)}
.sb-nav{flex:1;display:flex;flex-direction:column;gap:4px}
.sb-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;font-size:.82rem;font-weight:600;color:rgba(255,255,255,.55);transition:all .18s}
.sb-link.active,.sb-link:hover{background:rgba(255,106,0,.15);color:var(--white)}
.sb-link.active{color:var(--orange)}
.sb-logout{margin-top:auto;width:100%;height:42px;background:rgba(255,255,255,.06);border-radius:8px;color:rgba(255,255,255,.45);font-size:.8rem;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .18s}
.sb-logout:hover{background:rgba(239,68,68,.15);color:#fca5a5}

.main{margin-left:220px;padding:32px 28px;min-height:100vh}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.topbar-title{font-size:1.5rem;font-weight:800;color:var(--dark);letter-spacing:-.02em}
.topbar-sub{font-size:.8rem;color:var(--text2);margin-top:2px}
.topbar-right{display:flex;gap:10px}

/* ── STATS ── */
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:28px}
.stat-card{background:var(--white);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:4px}
.stat-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;margin-bottom:8px}
.stat-icon.orange{background:rgba(255,106,0,.12);color:var(--orange)}
.stat-icon.green{background:rgba(34,197,94,.12);color:var(--green)}
.stat-icon.blue{background:rgba(59,130,246,.12);color:#3b82f6}
.stat-icon.purple{background:rgba(139,92,246,.12);color:#8b5cf6}
.stat-icon.yellow{background:rgba(245,158,11,.12);color:var(--yellow)}
.stat-label{font-size:.68rem;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.08em}
.stat-value{font-size:2rem;font-weight:800;color:var(--dark);letter-spacing:-.03em;line-height:1}

/* ── TABELA ── */
.table-card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.table-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:12px}
.table-title{font-size:1rem;font-weight:700;color:var(--dark)}
.table-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.search-wrap{position:relative}
.search-wrap svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#bbb;pointer-events:none}
.search-input{height:38px;border:1.5px solid var(--border);border-radius:8px;padding:0 14px 0 36px;font-family:'Plus Jakarta Sans',sans-serif;font-size:.82rem;color:var(--dark);outline:none;width:220px;transition:border-color .2s}
.search-input:focus{border-color:var(--orange)}
.filter-select{height:38px;border:1.5px solid var(--border);border-radius:8px;padding:0 28px 0 12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:.82rem;color:var(--dark);outline:none;appearance:none;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat right 10px center;cursor:pointer;transition:border-color .2s}
.filter-select:focus{border-color:var(--orange)}
.btn-export{height:38px;padding:0 16px;background:var(--orange);color:#fff;border-radius:8px;font-size:.8rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;display:flex;align-items:center;gap:7px;transition:background .2s,transform .2s;box-shadow:0 4px 14px rgba(255,106,0,.35)}
.btn-export:hover{background:var(--orange-d);transform:translateY(-1px)}
.btn-export:is(a){text-decoration:none}

table{width:100%;border-collapse:collapse}
thead{background:#fafafa}
th{padding:12px 16px;text-align:left;font-size:.7rem;font-weight:700;color:var(--text2);letter-spacing:.08em;text-transform:uppercase;white-space:nowrap;border-bottom:1px solid var(--border)}
td{padding:13px 16px;font-size:.82rem;color:var(--text);border-bottom:1px solid var(--border);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fafbff}
.td-nome{font-weight:600;color:var(--dark)}
.td-tel{font-weight:500;font-family:monospace;font-size:.85rem;letter-spacing:.03em}
.td-data{color:var(--text2);font-size:.78rem;white-space:nowrap}

.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;letter-spacing:.04em}
.badge.novo{background:rgba(59,130,246,.1);color:#2563eb}
.badge.atendido{background:rgba(34,197,94,.12);color:#16a34a}
.badge.descartado{background:rgba(239,68,68,.1);color:#dc2626}
.badge-dot{width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0}

.action-form{display:inline}
.btn-sm{height:30px;padding:0 10px;border-radius:6px;font-size:.72rem;font-weight:600;display:inline-flex;align-items:center;gap:5px;transition:all .15s}
.btn-atender{background:rgba(34,197,94,.1);color:#16a34a}
.btn-atender:hover{background:rgba(34,197,94,.2)}
.btn-descartar{background:rgba(245,158,11,.1);color:#b45309}
.btn-descartar:hover{background:rgba(245,158,11,.2)}
.btn-excluir{background:rgba(239,68,68,.08);color:#dc2626}
.btn-excluir:hover{background:rgba(239,68,68,.16)}
.actions-wrap{display:flex;gap:5px;flex-wrap:wrap}

.empty-state{text-align:center;padding:60px 20px;color:var(--text2)}
.empty-icon{font-size:2.5rem;margin-bottom:10px}
.empty-msg{font-size:.9rem;font-weight:500}

.erro-box{background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius);padding:16px;color:#dc2626;font-size:.85rem;margin-bottom:20px}

@media(max-width:900px){
  .sidebar{width:60px;padding:20px 10px}
  .sb-brand,.sb-sub,.sb-link span{display:none}
  .sb-link{justify-content:center;padding:12px}
  .main{margin-left:60px;padding:20px 16px}
  .stats-grid{grid-template-columns:repeat(2,1fr)}
}
</style>
</head>
<body>

<?php if (!$logado): ?>
<!-- ════════════ LOGIN ════════════ -->
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div class="login-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div>
        <div class="login-brand">Arruda Leads</div>
        <div class="login-sub">Painel de cadastros</div>
      </div>
    </div>
    <h1 class="login-title">Bem-vindo de volta</h1>
    <p class="login-desc">Digite a senha para acessar o painel de leads.</p>
    <form method="POST">
      <label class="login-label" for="senha">Senha de acesso</label>
      <input class="login-input" type="password" id="senha" name="senha" placeholder="••••••••" autofocus required/>
      <button type="submit" class="login-btn">Entrar no painel</button>
      <?php if ($erro_login): ?>
        <p class="login-erro"><?= htmlspecialchars($erro_login) ?></p>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ════════════ DASHBOARD ════════════ -->
<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div>
      <div class="sb-brand">Arruda<br/>Leads</div>
    </div>
  </div>
  <nav class="sb-nav">
    <a href="index.php" class="sb-link active">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      <span>Dashboard</span>
    </a>
    <a href="exportar.php" class="sb-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      <span>Exportar CSV</span>
    </a>
  </nav>
  <form method="POST">
    <button type="submit" name="logout" value="1" class="sb-logout">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      <span>Sair</span>
    </button>
  </form>
</aside>

<main class="main">
  <div class="topbar">
    <div>
      <div class="topbar-title">Arruda Leads</div>
      <div class="topbar-sub">Todos os cadastros recebidos dos sites Arruda Imóveis</div>
    </div>
    <div class="topbar-right">
      <a href="exportar.php" class="btn-export">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exportar CSV
      </a>
    </div>
  </div>

  <?php if (!empty($erro_db)): ?>
    <div class="erro-box">⚠️ <?= htmlspecialchars($erro_db) ?></div>
  <?php else: ?>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
      <div class="stat-label">Total de Leads</div>
      <div class="stat-value"><?= number_format((int)$total) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
      <div class="stat-label">Hoje</div>
      <div class="stat-value"><?= (int)$hoje ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon purple"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
      <div class="stat-label">Esta Semana</div>
      <div class="stat-value"><?= (int)$semana ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
      <div class="stat-label">Este Mês</div>
      <div class="stat-value"><?= (int)$mes ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon yellow"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
      <div class="stat-label">Novos</div>
      <div class="stat-value"><?= (int)$novos ?></div>
    </div>
  </div>

  <!-- TABELA -->
  <div class="table-card">
    <div class="table-header">
      <div class="table-title">Cadastros <?= count($leads) > 0 ? '(' . count($leads) . ')' : '' ?></div>
      <div class="table-actions">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
          <div class="search-wrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input class="search-input" type="text" name="q" placeholder="Buscar nome ou telefone…" value="<?= htmlspecialchars($busca) ?>"/>
          </div>
          <select class="filter-select" name="status" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            <option value="novo"       <?= $status_filtro==='novo'       ? 'selected' : '' ?>>Novos</option>
            <option value="atendido"   <?= $status_filtro==='atendido'   ? 'selected' : '' ?>>Atendidos</option>
            <option value="descartado" <?= $status_filtro==='descartado' ? 'selected' : '' ?>>Descartados</option>
          </select>
          <button type="submit" class="btn-sm btn-atender">Buscar</button>
          <?php if ($busca || $status_filtro): ?>
            <a href="index.php" class="btn-sm btn-excluir">✕ Limpar</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <?php if (empty($leads)): ?>
      <div class="empty-state">
        <div class="empty-icon">📋</div>
        <div class="empty-msg">Nenhum cadastro encontrado.</div>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>Telefone</th>
          <th>Empreendimento</th>
          <th>Data / Hora</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leads as $l): ?>
        <tr>
          <td style="color:var(--text2);font-size:.75rem"><?= (int)$l['id'] ?></td>
          <td class="td-nome"><?= htmlspecialchars($l['nome']) ?></td>
          <td class="td-tel"><?= htmlspecialchars($l['telefone']) ?></td>
          <td><?= htmlspecialchars($l['empreendimento']) ?></td>
          <td class="td-data"><?= date('d/m/Y H:i', strtotime($l['data_cadastro'])) ?></td>
          <td>
            <span class="badge <?= $l['status'] ?>">
              <span class="badge-dot"></span>
              <?= ucfirst($l['status']) ?>
            </span>
          </td>
          <td>
            <div class="actions-wrap">
              <?php if ($l['status'] !== 'atendido'): ?>
              <form class="action-form" method="POST">
                <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>"/>
                <input type="hidden" name="atualizar_status" value="atendido"/>
                <button type="submit" class="btn-sm btn-atender" title="Marcar como atendido">✓ Atendido</button>
              </form>
              <?php endif; ?>
              <?php if ($l['status'] !== 'descartado'): ?>
              <form class="action-form" method="POST">
                <input type="hidden" name="lead_id" value="<?= (int)$l['id'] ?>"/>
                <input type="hidden" name="atualizar_status" value="descartado"/>
                <button type="submit" class="btn-sm btn-descartar" title="Descartar">✗ Descartar</button>
              </form>
              <?php endif; ?>
              <form class="action-form" method="POST" onsubmit="return confirm('Excluir este lead permanentemente?')">
                <input type="hidden" name="excluir_id" value="<?= (int)$l['id'] ?>"/>
                <button type="submit" class="btn-sm btn-excluir" title="Excluir">🗑</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</main>

<?php endif; ?>
</body>
</html>
