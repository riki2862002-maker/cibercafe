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
    <title>💻 Gestión de Máquinas - Cibercafé Pro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="../index.php" class="btn-back" title="Volver al Dashboard">
            <i class="fas fa-arrow-left"></i> Dashboard Principal
        </a>
        <h2><i class="fas fa-desktop"></i> 💻 Gestión de Máquinas</h2>
        <span class="page-stats">
            <?= $pdo->query("SELECT COUNT(*) FROM maquinas")->fetchColumn() ?> máquinas registradas
        </span>
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

        <!-- BOTÓN NUEVA MÁQUINA -->
        <div class="page-header">
            <button class="btn btn-success btn-large" onclick="toggleForm('maquinaForm')">
                <i class="fas fa-plus"></i> Nueva Máquina
            </button>
        </div>

        <!-- FORMULARIO MÁQUINA -->
        <form id="maquinaForm" class="form-modal" style="display: none;" method="POST">
            <input type="hidden" name="action" id="maquinaAction" value="add_maquina">
            <input type="hidden" name="id" id="maquinaId">
            
            <h3 id="formTitle">Nueva Máquina</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> Número de Máquina *</label>
                    <input type="text" name="numero" required placeholder="PC-01, PC-02, etc...">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-network-wired"></i> Dirección IP</label>
                    <input type="text" name="ip_address" placeholder="192.168.1.101">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-memory"></i> RAM (GB) *</label>
                    <input type="number" name="ram_gb" min="4" max="64" value="8" required>
                </div>
                
                <div class="form-group full-width">
                    <label><i class="fas fa-toggle-on"></i> Estado *</label>
                    <select name="estado" required>
                        <option value="disponible">🟢 Disponible</option>
                        <option value="ocupada">🟡 Ocupada</option>
                        <option value="mantenimiento">🔴 Mantenimiento</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar Máquina
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleForm('maquinaForm')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </form>

        <!-- ESTADÍSTICAS MÁQUINAS -->
        <?php
        $stats_maquinas = $pdo->query("
            SELECT 
                SUM(CASE WHEN estado='disponible' THEN 1 ELSE 0 END) as disponibles,
                SUM(CASE WHEN estado='ocupada' THEN 1 ELSE 0 END) as ocupadas,
                SUM(CASE WHEN estado='mantenimiento' THEN 1 ELSE 0 END) as mantenimiento,
                AVG(ram_gb) as ram_promedio
            FROM maquinas
        ")->fetch();
        ?>
        <div class="stats-grid">
            <div class="stat-card success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <span class="stat-num"><?= $stats_maquinas['disponibles'] ?></span>
                    <span class="stat-label">Disponibles</span>
                </div>
            </div>
            <div class="stat-card busy">
                <i class="fas fa-play-circle"></i>
                <div>
                    <span class="stat-num"><?= $stats_maquinas['ocupadas'] ?></span>
                    <span class="stat-label">Ocupadas</span>
                </div>
            </div>
            <div class="stat-card warning">
                <i class="fas fa-tools"></i>
                <div>
                    <span class="stat-num"><?= $stats_maquinas['mantenimiento'] ?></span>
                    <span class="stat-label">Mantenimiento</span>
                </div>
            </div>
            <div class="stat-card primary">
                <i class="fas fa-microchip"></i>
                <div>
                    <span class="stat-num"><?= round($stats_maquinas['ram_promedio'], 1) ?> GB</span>
                    <span class="stat-label">RAM Promedio</span>
                </div>
            </div>
        </div>

        <!-- TABLA DE MÁQUINAS -->
        <div class="table-container">
            <h3>Lista Completa de Máquinas</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Número</th>
                        <th>IP</th>
                        <th>RAM</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM maquinas ORDER BY numero ASC");
                    while ($maquina = $stmt->fetch()) {
                        $estadoClass = match($maquina['estado']) {
                            'disponible' => 'badge-success',
                            'ocupada' => 'badge-warning',
                            'mantenimiento' => 'badge-danger',
                            default => 'badge-secondary'
                        };
                        
                        $estadoIcon = match($maquina['estado']) {
                            'disponible' => '🟢',
                            'ocupada' => '🟡',
                            'mantenimiento' => '🔴',
                            default => '⚪'
                        };
                        
                        echo "
                        <tr>
                            <td><strong>#{$maquina['id']}</strong></td>
                            <td>
                                <div class='maquina-name'>
                                    <i class='fas fa-desktop'></i>
                                    " . htmlspecialchars($maquina['numero']) . "
                                </div>
                            </td>
                            <td>" . ($maquina['ip_address'] ?: '<em>No asignada</em>') . "</td>
                            <td><strong>{$maquina['ram_gb']} GB</strong></td>
                            <td>
                                <span class='badge {$estadoClass}'>
                                    {$estadoIcon} {$maquina['estado']}
                                </span>
                            </td>
                            <td>" . date('d/m/Y H:i', strtotime($maquina['created_at'])) . "</td>
                            <td>
                                <button class='btn-icon btn-edit' onclick='editMaquina({$maquina['id']}, \"{$maquina['numero']}\", \"{$maquina['ip_address']}\", {$maquina['ram_gb']}, \"{$maquina['estado']})' title='Editar'>
                                    <i class='fas fa-edit'></i>
                                </button>
                                <button class='btn-icon btn-delete' onclick='deleteMaquina({$maquina['id']}, \"{$maquina['numero']}\")' title='Eliminar'>
                                    <i class='fas fa-trash'></i>
                                </button>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../assets/script.js"></script>
    <script>
        // Toggle formulario
        function toggleForm(formId) {
            const form = document.getElementById(formId);
            const title = document.getElementById('formTitle');
            
            if (form.style.display === 'block') {
                form.style.display = 'none';
                form.reset();
                document.getElementById('maquinaAction').value = 'add_maquina';
                title.textContent = 'Nueva Máquina';
            } else {
                form.style.display = 'block';
                document.querySelector('input[name="numero"]').focus();
            }
        }

        // Editar máquina
        function editMaquina(id, numero, ip, ram, estado) {
            toggleForm('maquinaForm');
            document.getElementById('maquinaId').value = id;
            document.getElementById('maquinaAction').value = 'edit_maquina';
            document.getElementById('formTitle').textContent = `Editando: ${numero}`;
            
            document.querySelector('input[name="numero"]').value = numero;
            document.querySelector('input[name="ip_address"]').value = ip || '';
            document.querySelector('input[name="ram_gb"]').value = ram;
            document.querySelector('select[name="estado"]').value = estado;
        }

        // Eliminar máquina
        function deleteMaquina(id, numero) {
            if (confirm(`¿Eliminar la máquina "${numero}"?\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_maquina">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Validación IP
        document.querySelector('input[name="ip_address"]').addEventListener('input', function() {
            const ip = this.value;
            const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
            if (ip && !ipRegex.test(ip)) {
                this.setCustomValidity('Formato IP inválido (ej: 192.168.1.100)');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
