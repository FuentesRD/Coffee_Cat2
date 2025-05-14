<?php
// Es crucial iniciar la sesión al principio de cualquier script que la necesite.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost'; // Tu host, usualmente localhost
$dbname = 'cafe';    // El nombre de tu base de datos
$user = 'root';      // Tu usuario de base de datos
$pass = '';          // Tu contraseña de base de datos

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Habilitar excepciones para errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Establecer el modo de fetch por defecto
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactivar emulación de preparadas para mayor seguridad
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Error de conexión a la BD: " . $e->getMessage());
    die("No se pudo conectar a la base de datos. Por favor, inténtalo más tarde.");
}

// Lógica para "Recordarme" usando cookies
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $cookie_data = json_decode($_COOKIE['remember_me'], true);

    if (isset($cookie_data['user_id']) && isset($cookie_data['token'])) {
        // En un sistema real, validar $cookie_data['token'] contra la BD
        $stmt_cookie_user = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = :id_usuario_cookie"); // Usar placeholder nombrado
        $stmt_cookie_user->execute([':id_usuario_cookie' => $cookie_data['user_id']]);
        $user_from_cookie = $stmt_cookie_user->fetch();
        
        if ($user_from_cookie) {
            $_SESSION['user_id'] = $user_from_cookie['id_usuario'];
            $_SESSION['user_nombre'] = $user_from_cookie['nombre_usuario'];
            $_SESSION['user_email'] = $user_from_cookie['email'];
        } else {
            setcookie('remember_me', '', time() - 3600, "/"); 
        }
    } else {
         setcookie('remember_me', '', time() - 3600, "/");
    }
}

// --- CÓDIGO CORREGIDO PARA EL CONTADOR DEL CARRITO ---
// Inicializar/Actualizar contador de ítems en el carrito en la sesión
if (isset($_SESSION['user_id'])) {
    try {
        // La columna en la tabla carrito se llama 'usuario' (no 'id_usuario')
        // y el valor que tenemos en $_SESSION['user_id'] es el que corresponde a usuario.id_usuario
        $stmt_cart_count_init = $pdo->prepare("SELECT SUM(cantidad) as total_items FROM carrito WHERE usuario = :id_usuario_en_sesion");
        $stmt_cart_count_init->execute([':id_usuario_en_sesion' => $_SESSION['user_id']]); // Usar el ID de usuario de la sesión
        $cart_data_init = $stmt_cart_count_init->fetch();
        $_SESSION['cart_item_count'] = (int)($cart_data_init['total_items'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error al obtener conteo del carrito en conexion.php: " . $e->getMessage());
        $_SESSION['cart_item_count'] = 0; // Default a 0 en caso de error
    }
} else {
    $_SESSION['cart_item_count'] = 0; // Si no hay usuario logueado, el carrito tiene 0 ítems.
}
// --- FIN: CÓDIGO CORREGIDO PARA EL CONTADOR DEL CARRITO ---
?>
