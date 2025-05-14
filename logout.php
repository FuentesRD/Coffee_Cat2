<?php
// Es importante iniciar la sesión para poder destruirla.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Eliminar todas las variables de sesión.
$_SESSION = array();

// Si se desea destruir la sesión completamente, borra también la cookie de sesión.
// Nota: ¡Esto destruirá la sesión, y no la información de la sesión!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión.
session_destroy();

// Eliminar la cookie "Recordarme" si existe
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, "/"); // Establecer tiempo en el pasado
}

// Redirigir al usuario a la página de inicio o de login
header("Location: index.php"); // O login.php si prefieres
exit();
?>