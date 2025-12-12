<?php 
require_once '../config.php'; 
if (!isLoggedIn()) { 
    header('Location: ../index.php'); 
    exit; 
}

// Obtener sesión específica si viene por parámetro
$sesion_id = $_GET['sesion'] ?? null;
$sesion_info = null;

if ($sesion_id) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.tiempo_inicio, s.tiempo_fin, u.nombre as usuario, m.numero as maquina
        FROM sesiones s 
        JOIN usuarios u ON s.usuario_id = u.id 
        JOIN maquinas m ON s.maquina_id = m.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$sesion_id]);
    $sesion_info = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🖨️ Gestión de Impresiones - Cibercafé Pro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="../index.php" class="btn-back" title="Volver al Dashboard">
            <i class="fas fa-arrow-left"></i> Dashboard Principal
        </a>
        <h2><i class="fas fa-print"></i> 🖨️ Gestión de Impresiones</h2>
    </nav>

    <div class="container">
        <!-- MENSAJES -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- ESTADÍSTICAS IMPRESIONES -->
        <?php
        $stats_impresiones = $pdo->query("
            SELECT 
                COUNT(*) as total_impresiones,
                SUM(paginas_bn) as total_bn,
                SUM(paginas_color) as total_color,
                SUM(total) as ingresos_impresiones
            FROM impresiones
        ")->fetch();
        
        $hoy_impresiones = $pdo->query("SELECT SUM(total) FROM impresiones WHERE DATE(fecha)=CURDATE()")->fetchColumn() ?? 0;
        ?>
        <div class="stats-grid">
            <div class="stat-card primary">
                <i class="fas fa-print"></i>
                <div>
                    <span class="stat-num">$<?= number_format($stats_impresiones['ingresos_impresiones'], 2) ?></span>
                    <span class="stat-label">Total Impresiones</span>
                </div>
            </div>
            <div class="stat-card success">
                <i class="fas fa-file-alt"></i>
                <div>
                    <span class="stat-num"><?= $stats_impresiones['total_bn'] ?></span>
                    <span class="stat-label">Páginas B/N</span>
                </div>
            </div>
            <div class="stat-card warning">
                <i class="fas fa-file-image"></i>
                <div>
                    <span class="stat-num"><?= $stats_impresiones['total_color'] ?></span>
                    <span class="stat-label">Páginas Color</span>
                </div>
            </div>
            <div class="stat-card info">
                <i class="fas fa-calendar-day"></i>
                <div>
                    <span class="stat-num">$<?= number_format($hoy_impresiones, 2) ?></span>
                    <span class="stat-label">Hoy</span>
                </div>
            </div>
        </div>

        <?php if ($sesion_info): ?>
            <!-- FORMULARIO IMPRESIÓN RÁPIDA PARA SESIÓN ESPECÍFICA -->
            <div class="form-modal">
                <h3>🖨️ Registrar Impresión Rápida</h3>
                <p><strong>Sesión:</strong> #<?= $sesion_info['id'] ?> | 
                   <strong>Usuario:</strong> <?= htmlspecialchars($sesion_info['usuario']) ?> | 
                   <strong>Máquina:</strong> <?= $sesion_info['maquina'] ?></p>
                
                <form method="POST" class="form-grid">
                    <input type="hidden" name="action" value="add_impresion">
                    <input type="hidden" name="sesion_id" value="<?= $sesion_id ?>">
                    
                    <div class="form-group">
                        <label><i class="fas fa-file-alt"></i> Páginas B/N ($2 c/u)</label>
                        <input type="number" name="paginas_bn" min="0" value="0" placeholder="0">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-file-image"></i> Páginas Color ($5 c/u)</label>
                        <input type="number" name="paginas_color" min="0" value="0" placeholder="0">
                    </div>
                    
                    <div class="form-group full-width">
                        <label><strong>Total Impresión: $<span id="totalImpresion">0.00</span></strong></label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success btn-large">
                            <i class="fas fa-print"></i> Registrar Impresión
                        </button>
                        <a href="impresiones.php" class="btn btn-secondary btn-large">Ver Todas</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- TABLA TODAS LAS IMPRESIONES -->
        <div class="table-container">
            <h3>Todas las Impresiones (<?= $stats_impresiones['total_impresiones'] ?> registros)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sesión</th>
                        <th>Usuario</th>
                        <th>Máquina</th>
                        <th>B/N</th>
                        <th>Color</th>
                        <th>Costo B/N</th>
                        <th>Costo Color</th>
                        <th><strong>Total</strong></th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT i.*, s.id as sesion_id, u.nombre, m.numero
                        FROM impresiones i
                        JOIN sesiones s ON i.sesion_id = s.id
                        JOIN usuarios u ON s.usuario_id = u.id
                        JOIN maquinas m ON s.maquina_id = m.id
                        ORDER BY i.fecha DESC LIMIT 100
                    ");
                    while ($impresion = $stmt->fetch()) {
                        echo "
                        <tr>
                            <td><strong>#{$impresion['id']}</strong></td>
                            <td><a href='?sesion={$impresion['sesion_id']}'>#{$impresion['sesion_id']}</a></td>
                            <td>" . htmlspecialchars($impresion['nombre']) . "</td>
                            <td>{$impresion['numero']}</td>
                            <td><strong>{$impresion['paginas_bn']}</strong></td>
                            <td><strong>{$impresion['paginas_color']}</strong></td>
                            <td class='money'>$" . number_format($impresion['costo_bn'], 2) . "</td>
                            <td class='money'>$" . number_format($impresion['costo_color'], 2) . "</td>
                            <td class='total-column'><strong>$" . number_format($impresion['total'], 2) . "</strong></td>
                            <td>" . date('d/m H:i', strtotime($impresion['fecha'])) . "</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- RESUMEN HOY -->
        <?php if ($hoy_impresiones > 0): ?>
        <div class="table-container">
            <h3>📅 Impresiones de Hoy (<?= date('d/m/Y') ?>)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sesión</th>
                        <th>Usuario</th>
                        <th>B/N</th>
                        <th>Color</th>
                        <th>Total</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT i.*, u.nombre, s.id as sesion_id
                        FROM impresiones i
                        JOIN sesiones s ON i.sesion_id = s.id
                        JOIN usuarios u ON s.usuario_id = u.id
                        WHERE DATE(i.fecha) = CURDATE()
                        ORDER BY i.fecha DESC
                    ");
                    while ($impresion = $stmt->fetch()) {
                        echo "
                        <tr>
                            <td><strong>#{$impresion['sesion_id']}</strong></td>
                            <td>" . htmlspecialchars($impresion['nombre']) . "</td>
                            <td>{$impresion['paginas_bn']}</td>
                            <td>{$impresion['paginas_color']}</td>
                            <td class='money'><strong>$" . number_format($impresion['total'], 2) . "</strong></td>
                            <td>" . date('H:i', strtotime($impresion['fecha'])) . "</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script src="../assets/script.js"></script>
    <script>
        // Calculadora de impresiones en tiempo real
        function calcularTotal() {
            const bn = parseInt(document.querySelector('input[name="paginas_bn"]').value) || 0;
            const color = parseInt(document.querySelector('input[name="paginas_color"]').value) || 0;
            const total = (bn * 2) + (color * 5);
            document.getElementById('totalImpresion').textContent = total.toFixed(2);
        }

        // Event listeners para inputs
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[name="paginas_bn"], input[name="paginas_color"]');
            inputs.forEach(input => {
                input.addEventListener('input', calcularTotal);
            });
            
            // Calcular inicial
            calcularTotal();
        });

        // Enter para submit
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.form.submit();
                }
            });
        });
    </script>
</body>
</html>
