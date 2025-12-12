CREATE DATABASE cibercafe_pro;
USE cibercafe_pro;

CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleado') DEFAULT 'empleado',
    telefono VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE maquinas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(20) UNIQUE NOT NULL,
    ip_address VARCHAR(15),
    estado ENUM('disponible', 'ocupada', 'mantenimiento') DEFAULT 'disponible',
    ram_gb INT DEFAULT 8,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sesiones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    maquina_id INT,
    tiempo_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tiempo_fin TIMESTAMP NULL,
    tiempo_total_min INT DEFAULT 0,
    costo DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (maquina_id) REFERENCES maquinas(id)
);

CREATE TABLE impresiones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sesion_id INT,
    paginas_bn INT DEFAULT 0,
    paginas_color INT DEFAULT 0,
    costo_bn DECIMAL(10,2) DEFAULT 0,
    costo_color DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id)
);

-- VISTAS REPORTES
CREATE VIEW reportes_diarios AS
SELECT 
    DATE(tiempo_fin) as fecha,
    COUNT(*) as sesiones,
    SUM(tiempo_total_min) as minutos_totales,
    SUM(s.costo) as ingresos_tiempo,
    SUM(COALESCE(i.total, 0)) as ingresos_impresiones,
    SUM(s.costo + COALESCE(i.total, 0)) as total_general
FROM sesiones s 
LEFT JOIN impresiones i ON s.id = i.sesion_id 
WHERE s.tiempo_fin IS NOT NULL 
GROUP BY DATE(tiempo_fin);

CREATE VIEW reportes_mensuales AS
SELECT 
    YEAR(tiempo_fin) as año,
    MONTH(tiempo_fin) as mes,
    COUNT(*) as sesiones,
    SUM(tiempo_total_min) as minutos_totales,
    SUM(s.costo) as ingresos_tiempo,
    SUM(COALESCE(i.total, 0)) as ingresos_impresiones,
    SUM(s.costo + COALESCE(i.total, 0)) as total_general
FROM sesiones s 
LEFT JOIN impresiones i ON s.id = i.sesion_id 
WHERE s.tiempo_fin IS NOT NULL 
GROUP BY YEAR(tiempo_fin), MONTH(tiempo_fin);

-- DATOS DE PRUEBA
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Admin Principal', 'admin@cibercafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Empleado 1', 'empleado1@cibercafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado');

INSERT INTO maquinas (numero, ip_address, ram_gb) VALUES 
('PC-01', '192.168.1.101', 8),
('PC-02', '192.168.1.102', 16),
('PC-03', '192.168.1.103', 8),
('PC-04', '192.168.1.104', 16);
