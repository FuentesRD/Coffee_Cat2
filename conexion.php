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
    // En un entorno de producción, loggear este error y mostrar un mensaje genérico.
    // No exponer detalles de la excepción al usuario.
    error_log("Error de conexión a la BD: " . $e->getMessage());
    die("No se pudo conectar a la base de datos. Por favor, inténtalo más tarde.");
}

// Lógica para "Recordarme" usando cookies
// Esto se ejecuta si el usuario no tiene una sesión activa pero sí tiene una cookie 'remember_me'.
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $cookie_data = json_decode($_COOKIE['remember_me'], true);

    // Validación básica de la cookie (en un sistema real, el token debería validarse contra la BD)
    if (isset($cookie_data['user_id']) && isset($cookie_data['token'])) {
        // Aquí, idealmente, buscarías el usuario Y validarías el token contra uno almacenado en la BD
        // Por simplicidad para este proyecto, solo usamos el user_id.
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$cookie_data['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Si el usuario existe, recreamos la sesión.
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['user_nombre'] = $user['nombre_usuario']; // Guardar también el nombre para fácil acceso
            $_SESSION['user_email'] = $user['email'];
        } else {
            // Si el user_id de la cookie no es válido, o el token no coincide (en un sistema más seguro),
            // se debería eliminar la cookie.
            setcookie('remember_me', '', time() - 3600, "/"); // Borrar cookie
        }
    } else {
         setcookie('remember_me', '', time() - 3600, "/"); // Cookie malformada, borrarla
    }
}
?>