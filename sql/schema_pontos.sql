-- tabela de eventos de ponto (cada clique: entrada/saída/pausa)
CREATE TABLE IF NOT EXISTS pontos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  funcionario_id INT NULL,        -- se houver identificação do funcionário
  obra_id INT NOT NULL,          -- FK para obras.id
  tipo ENUM('entrada','saida','pausa_inicio','pausa_fim') NOT NULL,
  ocorrido_at DATETIME(6) NOT NULL, -- armazene em UTC (ex: UTC_TIMESTAMP(6))
  device_ip VARCHAR(45) DEFAULT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_func_obra (funcionario_id, obra_id),
  INDEX idx_ocorrido (ocorrido_at)
);

-- tabela de jornadas (pares entrada/saída) para cálculo de duração
CREATE TABLE IF NOT EXISTS jornadas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  funcionario_id INT NULL,
  obra_id INT NOT NULL,
  start_at DATETIME(6) NOT NULL,
  end_at DATETIME(6) DEFAULT NULL,
  duration_seconds INT DEFAULT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_jornada_func (funcionario_id, obra_id, status),
  INDEX idx_jornada_period (start_at, end_at)
);