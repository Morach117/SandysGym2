    $(document).ready(function() {
        // Función para enviar los datos del formulario mediante AJAX
        function guardarCambios() {
            // Obtener el formulario y el ID del socio
            var formulario = $("#editarPerfilForm");
            var idSocio = $("#id_socio").val();

            // Realizar la solicitud AJAX
            $.ajax({
                url: "./query/procesar_edicion.php",
                type: "POST",
                data: formulario.serialize(), // Serializar los datos del formulario
                success: function(response) {
                    // La solicitud fue exitosa
                    console.log(response);
                    // Mostrar una alerta de éxito con SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'La información del socio se ha actualizado correctamente.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        // Redireccionar a index.php?page=user
                        window.location.href = "index.php?page=user_home";
                    });
                },
                error: function(xhr, status, error) {
                    // Error en la solicitud
                    console.error(xhr.responseText);
                    // Mostrar una alerta de error con SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error!',
                        text: 'Error al actualizar la información del socio.',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }

        // Agregar un evento click al botón de guardar cambios
        $("#guardarCambiosBtn").click(function() {
            guardarCambios();
        });
    });