<?php 
require_once 'config.php'; 
if (!isLoggedIn()) { 
    header('Location: login.php'); 
    exit; 
}

// Obtener estadísticas
$totales = getTotales($pdo);
$stats = getStatsRapidas($pdo);

// Estadísticas del día
$hoy_tiempo = $pdo->query("SELECT SUM(costo) FROM sesiones WHERE DATE(tiempo_fin)=CURDATE()")->fetchColumn() ?? 0;
$hoy_impresiones = $pdo->query("SELECT SUM(total) FROM impresiones WHERE DATE(fecha)=CURDATE()")->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏠 Dashboard - Cibercafé Pro</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-gamepad"></i>
            <span>Cibercafé Pro</span>
        </div>
        <div class="nav-user">
            <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <span class="user-role badge <?= $_SESSION['rol'] === 'admin' ? 'badge-danger' : 'badge-primary' ?>">
                <?= $_SESSION['rol'] === 'admin' ? 'Admin' : 'Empleado' ?>
            </span>
            <a href="login.php?logout=1" class="btn-logout" title="Cerrar Sesión">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </nav>

    <div class="container">
        <!-- MENSAJES DE ALERTA -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- TOTALES GENERALES -->
        <section class="stats-section">
            <h2><i class="fas fa-chart-line"></i> Resumen General</h2>
            <div class="stats-grid">
                <div class="stat-card primary">
                    <i class="fas fa-infinity"></i>
                    <div>
                        <span class="stat-num">$<?= number_format($totales['gran_total'] ?? 0, 2) ?></span>
                        <span class="stat-label">Gran Total</span>
                    </div>
                </div>
                <div class="stat-card success">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span class="stat-num"><?= number_format($totales['total_minutos'] ?? 0) ?></span>
                        <span class="stat-label">Minutos Totales</span>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <div>
                        <span class="stat-num"><?= number_format($totales['total_sesiones'] ?? 0) ?></span>
                        <span class="stat-label">Sesiones Totales</span>
                    </div>
                </div>
                <div class="stat-card info">
                    <i class="fas fa-desktop"></i>
                    <div>
                        <span class="stat-num"><?= $stats['total_maqs'] ?></span>
                        <span class="stat-label">Máquinas Totales</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ESTADÍSTICAS DEL DÍA -->
        <section class="stats-section">
            <h2><i class="fas fa-calendar-day"></i> Hoy <?= date('d/m/Y') ?></h2>
            <div class="stats-grid">
                <div class="stat-card busy">
                    <i class="fas fa-play-circle"></i>
                    <div>
                        <span class="stat-num"><?= $stats['ses_activas'] ?></span>
                        <span class="stat-label">Sesiones Activas</span>
                    </div>
                </div>
                <div class="stat-card warning">
                    <i class="fas fa-desktop"></i>
                    <div>
                        <span class="stat-num"><?= $stats['maqs_ocupadas'] ?></span>
                        <span class="stat-label">Máquinas Ocupadas</span>
                    </div>
                </div>
                <div class="stat-card success">
                    <i class="fas fa-dollar-sign"></i>
                    <div>
                        <span class="stat-num">$<?= number_format($hoy_tiempo + $hoy_impresiones, 2) ?></span>
                        <span class="stat-label">Ingresos Hoy</span>
                    </div>
                </div>
                <div class="stat-card secondary">
                    <i class="fas fa-print"></i>
                    <div>
                        <span class="stat-num">$<?= number_format($hoy_impresiones, 2) ?></span>
                        <span class="stat-label">Impresiones Hoy</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ACCIONES PRINCIPALES -->
        <section class="actions-section">
            <h2><i class="fas fa-list"></i> Panel de Control</h2>
            <div class="actions-grid">
                <?php if(isAdmin()): ?>
                <a href="dashboard/usuarios.php" class="action-card" title="Gestión completa de usuarios">
                    <div class="action-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>👥 Gestión de Usuarios</h3>
                    <p>Agregar, editar y eliminar usuarios</p>
                </a>
                
                <a href="dashboard/maquinas.php" class="action-card" title="Control total de máquinas">
                    <div class="action-icon">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <h3>💻 Gestión de Máquinas</h3>
                    <p>Configurar y monitorear equipos</p>
                </a>
                <?php endif; ?>
                
                <a href="dashboard/sesiones.php" class="action-card" title="Iniciar y finalizar sesiones">
                    <div class="action-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>⏱️ Control de Sesiones</h3>
                    <p>Iniciar, finalizar y monitorear</p>
                </a>
                
                <a href="dashboard/impresiones.php" class="action-card" title="Registro de impresiones y costos">
                    <div class="action-icon">
                        <i class="fas fa-print"></i>
                    </div>
                    <h3>🖨️ Gestión de Impresiones</h3>
                    <p>Controlar páginas y cobros</p>
                </a>
                
                <a href="dashboard/reportes.php" class="action-card" title="Reportes financieros completos">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>📊 Reportes Financieros</h3>
                    <p>Diarios, mensuales y totales</p>
                </a>
            </div>
        </section>
    </div>

    <script src="assets/script.js"></script>
    <script>
        // Auto-refresh cada 30 segundos
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        // Animación de números
        function animateNumbers() {
            document.querySelectorAll('.stat-num').forEach(num => {
                num.style.animation = 'countUp 1s ease-out';
            });
        }
        
        // Ejecutar al cargar
        window.addEventListener('load', animateNumbers);
    </script>
</body>
</html>
