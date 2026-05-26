<?php
/* ═══════════════════════════════════════════════════════════════
   CONFIGURAÇÃO DO BANCO DE DADOS — ARRUDA IMÓVEIS
   Preencha com as credenciais do seu banco MySQL na Hostinger.
   Acesse: hPanel → Banco de Dados → MySQL Databases
═══════════════════════════════════════════════════════════════ */

define('DB_HOST', 'localhost');          // Geralmente "localhost" na Hostinger
define('DB_NAME', 'SEU_BANCO_AQUI');    // Nome do banco criado no hPanel
define('DB_USER', 'SEU_USUARIO_AQUI'); // Usuário do banco
define('DB_PASS', 'SUA_SENHA_AQUI');   // Senha do banco

/* ─── Senha do painel ARRUDA LEADS ─────────────────────────── */
define('ADMIN_SENHA', 'Arruda@2025');  // ← Mude para uma senha forte!

/* ─── Domínio autorizado a enviar leads (CORS) ──────────────── */
// Ex: 'https://dimitrifergus.github.io' ou '*' para qualquer origem
define('CORS_ORIGIN', '*');
