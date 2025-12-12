<?php 
require_once '../config.php'; 
if (!isLoggedIn()) { 
    header('Location: ../index.php'); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⏱️ Control de Sesiones - Cibercafé Pro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="../index.php" class="btn-back" title="Volver al Dashboard">
            <i class="fas fa-arrow-left"></i> Dashboard Principal
        </a>
        <h2><i class="fas fa-clock"></i> ⏱️ Control de Sesiones</h2>
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

        <!-- ESTADÍSTICAS SESIONES -->
        <?php
        $stats_sesiones = $pdo->query("
            SELECT 
                COUNT(*) as total_sesiones,
                SUM(CASE WHEN tiempo_fin IS NULL THEN 1 ELSE 0 END) as activas,
                SUM(CASE WHEN tiempo_fin IS NOT NULL THEN 1 ELSE 0 END) as finalizadas,
                SUM(costo) as ingresos_tiempo
            FROM sesiones
        ")->fetch();
        
        $sesiones_activas = $pdo->query("SELECT COUNT(*) FROM sesiones WHERE tiempo_fin IS NULL")->fetchColumn();
        ?>
        <div class="stats-grid">
            <div class="stat-card busy">
                <i class="fas fa-play-circle"></i>
                <div>
                    <span class="stat-num"><?= $sesiones_activas ?></span>
                    <span class="stat-label">Sesiones Activas</span>
                </div>
            </div>
            <div class="stat-card success">
                <i class="fas fa-stopwatch"></i>
                <div>
                    <span class="stat-num"><?= $stats_sesiones['total_sesiones'] ?></span>
                    <span class="stat-label">Total Sesiones</span>
                </div>
            </div>
            <div class="stat-card primary">
                <i class="fas fa-dollar-sign"></i>
                <div>
                    <span class="stat-num">$<?= number_format($stats_sesiones['ingresos_tiempo'], 2) ?></span>
                    <span class="stat-label">Ingresos Tiempo</span>
                </div>
            </div>
        </div>

        <!-- BOTÓN INICIAR SESIÓN -->
        <div class="page-header">
            <button class="btn btn-success btn-large" onclick="showMaquinasDisponibles()">
                <i class="fas fa-play"></i> Iniciar Nueva Sesión
            </button>
        </div>

        <!-- MODAL SELECCIÓN MÁQUINAS -->
        <div id="modalMaquinas" class="modal-overlay" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>🎮 Selecciona una Máquina Disponible</h3>
                    <button class="btn-close" onclick="hideModal()">×</button>
                </div>
                <div id="maquinas-disponibles" class="maquinas-grid">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM maquinas WHERE estado='disponible' ORDER BY numero");
                    if ($stmt->rowCount() == 0) {
                        echo '<div class="no-maquinas"><i class="fas fa-exclamation-triangle"></i><br>No hay máquinas disponibles</div>';
                    }
                    while ($maquina = $stmt->fetch()) {
                        echo "
                        <div class='maquina-card disponible glow' 
                             data-id='{$maquina['id']}' 
                             data-numero='{$maquina['numero']}'
                             onclick='iniciarSesion({$maquina['id']}, \"{$maquina['numero']}\")'>
                            <div class='maquina-icon'>💻</div>
                            <h4>{$maquina['numero']}</h4>
                            <div class='maquina-specs'>
                                <div><i class='fas fa-memory'></i> {$maquina['ram_gb']}GB RAM</div>
                                <div><i class='fas fa-network-wired'></i> " . ($maquina['ip_address'] ?: 'No IP') . "</div>
                            </div>
                            <div class='maquina-status'>
                                <span class='status-dot green'></span>
                                <strong>DISPONIBLE</strong>
                            </div>
                            <button class='btn-play'>▶ INICIAR</button>
                        </div>";
                    }
                    ?>
                </div>
                <div style="text-align: center; margin-top: 2rem;">
                    <button class="btn btn-secondary" onclick="hideModal()">Cancelar</button>
                </div>
            </div>
        </div>

        <!-- TABLA SESIONES ACTIVAS -->
        <div class="table-container">
            <h3>Sesiones Activas (<?= $sesiones_activas ?>)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Máquina</th>
                        <th>Inicio</th>
                        <th>Duración</th>
                        <th>Impresiones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="sesionesActivas">
                    <?php
                    $stmt = $pdo->query("
                        SELECT s.id, s.tiempo_inicio, u.nombre, m.numero,
                               COALESCE(SUM(i.total), 0) as costo_impresiones
                        FROM sesiones s 
                        JOIN usuarios u ON s.usuario_id = u.id
                        JOIN maquinas m ON s.maquina_id = m.id
                        LEFT JOIN impresiones i ON s.id = i.sesion_id
                        WHERE s.tiempo_fin IS NULL 
                        GROUP BY s.id, u.nombre, m.numero, s.tiempo_inicio
                        ORDER BY s.tiempo_inicio DESC
                    ");
                    while ($sesion = $stmt->fetch()) {
                        echo "
                        <tr data-sesion-id='{$sesion['id']}'>
                            <td><strong>#{$sesion['id']}</strong></td>
                            <td><strong>" . htmlspecialchars($sesion['nombre']) . "</strong></td>
                            <td><strong>{$sesion['numero']}</strong></td>
                            <td>" . date('H:i:s', strtotime($sesion['tiempo_inicio'])) . "</td>
                            <td id='duracion-{$sesion['id']}'>Calculando...</td>
                            <td class='money'>$" . number_format($sesion['costo_impresiones'], 2) . "</td>
                            <td>
                                <a href='impresiones.php?sesion={$sesion['id']}' class='btn-icon btn-primary' title='Impresiones'>
                                    <i class='fas fa-print'></i>
                                </a>
                                <button class='btn-icon btn-warning' onclick='finalizarSesion({$sesion['id']})' title='Finalizar Sesión'>
                                    <i class='fas fa-stop'></i>
                                </button>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- TABLA SESIONES RECIENTES -->
        <div class="table-container">
            <h3>Últimas Sesiones Finalizadas (10 más recientes)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Máquina</th>
                        <th>Duración</th>
                        <th>Total Tiempo</th>
                        <th>Impresiones</th>
                        <th>Finalizado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT s.id, s.tiempo_total_min, s.costo, s.tiempo_fin, 
                               u.nombre, m.numero, COALESCE(SUM(i.total), 0) as imp_cost
                        FROM sesiones s 
                        JOIN usuarios u ON s.usuario_id = u.id
                        JOIN maquinas m ON s.maquina_id = m.id
                        LEFT JOIN impresiones i ON s.id = i.sesion_id
                        WHERE s.tiempo_fin IS NOT NULL
                        GROUP BY s.id, u.nombre, m.numero, s.tiempo_total_min, s.costo, s.tiempo_fin
                        ORDER BY s.tiempo_fin DESC LIMIT 10
                    ");
                    while ($sesion = $stmt->fetch()) {
                        $total = $sesion['costo'] + $sesion['imp_cost'];
                        echo "
                        <tr>
                            <td>#{$sesion['id']}</td>
                            <td>" . htmlspecialchars($sesion['nombre']) . "</td>
                            <td>{$sesion['numero']}</td>
                            <td>{$sesion['tiempo_total_min']} min</td>
                            <td class='money'>$" . number_format($sesion['costo'], 2) . "</td>
                            <td class='money'>$" . number_format($sesion['imp_cost'], 2) . "</td>
                            <td>" . date('d/m H:i', strtotime($sesion['tiempo_fin'])) . "</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../assets/script.js"></script>
    <script>
        // Modal máquinas
        function showMaquinasDisponibles() {
            document.getElementById('modalMaquinas').style.display = 'flex';
        }
        
        function hideModal() {
            document.getElementById('modalMaquinas').style.display = 'none';
        }

        // Iniciar sesión
        function iniciarSesion(maquinaId, numero) {
            if (confirm(`▶️ ¿Iniciar sesión en la máquina "${numero}"?\nLa máquina quedará ocupada.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `
                    <input type="hidden" name="action" value="iniciar_sesion">
                    <input type="hidden" name="maquina_id" value="${maquinaId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Finalizar sesión
        function finalizarSesion(sesionId) {
            if (confirm('⏹️ ¿Finalizar esta sesión?\nSe calculará el tiempo usado y se liberará la máquina.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `
                    <input type="hidden" name="action" value="finalizar_sesion">
                    <input type="hidden" name="sesion_id" value="${sesionId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Timer sesiones activas
        function actualizarDuraciones() {
            document.querySelectorAll('#sesionesActivas tr[data-sesion-id]').forEach(row => {
                const sesionId = row.dataset.sesionId;
                const duracionCell = document.getElementById(`duracion-${sesionId}`);
                if (duracionCell) {
                    // Simular timer (actualización real en reload)
                    duracionCell.textContent = '▶️ Activa';
                }
            });
        }
        
        // Actualizar cada 5 segundos
        setInterval(actualizarDuraciones, 5000);
    </script>
</body>
</html>
