<?php
session_start();
date_default_timezone_set('America/Mexico_City');

$host = 'localhost';
$dbname = 'cibercafe_pro';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("❌ Error de conexión a la base de datos: " . $e->getMessage());
}

function isLoggedIn() { 
    return isset($_SESSION['user_id']); 
}

function isAdmin() { 
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'; 
}

// FUNCIÓN TOTALES GENERALES
function getTotales($pdo) {
    return $pdo->query("
        SELECT 
            COUNT(*) as total_sesiones,
            SUM(tiempo_total_min) as total_minutos,
            SUM(s.costo) as total_tiempo,
            SUM(COALESCE(i.total, 0)) as total_impresiones,
            SUM(s.costo + COALESCE(i.total, 0)) as gran_total
        FROM sesiones s 
        LEFT JOIN impresiones i ON s.id = i.sesion_id
    ")->fetch();
}

// FUNCIÓN REPORTES DIARIOS/MENSUALES
function getReportes($pdo, $tipo = 'diario') {
    if ($tipo == 'diario') {
        return $pdo->query("
            SELECT * FROM reportes_diarios 
            ORDER BY fecha DESC 
            LIMIT 30
        ")->fetchAll();
    } else {
        return $pdo->query("
            SELECT * FROM reportes_mensuales 
            ORDER BY año DESC, mes DESC 
            LIMIT 12
        ")->fetchAll();
    }
}

// FUNCIÓN ESTADÍSTICAS RÁPIDAS
function getStatsRapidas($pdo) {
    return $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM maquinas) as total_maqs,
            (SELECT COUNT(*) FROM maquinas WHERE estado='ocupada') as maqs_ocupadas,
            (SELECT COUNT(*) FROM sesiones WHERE tiempo_fin IS NULL) as ses_activas,
            (SELECT SUM(costo) FROM sesiones WHERE DATE(tiempo_fin)=CURDATE()) as hoy_tiempo,
            (SELECT SUM(total) FROM impresiones WHERE DATE(fecha)=CURDATE()) as hoy_impresiones
    ")->fetch();
}

// === PROCESADOR CENTRAL DE ACCIONES ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $actions = [
        // 👥 GESTIÓN DE USUARIOS
        'add_usuario' => function() use($pdo) {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, telefono, rol) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['nombre'], 
                $_POST['email'], 
                password_hash($_POST['password'], PASSWORD_DEFAULT), 
                $_POST['telefono'] ?? '', 
                $_POST['rol']
            ]);
        },
        
        'edit_usuario' => function() use($pdo) {
            if (!empty($_POST['password'])) {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, password=?, telefono=?, rol=? WHERE id=?");
                $stmt->execute([
                    $_POST['nombre'], 
                    $_POST['email'], 
                    password_hash($_POST['password'], PASSWORD_DEFAULT), 
                    $_POST['telefono'], 
                    $_POST['rol'], 
                    $_POST['id']
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, telefono=?, rol=? WHERE id=?");
                $stmt->execute([
                    $_POST['nombre'], 
                    $_POST['email'], 
                    $_POST['telefono'], 
                    $_POST['rol'], 
                    $_POST['id']
                ]);
            }
        },
        
        'delete_usuario' => function() use($pdo) {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id=? AND id != 1");
            $stmt->execute([$_POST['id']]);
        },

        // 💻 GESTIÓN DE MÁQUINAS
        'add_maquina' => function() use($pdo) {
            $stmt = $pdo->prepare("INSERT INTO maquinas (numero, ip_address, estado, ram_gb) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['numero'], 
                $_POST['ip_address'], 
                $_POST['estado'], 
                $_POST['ram_gb']
            ]);
        },
        
        'edit_maquina' => function() use($pdo) {
            $stmt = $pdo->prepare("UPDATE maquinas SET numero=?, ip_address=?, estado=?, ram_gb=? WHERE id=?");
            $stmt->execute([
                $_POST['numero'], 
                $_POST['ip_address'], 
                $_POST['estado'], 
                $_POST['ram_gb'], 
                $_POST['id']
            ]);
        },
        
        'delete_maquina' => function() use($pdo) {
            $stmt = $pdo->prepare("DELETE FROM maquinas WHERE id=?");
            $stmt->execute([$_POST['id']]);
        },

        // ⏱️ GESTIÓN DE SESIONES
        'iniciar_sesion' => function() use($pdo) {
            // Crear nueva sesión
            $stmt = $pdo->prepare("INSERT INTO sesiones (usuario_id, maquina_id, tiempo_inicio) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $_POST['maquina_id']]);
            
            // Cambiar estado de máquina a ocupada
            $stmt = $pdo->prepare("UPDATE maquinas SET estado = 'ocupada' WHERE id = ?");
            $stmt->execute([$_POST['maquina_id']]);
        },
        
        'finalizar_sesion' => function() use($pdo) {
            $sesion_id = $_POST['sesion_id'];
            
            // Calcular tiempo transcurrido
            $stmt = $pdo->prepare("SELECT tiempo_inicio FROM sesiones WHERE id = ?");
            $stmt->execute([$sesion_id]);
            $inicio = $stmt->fetch()['tiempo_inicio'];
            $fin = date('Y-m-d H:i:s');
            $minutos = round((strtotime($fin) - strtotime($inicio)) / 60, 1);
            $costo_tiempo = round($minutos * 5 / 60, 2); // $5 por hora
            
            // Actualizar sesión con datos finales
            $stmt = $pdo->prepare("UPDATE sesiones SET tiempo_fin=?, tiempo_total_min=?, costo=? WHERE id=?");
            $stmt->execute([$fin, $minutos, $costo_tiempo, $sesion_id]);
            
            // Liberar máquina (volver a disponible)
            $stmt = $pdo->prepare("UPDATE maquinas m JOIN sesiones s ON m.id = s.maquina_id SET m.estado = 'disponible' WHERE s.id = ?");
            $stmt->execute([$sesion_id]);
        },

        // 🖨️ GESTIÓN DE IMPRESIONES
        'add_impresion' => function() use($pdo) {
            $bn = intval($_POST['paginas_bn'] ?? 0);
            $color = intval($_POST['paginas_color'] ?? 0);
            $costo_bn = $bn * 2;        // $2 por página B/N
            $costo_color = $color * 5;  // $5 por página color
            $total_imp = $costo_bn + $costo_color;
            
            $stmt = $pdo->prepare("INSERT INTO impresiones (sesion_id, paginas_bn, paginas_color, costo_bn, costo_color, total) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['sesion_id'], $bn, $color, $costo_bn, $costo_color, $total_imp]);
        }
    ];
    
    // Ejecutar acción solicitada
    if (isset($actions[$_POST['action']])) {
        try {
            $actions[$_POST['action']]();
            $_SESSION['success'] = '✅ Operación realizada correctamente';
        } catch (Exception $e) {
            $_SESSION['error'] = '❌ Error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = '❌ Acción no reconocida';
    }
    
    // Redirigir a página anterior
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// === PROCESAR LOGOUT ===
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
