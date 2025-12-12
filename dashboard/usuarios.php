<?php 
require_once '../config.php'; 
if (!isLoggedIn()) { 
    header('Location: ../index.php'); 
    exit; 
}
if (!isAdmin()) { 
    header('Location: ../index.php'); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👥 Gestión de Usuarios - Cibercafé Pro</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="../index.php" class="btn-back" title="Volver al Dashboard">
            <i class="fas fa-arrow-left"></i> Dashboard Principal
        </a>
        <h2><i class="fas fa-users"></i> 👥 Gestión de Usuarios</h2>
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

        <!-- BOTÓN NUEVO USUARIO -->
        <div class="page-header">
            <button class="btn btn-primary btn-large" onclick="toggleForm('userForm')">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </button>
        </div>

        <!-- FORMULARIO USUARIO -->
        <form id="userForm" class="form-modal" style="display: none;" method="POST">
            <input type="hidden" name="action" id="userAction" value="add_usuario">
            <input type="hidden" name="id" id="userId">
            
            <h3><?= $_POST['action'] ?? 'Nuevo Usuario' ?></h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nombre Completo *</label>
                    <input type="text" name="nombre" required placeholder="Juan Pérez">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" name="email" required placeholder="juan@example.com">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="tel" name="telefono" placeholder="555-123-4567">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Contraseña *</label>
                    <input type="password" name="password" id="userPassword" required placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Rol *</label>
                    <select name="rol" required>
                        <option value="empleado">Empleado</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar Usuario
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleForm('userForm')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </form>

        <!-- TABLA DE USUARIOS -->
        <div class="table-container">
            <h3>Lista de Usuarios (<?= $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() ?> total)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
                    while ($user = $stmt->fetch()) {
                        $rolClass = $user['rol'] === 'admin' ? 'badge-danger' : 'badge-primary';
                        echo "
                        <tr>
                            <td><strong>#{$user['id']}</strong></td>
                            <td>
                                <div class='user-name'>
                                    <i class='fas fa-user'></i>
                                    " . htmlspecialchars($user['nombre']) . "
                                </div>
                            </td>
                            <td>" . htmlspecialchars($user['email']) . "</td>
                            <td>" . ($user['telefono'] ?: '<em>No registrado</em>') . "</td>
                            <td>
                                <span class='badge {$rolClass}'>" . ucfirst($user['rol']) . "</span>
                            </td>
                            <td>" . date('d/m/Y H:i', strtotime($user['created_at'])) . "</td>
                            <td>
                                <button class='btn-icon btn-edit' onclick='editUser({$user['id']}, \"{$user['nombre']}\", \"{$user['email']}\", \"{$user['telefono']}\", \"{$user['rol']}\")' title='Editar'>
                                    <i class='fas fa-edit'></i>
                                </button>
                                <button class='btn-icon btn-delete' onclick='deleteUser({$user['id']}, \"{$user['nombre']}\")' title='Eliminar'>
                                    <i class='fas fa-trash'></i>
                                </button>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- ESTADÍSTICAS USUARIOS -->
        <?php
        $stats_users = $pdo->query("
            SELECT 
                SUM(CASE WHEN rol='admin' THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN rol='empleado' THEN 1 ELSE 0 END) as empleados
            FROM usuarios
        ")->fetch();
        ?>
        <div class="stats-grid">
            <div class="stat-card primary">
                <i class="fas fa-user-shield"></i>
                <div><span class="stat-num"><?= $stats_users['admins'] ?></span> Administradores</div>
            </div>
            <div class="stat-card success">
                <i class="fas fa-users"></i>
                <div><span class="stat-num"><?= $stats_users['empleados'] ?></span> Empleados</div>
            </div>
        </div>
    </div>

    <script src="../assets/script.js"></script>
    <script>
        // Toggle formulario
        function toggleForm(formId) {
            const form = document.getElementById(formId);
            if (form.style.display === 'block') {
                form.style.display = 'none';
                form.reset();
                document.getElementById('userAction').value = 'add_usuario';
                document.getElementById('userPassword').required = true;
            } else {
                form.style.display = 'block';
                document.querySelector('input[name="nombre"]').focus();
            }
        }

        // Editar usuario
        function editUser(id, nombre, email, telefono, rol) {
            toggleForm('userForm');
            document.getElementById('userId').value = id;
            document.getElementById('userAction').value = 'edit_usuario';
            document.querySelector('input[name="nombre"]').value = nombre;
            document.querySelector('input[name="email"]').value = email;
            document.querySelector('input[name="telefono"]').value = telefono;
            document.querySelector('select[name="rol"]').value = rol;
            document.getElementById('userPassword').required = false;
            document.getElementById('userPassword').placeholder = 'Dejar vacío para no cambiar';
        }

        // Eliminar usuario
        function deleteUser(id, nombre) {
            if (confirm(`¿Eliminar a "${nombre}"?\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_usuario">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Validación contraseña
        document.querySelector('input[name="password"]').addEventListener('input', function() {
            if (this.value.length < 6 && this.required) {
                this.setCustomValidity('La contraseña debe tener al menos 6 caracteres');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
