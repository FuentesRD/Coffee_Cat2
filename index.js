// Simulación de carga de productos con spinner
document.addEventListener('DOMContentLoaded', function() {
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