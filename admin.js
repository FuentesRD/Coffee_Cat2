        const inputFile = document.getElementById('imgpath_nueva');
        const imagePreview = document.getElementById('imagePreview'); 
        const existingImageContainer = document.querySelector('.img-preview'); // Contenedor de la imagen actual al editar

        if (inputFile) {
            inputFile.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        let previewElement = imagePreview; // Usar el placeholder por defecto
                        if (document.querySelector('input[name="id_prod"]') && existingImageContainer) {
                            // Si estamos editando y hay una imagen actual, la reemplazamos
                            previewElement = existingImageContainer;
                        }
                        
                        if (previewElement) {
                           previewElement.src = e.target.result;
                           previewElement.style.display = 'block';
                        }
                    }
                    reader.readAsDataURL(file);
                }
            });
        }