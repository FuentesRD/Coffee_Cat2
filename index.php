<?php
// Start the session
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="index.css">

    <title>Coffee Cat</title>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cup-hot-fill"></i> Coffee Cat
            </a>
            
            <div class="d-flex align-items-center order-lg-3 ms-auto">
                <!-- Iconos siempre visibles -->
                <a href="cuenta.php" class="nav-link nav-icon">
                    <i class="bi bi-person"></i>
                    <span class="d-none d-lg-inline">Cuenta</span>
                </a>
                <a href="#" class="nav-link nav-icon">
                    <i class="bi bi-cart3"></i>
                    <span class="badge rounded-pill">3</span>
                    <span class="d-none d-lg-inline">Carrito</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <div class="collapse navbar-collapse order-lg-2" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house-door me-1"></i>Inicio
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-shop-window me-1"></i>Productos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">
                                <i class="bi bi-cup-straw me-2"></i>Café en Grano
                            </a></li>
                            <li><a class="dropdown-item" href="#">
                                <i class="bi bi-mortarboard-fill me-2"></i>Café Molido
                            </a></li>
                            <li><a class="dropdown-item" href="#">
                                <i class="bi bi-funnel-fill me-2"></i>Accesorios
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-info-circle me-1"></i>Nosotros
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-chat-dots me-1"></i>Contacto
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    

    <header class="hero-section">
        <div class="container text-center text-white">
            <h1 class="display-4 mb-4" style="font-family: 'Playfair Display', serif;">
                Descubre el Auténtico Sabor del Café
            </h1>
            <p class="lead mb-4">Cafés premium de origen único tostado artesanal</p>
            <a href="#" class="btn btn-lg btn-outline-light">Ver Productos</a>
        </div>
    </header>

    <div id="cafeCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#cafeCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#cafeCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#cafeCarousel" data-bs-slide-to="2"></button>
        </div>
        
        <div class="carousel-inner">
            <div class="carousel-item active" style="background-image: url('https://via.placeholder.com/1920x500')">
                <div class="carousel-caption">
                    <h3>Nueva Colección Otoño 2023</h3>
                    <p>Descubre nuestros cafés de cosecha reciente</p>
                </div>
            </div>
            <div class="carousel-item" style="background-image: url('https://via.placeholder.com/1920x500')">
                <div class="carousel-caption">
                    <h3>Equipo para Baristas</h3>
                    <p>Los mejores accesorios profesionales</p>
                </div>
            </div>
            <div class="carousel-item" style="background-image: url('https://via.placeholder.com/1920x500')">
                <div class="carousel-caption">
                    <h3>Ofertas Especiales</h3>
                    <p>Hasta 40% de descuento en selección premium</p>
                </div>
            </div>
        </div>
        
        <button class="carousel-control-prev" type="button" data-bs-target="#cafeCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#cafeCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5" style="font-family: 'Playfair Display', serif;">
                Nuestros Productos Destacados
            </h2>
            
            <div class="row g-4">
                <!-- Producto 1 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-star-fill text-warning me-2"></i>
                                <h5 class="card-title mb-0">Café Arábica Premium</h5>
                            </div>
                            <p class="card-text">
                                <i class="bi bi-globe me-2"></i>Origen: Colombia
                            </p>
                            <p class="card-text">
                                <i class="bi bi-thermometer-sun me-2"></i>Tueste: Medio
                            </p>
                            <p class="h4 text-primary">$12.99</p>
                            <button class="btn btn-primary">
                                <i class="bi bi-cart-plus me-2"></i>Añadir
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Producto 2 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-star-fill text-warning me-2"></i>
                                <h5 class="card-title mb-0">Café Robusta Intenso</h5>
                            </div>
                            <p class="card-text">
                                <i class="bi bi-globe me-2"></i>Origen: Brasil
                            </p>
                            <p class="card-text">
                                <i class="bi bi-thermometer-sun me-2"></i>Tueste: Oscuro
                            </p>
                            <p class="h4 text-primary">$10.99</p>
                            <button class="btn btn-primary">
                                <i class="bi bi-cart-plus me-2"></i>Añadir
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Producto 3 -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-star-fill text-warning me-2"></i>
                                <h5 class="card-title mb-0">Descafeinado Natural</h5>
                            </div>
                            <p class="card-text">
                                <i class="bi bi-globe me-2"></i>Origen: México
                            </p>
                            <p class="card-text">
                                <i class="bi bi-thermometer-sun me-2"></i>Tueste: Claro
                            </p>
                            <p class="h4 text-primary">$14.99</p>
                            <button class="btn btn-primary">
                                <i class="bi bi-cart-plus me-2"></i>Añadir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-geo-alt me-2"></i>Ubicación</h5>
                    <p class="mb-0">Av. Café 123, Ciudad de México</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-clock me-2"></i>Horario</h5>
                    <p>Lun-Vie: 9 AM - 7 PM</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-telephone me-2"></i>Contacto</h5>
                    <p class="mb-0">contacto@cafedelmundo.com</p>
                    <p>Tel: +52 55 1234 5678</p>
                </div>
            </div>
            <div class="text-center pt-3 border-top">
                <p class="mb-0">
                    Síguenos:
                    <a href="#" class="text-white mx-2"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white mx-2"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white mx-2"><i class="bi bi-whatsapp"></i></a>
                </p>
            </div>
        </div>
    </footer>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    <script src="index.js"></script>
</body>
</html>