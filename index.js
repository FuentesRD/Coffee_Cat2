// Simulación de carga de productos con spinner
document.addEventListener('DOMContentLoaded', function() {
    const productos = [
        {
            nombre: "Café Arábica Premium",
            descripcion: "Café de altura tostado medio con notas de chocolate y nuez.",
            precio: "$12.99",
            imagen: "https://via.placeholder.com/300x200"
        },
        {
            nombre: "Café Robusta Intenso",
            descripcion: "Café robusto con un sabor fuerte y cuerpo completo.",
            precio: "$10.99",
            imagen: "https://via.placeholder.com/300x200"
        },
        {
            nombre: "Café Descafeinado Suave",
            descripcion: "Café suave sin cafeína, ideal para cualquier momento del día.",
            precio: "$11.49",
            imagen: "https://via.placeholder.com/300x200"
        }
        // Agrega más productos según necesites
    ];

    const container = document.getElementById('productosContainer');
    const spinner = document.querySelector('.spinner-cafe');

    // Simular carga con retardo de 1.5 segundos
    setTimeout(() => {
        spinner.style.display = 'none';
        productos.forEach(producto => {
            container.innerHTML += `
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow">
                        <img src="${producto.imagen}" class="card-img-top img-fluid" alt="${producto.nombre}">
                        <div class="card-body">
                            <h5 class="card-title">${producto.nombre}</h5>
                            <p class="card-text">${producto.descripcion}</p>
                            <p class="h4 text-primary">${producto.precio}</p>
                            <button class="btn btn-primary">
                                <span class="spinner-border spinner-border-sm d-none"></span>
                                Añadir al carrito
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }, 1500);

    // Manejar clic en botones de añadir al carrito
    document.querySelectorAll('.btn-primary').forEach(button => {
        button.addEventListener('click', function() {
            const spinner = this.querySelector('.spinner-border');
            spinner.classList.remove('d-none');
            this.disabled = true;
            
            setTimeout(() => {
                spinner.classList.add('d-none');
                this.disabled = false;
            }, 1000);
        });
    });
});