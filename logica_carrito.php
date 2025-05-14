<?php
require 'conexion.php'; // Asegura session_start() y $pdo

// --- Funciones Auxiliares ---
function actualizarContadorCarrito($pdo, $id_usuario_sesion) {
    try {
        $stmt_count = $pdo->prepare("SELECT SUM(cantidad) as total_items FROM carrito WHERE usuario = :id_usuario_sesion");
        $stmt_count->execute([':id_usuario_sesion' => $id_usuario_sesion]);
        $cart_data = $stmt_count->fetch();
        $_SESSION['cart_item_count'] = (int)($cart_data['total_items'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error al actualizar contador del carrito: " . $e->getMessage());
        $_SESSION['cart_item_count'] = 0;
    }
}

function redirigirConMensaje($url, $mensaje, $tipo = 'info') {
    $_SESSION['flash_message'] = ['mensaje' => $mensaje, 'tipo' => $tipo];
    header("Location: " . $url);
    exit();
}

// --- Verificar si el usuario está logueado ---
if (!isset($_SESSION['user_id'])) {
    if(isset($_POST['action']) && isset($_POST['id_prod'])) {
        $_SESSION['pending_cart_action'] = $_POST;
    }
    $_SESSION['redirect_after_login'] = $_POST['pagina_retorno'] ?? 'index.php';
    redirigirConMensaje('login.php', 'Debes iniciar sesión para gestionar tu carrito.', 'warning');
}

$id_usuario_actual_sesion = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pagina_retorno = $_POST['pagina_retorno'] ?? $_GET['pagina_retorno'] ?? 'index.php';

if (empty($action)) {
    redirigirConMensaje($pagina_retorno, 'Acción no especificada.', 'danger');
}

try {
    // --- ACCIÓN: AGREGAR PRODUCTO ---
    if ($action === 'agregar_al_carrito') {
        $id_prod_recibido = isset($_POST['id_prod']) ? (int)$_POST['id_prod'] : 0;
        $cantidad_solicitada = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;

        if ($id_prod_recibido <= 0 || $cantidad_solicitada <= 0) {
            redirigirConMensaje($pagina_retorno, 'Datos de producto o cantidad no válidos.', 'danger');
        }

        $stmt_prod_info = $pdo->prepare("SELECT nombre, cantidad_alm FROM producto WHERE id_prod = :id_prod_recibido");
        $stmt_prod_info->execute([':id_prod_recibido' => $id_prod_recibido]);
        $producto_info_db = $stmt_prod_info->fetch();

        if (!$producto_info_db) {
            redirigirConMensaje($pagina_retorno, 'Producto no encontrado.', 'danger');
        }

        $stmt_check = $pdo->prepare("SELECT id_carrito, cantidad FROM carrito WHERE usuario = :id_usuario_actual_sesion AND producto = :id_prod_recibido");
        $stmt_check->execute([':id_usuario_actual_sesion' => $id_usuario_actual_sesion, ':id_prod_recibido' => $id_prod_recibido]);
        $item_carrito_existente = $stmt_check->fetch();

        $cantidad_actual_en_carrito_db = $item_carrito_existente ? $item_carrito_existente['cantidad'] : 0;
        $cantidad_total_deseada = $cantidad_actual_en_carrito_db + $cantidad_solicitada;

        if ($cantidad_total_deseada > $producto_info_db['cantidad_alm']) {
            $disponible_para_agregar = $producto_info_db['cantidad_alm'] - $cantidad_actual_en_carrito_db;
            $mensaje = "No hay suficiente stock para agregar " . $cantidad_solicitada . " unidad(es) de '" . htmlspecialchars($producto_info_db['nombre']) . "'. ";
            if ($disponible_para_agregar > 0) {
                $mensaje .= "Puedes agregar hasta " . $disponible_para_agregar . " más.";
            } else {
                $mensaje .= "Ya tienes todo el stock disponible en tu carrito o no hay stock.";
            }
            redirigirConMensaje($pagina_retorno, $mensaje, 'warning');
        }

        if ($item_carrito_existente) {
            $stmt_update = $pdo->prepare("UPDATE carrito SET cantidad = :cantidad WHERE id_carrito = :id_carrito");
            $stmt_update->execute([':cantidad' => $cantidad_total_deseada, ':id_carrito' => $item_carrito_existente['id_carrito']]);
        } else {
            $stmt_insert = $pdo->prepare("INSERT INTO carrito (usuario, producto, cantidad) VALUES (:id_usuario_actual_sesion, :id_prod_recibido, :cantidad)");
            $stmt_insert->execute([':id_usuario_actual_sesion' => $id_usuario_actual_sesion, ':id_prod_recibido' => $id_prod_recibido, ':cantidad' => $cantidad_solicitada]);
        }
        actualizarContadorCarrito($pdo, $id_usuario_actual_sesion);
        redirigirConMensaje($pagina_retorno, "'" . htmlspecialchars($producto_info_db['nombre']) . "' añadido/actualizado en tu carrito.", 'success');

    } elseif ($action === 'actualizar_cantidad_carrito') {
        $id_prod_recibido = isset($_POST['id_prod']) ? (int)$_POST['id_prod'] : 0;
        $nueva_cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;

        if ($id_prod_recibido <= 0) {
            redirigirConMensaje('carrito.php', 'Producto no válido.', 'danger');
        }

        if ($nueva_cantidad <= 0) { 
            $stmt_delete = $pdo->prepare("DELETE FROM carrito WHERE usuario = :id_usuario_actual_sesion AND producto = :id_prod_recibido");
            $stmt_delete->execute([':id_usuario_actual_sesion' => $id_usuario_actual_sesion, ':id_prod_recibido' => $id_prod_recibido]);
            actualizarContadorCarrito($pdo, $id_usuario_actual_sesion);
            redirigirConMensaje('carrito.php', 'Producto eliminado del carrito.', 'success');
        } else {
            $stmt_prod_info = $pdo->prepare("SELECT nombre, cantidad_alm FROM producto WHERE id_prod = :id_prod_recibido");
            $stmt_prod_info->execute([':id_prod_recibido' => $id_prod_recibido]);
            $producto_info_db = $stmt_prod_info->fetch();

            if (!$producto_info_db) {
                redirigirConMensaje('carrito.php', 'Producto no encontrado.', 'danger');
            }

            if ($nueva_cantidad > $producto_info_db['cantidad_alm']) {
                redirigirConMensaje('carrito.php', "No hay suficiente stock para " . $nueva_cantidad . " unidades de '" . htmlspecialchars($producto_info_db['nombre']) . "'. Máximo: " . $producto_info_db['cantidad_alm'], 'warning');
            }
            $stmt_update = $pdo->prepare("UPDATE carrito SET cantidad = :cantidad WHERE usuario = :id_usuario_actual_sesion AND producto = :id_prod_recibido");
            $stmt_update->execute([':cantidad' => $nueva_cantidad, ':id_usuario_actual_sesion' => $id_usuario_actual_sesion, ':id_prod_recibido' => $id_prod_recibido]);
            actualizarContadorCarrito($pdo, $id_usuario_actual_sesion);
            redirigirConMensaje('carrito.php', 'Cantidad actualizada en el carrito.', 'success');
        }
    
    } elseif ($action === 'eliminar_item_carrito') {
        $id_prod_recibido = isset($_GET['id_prod']) ? (int)$_GET['id_prod'] : 0;

        if ($id_prod_recibido <= 0) {
            redirigirConMensaje('carrito.php', 'Producto no válido para eliminar.', 'danger');
        }
        $stmt_delete = $pdo->prepare("DELETE FROM carrito WHERE usuario = :id_usuario_actual_sesion AND producto = :id_prod_recibido");
        $stmt_delete->execute([':id_usuario_actual_sesion' => $id_usuario_actual_sesion, ':id_prod_recibido' => $id_prod_recibido]);
        actualizarContadorCarrito($pdo, $id_usuario_actual_sesion);
        redirigirConMensaje('carrito.php', 'Producto eliminado del carrito.', 'success');

    // --- ACCIÓN: PROCESAR COMPRA ---
    } elseif ($action === 'procesar_compra_carrito') {
        $pdo->beginTransaction();

        $stmt_carrito_compra = $pdo->prepare("
            SELECT c.producto AS id_prod_en_carrito, c.cantidad, p.nombre, p.precio, p.cantidad_alm 
            FROM carrito c
            JOIN producto p ON c.producto = p.id_prod
            WHERE c.usuario = :id_usuario_actual_sesion FOR UPDATE
        ");
        $stmt_carrito_compra->execute([':id_usuario_actual_sesion' => $id_usuario_actual_sesion]);
        $items_a_comprar = $stmt_carrito_compra->fetchAll();

        if (empty($items_a_comprar)) {
            $pdo->rollBack();
            redirigirConMensaje('carrito.php', 'Tu carrito está vacío.', 'warning');
        }

        $errores_stock = [];
        $id_compra_grupo_actual = uniqid('compra_', true); // Este se insertará en historial.id_compra_grupo

        foreach ($items_a_comprar as $item) {
            if ($item['cantidad'] > $item['cantidad_alm']) {
                $errores_stock[] = "No hay suficiente stock para '" . htmlspecialchars($item['nombre']) . "'. Solicitado: " . $item['cantidad'] . ", Disponible: " . $item['cantidad_alm'] . ".";
            }
        }

        if (!empty($errores_stock)) {
            $pdo->rollBack();
            redirigirConMensaje('carrito.php', "Error de stock:<br>" . implode("<br>", $errores_stock) . "<br>Por favor, actualiza tu carrito.", 'danger');
        }

        // Preparar la inserción en la tabla historial con los nombres de columna correctos
        $stmt_insert_historial = $pdo->prepare(
            "INSERT INTO historial (id_compra_grupo, usuario, producto, cantidad, precio_unitario, subtotal_prod) 
             VALUES (:id_compra_grupo, :usuario_hist, :producto_hist, :cantidad_hist, :precio_unitario_hist, :subtotal_prod_hist)"
            // id_compra es AUTO_INCREMENT, no se incluye aquí
            // fecha_compra la has omitido
        );
        
        $stmt_update_stock = $pdo->prepare("UPDATE producto SET cantidad_alm = cantidad_alm - :cantidad_comprada WHERE id_prod = :id_prod_actualizar AND cantidad_alm >= :cantidad_comprada_check");
        
        $compra_procesada_algun_item = false;

        foreach ($items_a_comprar as $item) {
            // $item['id_prod_en_carrito'] contiene el id_prod del producto
            // $item['cantidad'] es la cantidad a comprar
            // $item['precio'] es el precio unitario del producto
            
            $update_result = $stmt_update_stock->execute([
                ':cantidad_comprada' => $item['cantidad'], 
                ':id_prod_actualizar' => $item['id_prod_en_carrito'], 
                ':cantidad_comprada_check' => $item['cantidad']
            ]);

            if ($stmt_update_stock->rowCount() > 0) {
                $subtotal_item_actual_para_historial = $item['precio'] * $item['cantidad'];
                
                $stmt_insert_historial->execute([
                    ':id_compra_grupo' => $id_compra_grupo_actual,
                    ':usuario_hist' => $id_usuario_actual_sesion, // FK a usuario.id_usuario
                    ':producto_hist' => $item['id_prod_en_carrito'], // FK a producto.id_prod
                    ':cantidad_hist' => $item['cantidad'],
                    ':precio_unitario_hist' => $item['precio'],
                    ':subtotal_prod_hist' => $subtotal_item_actual_para_historial
                ]);
                $compra_procesada_algun_item = true;
            } else {
                $errores_stock[] = "El stock para '" . htmlspecialchars($item['nombre']) . "' cambió o es insuficiente. No se pudo completar la compra de este ítem.";
            }
        }

        if (!empty($errores_stock)) {
            $pdo->rollBack(); 
            redirigirConMensaje('carrito.php', "Error al procesar la compra:<br>" . implode("<br>", $errores_stock), 'danger');
        } elseif ($compra_procesada_algun_item) {
            $stmt_vaciar_carrito = $pdo->prepare("DELETE FROM carrito WHERE usuario = :id_usuario_actual_sesion");
            $stmt_vaciar_carrito->execute([':id_usuario_actual_sesion' => $id_usuario_actual_sesion]);
            $pdo->commit();
            actualizarContadorCarrito($pdo, $id_usuario_actual_sesion);
            redirigirConMensaje('carrito.php', '¡Compra realizada con éxito! Gracias por tu pedido.', 'success');
        } else {
            $pdo->rollBack();
            redirigirConMensaje('carrito.php', 'No se pudo procesar ningún artículo de tu pedido. Verifica el stock.', 'warning');
        }
    } else {
        redirigirConMensaje($pagina_retorno, 'Acción de carrito no reconocida.', 'danger');
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error general en logica_carrito.php: " . $e->getMessage());
    redirigirConMensaje('carrito.php', 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo.', 'danger');
}
?>
