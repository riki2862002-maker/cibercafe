<?php 
require_once '../config.php'; 
if (!isLoggedIn()) { 
    header('Location: ../index.php'); 
    exit; 
}

// Obtener reportes y totales
$reportes_diarios = getReportes($pdo, 'diario');
$reportes_mensuales = getReportes($pdo, 'mensual');
$totales = getTotales($pdo);

// Estadísticas adicionales
$hoy_total = $pdo->query("
    SELECT SUM(s.costo + COALESCE(i.total, 0)) as total_hoy
    FROM sesiones s LEFT JOIN impresiones i ON s.id = i.sesion_id 
    WHERE DATE(s.tiempo_fin) = CURDATE()
")->fetchColumn() ?? 0;

$mes_actual_total = $pdo->query("
    SELECT SUM(s.costo + COALESCE(i.total, 0)) as total_mes
    FROM sesiones s LEFT JOIN impresiones i ON s.id = i.sesion_id 
    WHERE MONTH(s.tiempo_fin) = MONTH(CURDATE()) AND YEAR(s.tiempo_fin) = YEAR(CURDATE())
")->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Reportes Financieros - Cibercafé Pro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="../index.php" class="btn-back" title="Volver al Dashboard">
            <i class="fas fa-arrow-left"></i> Dashboard Principal
        </a>
        <h2><i class="fas fa-chart-bar"></i> 📊 Reportes Financieros</h2>
        <span class="page-stats">
            Total acumulado: $<strong><?= number_format($totales['gran_total'], 2) ?>
        </span>
    </nav>

    <div class="container">
        <!-- MENSAJES -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- TOTALES GENERALES -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <i class="fas fa-infinity"></i>
                <div>
                    <span class="stat-num">$<?= number_format($totales['gran_total'], 2) ?></span>
                    <span class="stat-label">Gran Total Acumulado</span>
                </div>
            </div>
            <div class="stat-card success">
                <i class="fas fa-clock"></i>
                <div>
                    <span class="stat-num"><?= number_format($totales['total_minutos'], 0) ?></span>
                    <span class="stat-label">Minutos Totales</span>
                </div>
            </div>
            <div class="stat-card info">
                <i class="fas fa-file-invoice-dollar"></i>
                <div>
                    <span class="stat-num"><?= number_format($totales['total_sesiones'], 0) ?></span>
                    <span class="stat-label">Sesiones Totales</span>
                </div>
            </div>
            <div class="stat-card warning">
                <i class="fas fa-calendar-day"></i>
                <div>
                    <span class="stat-num">$<?= number_format($hoy_total, 2) ?></span>
                    <span class="stat-label">Ingresos Hoy</span>
                </div>
            </div>
        </div>

        <!-- REPORTES DIARIOS -->
        <div class="table-container">
            <h3>📅 Últimos 30 Días (<?= count($reportes_diarios) ?> días con ventas)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Sesiones</th>
                        <th>Minutos</th>
                        <th>Tiempo ($)</th>
                        <th>Impresiones ($)</th>
                        <th><strong>Total ($)</strong></th>
                        <th>% Tiempo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_diario = 0;
                    foreach ($reportes_diarios as $reporte): 
                        $total_diario += $reporte['total_general'];
                        $porcentaje_tiempo = $reporte['total_general'] > 0 ? round(($reporte['ingresos_tiempo'] / $reporte['total_general']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><strong><?= date('d/m/Y', strtotime($reporte['fecha'])) ?></strong></td>
                        <td><?= number_format($reporte['sesiones']) ?></td>
                        <td><?= number_format($reporte['minutos_totales'], 0) ?> min</td>
                        <td class="money">$<?= number_format($reporte['ingresos_tiempo'], 2) ?></td>
                        <td class="money">$<?= number_format($reporte['ingresos_impresiones'], 2) ?></td>
                        <td class="total-column">
                            <strong>$<?= number_format($reporte['total_general'], 2) ?></strong>
                        </td>
                        <td><span class="badge <?= $porcentaje_tiempo > 70 ? 'success' : 'warning' ?>">
                            <?= $porcentaje_tiempo ?>%
                        </span></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td><strong>TOTAL 30 DÍAS</strong></td>
                        <td><strong><?= array_sum(array_column($reportes_diarios, 'sesiones')) ?></strong></td>
                        <td><strong><?= number_format(array_sum(array_column($reportes_diarios, 'minutos_totales')), 0) ?> min</strong></td>
                        <td class="money"><strong>$<?= number_format(array_sum(array_column($reportes_diarios, 'ingresos_tiempo')), 2) ?></strong></td>
                        <td class="money"><strong>$<?= number_format(array_sum(array_column($reportes_diarios, 'ingresos_impresiones')), 2) ?></strong></td>
                        <td class="total-column"><strong>$<?= number_format($total_diario, 2) ?></strong></td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- REPORTES MENSUALES -->
        <div class="table-container">
            <h3>📊 Últimos 12 Meses (<?= count($reportes_mensuales) ?> meses)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Sesiones</th>
                        <th>Minutos</th>
                        <th>Tiempo ($)</th>
                        <th>Impresiones ($)</th>
                        <th><strong>Total ($)</strong></th>
                        <th>Promedio Diario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_mensual = 0;
                    foreach ($reportes_mensuales as $reporte): 
                        $total_mensual += $reporte['total_general'];
                        $mes_nombre = date('M Y', mktime(0, 0, 0, $reporte['mes'], 1, $reporte['año']));
                        $promedio_diario = $reporte['total_general'] / 30; // Aprox 30 días/mes
                    ?>
                    <tr>
                        <td><strong><?= $mes_nombre ?></strong></td>
                        <td><?= number_format($reporte['sesiones']) ?></td>
                        <td><?= number_format($reporte['minutos_totales'], 0) ?> min</td>
                        <td class="money">$<?= number_format($reporte['ingresos_tiempo'], 2) ?></td>
                        <td class="money">$<?= number_format($reporte['ingresos_impresiones'], 2) ?></td>
                        <td class="total-column">
                            <strong>$<?= number_format($reporte['total_general'], 2) ?></strong>
                        </td>
                        <td class="money">$<?= number_format($promedio_diario, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td><strong>TOTAL 12 MESES</strong></td>
                        <td><strong><?= array_sum(array_column($reportes_mensuales, 'sesiones')) ?></strong></td>
                        <td><strong><?= number_format(array_sum(array_column($reportes_mensuales, 'minutos_totales')), 0) ?> min</strong></td>
                        <td class="money"><strong>$<?= number_format(array_sum(array_column($reportes_mensuales, 'ingresos_tiempo')), 2) ?></strong></td>
                        <td class="money"><strong>$<?= number_format(array_sum(array_column($reportes_mensuales, 'ingresos_impresiones')), 2) ?></strong></td>
                        <td class="total-column"><strong>$<?= number_format($total_mensual, 2) ?></strong></td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- RESUMEN RÁPIDO -->
        <div class="stats-grid">
            <div class="stat-card success">
                <i class="fas fa-calendar-week"></i>
                <div>
                    <span class="stat-num">$<?= number_format($mes_actual_total, 2) ?></span>
                    <span class="stat-label">Mes Actual</span>
                </div>
            </div>
            <div class="stat-card primary">
                <i class="fas fa-trending-up"></i>
                <div>
                    <span class="stat-num"><?= round(($totales['gran_total'] > 0 ? ($hoy_total / $totales['gran_total'] * 100) : 0), 1) ?>%</span>
                    <span class="stat-label">% del Total (Hoy)</span>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/script.js"></script>
    <script>
        // Auto-refresh cada 2 minutos
        setTimeout(() => {
            location.reload();
        }, 120000);
    </script>
</body>
</html>
