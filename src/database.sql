-- ============================================================
--  VIDA CENTRO DE SAÚDE — Base de Dados PostgreSQL
--  Copiar e executar no psql ou em qualquer cliente PostgreSQL
-- ============================================================

-- Criar base de dados (executar separado se necessário)
-- CREATE DATABASE clinica_vida ENCODING 'UTF8';

-- ============================================================
-- TABELAS
-- ============================================================

-- Staff (médicos e recepcionistas)
CREATE TABLE IF NOT EXISTS staff (
    id          SERIAL PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,   -- id de login
    chave       VARCHAR(100) NOT NULL,           -- hash da password (password_hash/BCRYPT)
    nome        VARCHAR(150) NOT NULL,
    tipo        VARCHAR(20)  NOT NULL CHECK (tipo IN ('medico', 'recepcionista')),
    clinica     BOOLEAN      NOT NULL DEFAULT FALSE,  -- TRUE = médico interno da clínica
    criado_em   TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Marcações / tickets
CREATE TABLE IF NOT EXISTS marcacoes (
    id          SERIAL PRIMARY KEY,
    ticket      VARCHAR(30)  NOT NULL UNIQUE,
    data        DATE         NOT NULL,
    hora        TIME         NULL,              -- definida pela recepcionista
    cliente     VARCHAR(10)  NOT NULL DEFAULT 'antigo' CHECK (cliente IN ('novo', 'antigo')),
    urgencia    VARCHAR(10)  NOT NULL DEFAULT 'normal' CHECK (urgencia IN ('normal', 'urgente')),
    estado      VARCHAR(20)  NOT NULL DEFAULT 'Pendente'
                             CHECK (estado IN ('Pendente', 'Concluido', 'Cancelado')),
    medico      VARCHAR(150) NOT NULL DEFAULT '',
    processo    VARCHAR(50)  NOT NULL DEFAULT '',
    criado_em   TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_em  TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Notificações de cancelamento (uma linha activa de cada vez)
CREATE TABLE IF NOT EXISTS notificacoes (
    id          SERIAL PRIMARY KEY,
    ativa       BOOLEAN      NOT NULL DEFAULT FALSE,
    mensagem_pt TEXT         NOT NULL DEFAULT '',
    mensagem_en TEXT         NOT NULL DEFAULT '',
    criado_em   TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Trigger para atualizar updated_em automaticamente
CREATE OR REPLACE FUNCTION trg_updated_em()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_em = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS set_updated_em ON marcacoes;
CREATE TRIGGER set_updated_em
    BEFORE UPDATE ON marcacoes
    FOR EACH ROW EXECUTE FUNCTION trg_updated_em();

-- ============================================================
-- DADOS INICIAIS — STAFF
-- ============================================================

-- Passwords em texto simples substituídas por hash bcrypt (password_hash / PASSWORD_BCRYPT).
-- Hash de 'armando'  = 1234arma | Hash de 'maria' e 'luisa' = vida2026
-- Para gerar novos hashes ao adicionar staff, usa: php gerar_hash.php "nova_password"
INSERT INTO staff (username, chave, nome, tipo, clinica) VALUES
    ('armando', '$2y$10$iNSfjT.pO0x8zGP.ED9CueNBOa4x2UKgh8CaCJX99tU9Rxo6YoTHm', 'Dr. Armando Silva', 'medico',        TRUE),
    ('maria',   '$2y$10$Mf6fkbZ9WykMq/kIEuSHDu.K9UZ9IATFo/uPXWtu7/xK3efOUk2ey', 'Maria Santos',      'recepcionista', FALSE),
    ('luisa',   '$2y$10$JkOIE84OgqSs4Xj3ocoGAe2b6K9gwmyiVaXd9h4Y7g3M50rWyZsUq', 'Luísa Mário',       'recepcionista', FALSE)
ON CONFLICT (username) DO NOTHING;

-- ============================================================
-- DADOS INICIAIS — MARCAÇÕES (migração do JSON)
-- ============================================================

INSERT INTO marcacoes (ticket, data, cliente, urgencia, estado, medico, processo, criado_em) VALUES
    ('#V-0001',  '2026-01-05', 'antigo', 'normal',  'Concluido', 'Dr. Armando Silva',   'P-100',  NOW()),
    ('#V-0002',  '2026-01-12', 'novo',   'urgente', 'Concluido', 'Dr.ª Luísa Mário',   'P-101',  NOW()),
    ('#V-0003',  '2026-05-06', 'antigo', 'normal',  'Concluido', 'Dr. Carlos Nhaca',    '1016',   NOW()),
    ('#V-0004',  '2026-01-20', 'novo',   'normal',  'Concluido', 'Dr. Armando Silva',   'P-102',  NOW()),
    ('#V-0005',  '2026-01-25', 'antigo', 'urgente', 'Concluido', 'Dr.ª Luísa Mário',   'P-103',  NOW()),
    ('#V-0006',  '2026-01-28', 'novo',   'normal',  'Concluido', 'Dr. Armando Silva',   'P-104',  NOW()),
    ('#V-0101',  '2026-02-02', 'antigo', 'normal',  'Concluido', 'Dr.ª Luísa Mário',   'P-105',  NOW()),
    ('#V-0102',  '2026-02-03', 'novo',   'urgente', 'Concluido', 'Dr. Armando Silva',   'P-106',  NOW()),
    ('#V-0103',  '2026-05-06', 'antigo', 'normal',  'Concluido', 'Dr. Armando Silva',   '1018',   NOW()),
    ('#V-0104',  '2026-02-07', 'novo',   'normal',  'Concluido', 'Dr.ª Luísa Mário',   'P-107',  NOW()),
    ('#V-0105',  '2026-02-10', 'antigo', 'normal',  'Concluido', 'Dr. Armando Silva',   'P-108',  NOW()),
    ('#V-0106',  '2026-02-12', 'novo',   'urgente', 'Concluido', 'Dr.ª Luísa Mário',   'P-109',  NOW()),
    ('#V-0107',  '2026-02-14', 'antigo', 'normal',  'Concluido', 'Dr. Armando Silva',   'P-110',  NOW()),
    ('#V-0108',  '2026-05-04', 'novo',   'normal',  'Concluido', 'Dr.ª Luísa Mário',   '1013',   NOW()),
    ('#V-0109',  '2026-02-18', 'antigo', 'urgente', 'Concluido', 'Dr.ª Luísa Mário',   'P-111',  NOW()),
    ('#V-0110',  '2026-02-20', 'novo',   'normal',  'Concluido', 'Dr. Armando Silva',   'P-112',  NOW()),
    ('#V-0111',  '2026-02-22', 'antigo', 'normal',  'Concluido', 'Dr.ª Luísa Mário',   'P-113',  NOW()),
    ('#V-0112',  '2026-02-24', 'novo',   'urgente', 'Concluido', 'Dr. Armando Silva',   'P-114',  NOW()),
    ('#V-0113',  '2026-02-26', 'antigo', 'normal',  'Concluido', 'Dr.ª Luísa Mário',   'P-115',  NOW()),
    ('#V-0114',  '2026-02-28', 'novo',   'normal',  'Concluido', 'Dr. Armando Silva',   'P-116',  NOW()),
    ('#V-1001',  '2026-03-05', 'antigo', 'normal',  'Concluido', 'Dr. Armando Silva',   'P-500',  NOW()),
    ('#V-1002',  '2026-03-05', 'novo',   'normal',  'Concluido', 'Dr. Armando Silva',   'P-501',  NOW()),
    ('#V-1003',  '2026-03-07', 'antigo', 'urgente', 'Concluido', 'Dr.ª Luísa Mário',   'P-220',  NOW()),
    ('#V-1004',  '2026-03-10', 'novo',   'normal',  'Concluido', 'Dr. Armando Silva',   'P-502',  NOW()),
    ('#V-1005',  '2026-03-12', 'antigo', 'normal',  'Concluido', 'Dr.ª Luísa Mário',   'P-115',  NOW()),
    ('#V-1006',  '2026-03-15', 'novo',   'urgente', 'Concluido', 'Dr. Armando Silva',   'P-503',  NOW()),
    ('#V-1007',  '2026-03-18', 'antigo', 'normal',  'Concluido', 'Dr.ª Luísa Mário',   'P-090',  NOW()),
    ('#V-1008',  '2026-03-20', 'novo',   'normal',  'Concluido', 'Dr. Armando Silva',   'P-504',  NOW()),
    ('#V-1009',  '2026-03-22', 'antigo', 'urgente', 'Concluido', 'Dr.ª Luísa Mário',   'P-310',  NOW()),
    ('#V-1010',  '2026-03-25', 'novo',   'normal',  'Concluido', 'Dr. Armando Silva',   'P-505',  NOW()),
    ('#V-2001',  '2026-04-01', 'antigo', 'normal',  'Concluido', 'Dr. Armando Silva',   'P-400',  NOW()),
    ('#V-2002',  '2026-04-02', 'novo',   'urgente', 'Concluido', 'Dr.ª Luísa Mário',   'P-506',  NOW()),
    ('#V-2003',  '2026-04-03', 'antigo', 'normal',  'Concluido', 'Dr. Armando Silva',   'P-401',  NOW()),
    ('#V-2004',  '2026-04-05', 'novo',   'normal',  'Concluido', 'Dr.ª Luísa Mário',   'P-507',  NOW()),
    ('#V-2005',  '2026-04-06', 'antigo', 'urgente', 'Concluido', 'Dr. Armando Silva',   'P-402',  NOW()),
    ('#V-2006',  '2026-04-07', 'novo',   'normal',  'Concluido', 'Dr.ª Luísa Mário',   'P-508',  NOW()),
    ('#V-2007',  '2026-04-08', 'antigo', 'normal',  'Concluido', 'Dr. Armando Silva',   'P-403',  NOW()),
    ('#V-2008',  '2026-04-09', 'novo',   'urgente', 'Concluido', 'Dr.ª Luísa Mário',   'P-509',  NOW()),
    ('#V-2009',  '2026-04-10', 'antigo', 'normal',  'Concluido', 'Dr. Armando Silva',   'P-404',  NOW()),
    ('#V-2010',  '2026-04-11', 'novo',   'normal',  'Concluido', 'Dr.ª Luísa Mário',   'P-510',  NOW()),
    ('#V-6079',  '2026-05-06', 'novo',   'urgente', 'Pendente',  'Dr. Armando Silva',   '1017',   NOW()),
    ('#V-7240',  '2026-05-04', 'antigo', 'normal',  'Concluido', 'Dr. Armando Silva',   '1012',   NOW()),
    ('V-81ECE5', '2026-05-04', 'novo',   'normal',  'Concluido', 'Dr. Armando Silva',   '1014',   '2026-04-15 06:49:10'),
    ('V-5D9973', '2026-05-04', 'antigo', 'normal',  'Concluido', 'Dr.ª Luísa Mário',   '1015',   '2026-04-15 06:57:50'),
    ('V-9A07DB', '2026-04-27', 'novo',   'urgente', 'Concluido', 'Dr. Armando Silva',   '0002',   '2026-04-25 17:50:54'),
    ('V-11BC82', '2026-05-18', 'novo',   'urgente', 'Pendente',  'Dr. Armando Silva',   '1019',   '2026-04-25 17:52:55'),
    ('V-9DCA4B', '2026-05-18', 'novo',   'urgente', 'Pendente',  '',                    '',       '2026-04-25 17:52:58'),
    ('V-F627E7', '2026-05-18', 'novo',   'normal', 'Pendente',  '',                    '',       '2026-04-28 15:24:59'),
    ('V-3FDFC9', '2026-05-18', 'novo',   'urgente', 'Pendente', '',                    '',        '2026-04-28 15:25:54')
ON CONFLICT (ticket) DO NOTHING;

-- ============================================================
-- DADOS INICIAIS — NOTIFICAÇÃO
-- ============================================================

INSERT INTO notificacoes (ativa, mensagem_pt, mensagem_en) VALUES
    (FALSE, 'As consultas para amanhã estão adiadas', '')
ON CONFLICT DO NOTHING;

-- ============================================================
-- ÍNDICES úteis para performance
-- ============================================================

CREATE INDEX IF NOT EXISTS idx_marcacoes_data    ON marcacoes (data);
CREATE INDEX IF NOT EXISTS idx_marcacoes_estado  ON marcacoes (estado);
CREATE INDEX IF NOT EXISTS idx_marcacoes_medico  ON marcacoes (medico);
CREATE INDEX IF NOT EXISTS idx_marcacoes_ticket  ON marcacoes (ticket);
