-- Tabela de funcionários e pivot obra_funcionario (many-to-many)
CREATE TABLE IF NOT EXISTS funcionarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(150),
  phone VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS obra_funcionario (
  obra_id INT NOT NULL,
  funcionario_id INT NOT NULL,
  role VARCHAR(100) DEFAULT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (obra_id, funcionario_id),
  INDEX idx_of_obra (obra_id),
  INDEX idx_of_func (funcionario_id),
  CONSTRAINT fk_of_obra FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE,
  CONSTRAINT fk_of_func FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;