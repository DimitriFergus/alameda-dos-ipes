-- ═══════════════════════════════════════════════════════════
--  ARRUDA IMÓVEIS — Script de criação do banco de dados
--  Execute este arquivo no phpMyAdmin da Hostinger:
--  hPanel → Banco de Dados → phpMyAdmin → aba SQL
-- ═══════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `leads` (
  `id`              INT           NOT NULL AUTO_INCREMENT,
  `nome`            VARCHAR(255)  NOT NULL,
  `telefone`        VARCHAR(25)   NOT NULL,
  `empreendimento`  VARCHAR(150)  NOT NULL DEFAULT 'Alameda dos Ipês',
  `origem`          VARCHAR(100)  NOT NULL DEFAULT 'Landing Page',
  `status`          ENUM('novo','atendido','descartado') NOT NULL DEFAULT 'novo',
  `ip`              VARCHAR(45)   DEFAULT NULL,
  `user_agent`      TEXT          DEFAULT NULL,
  `data_cadastro`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status`  (`status`),
  KEY `idx_data`    (`data_cadastro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
