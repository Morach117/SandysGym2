<!-- Área de Banner -->
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.20/dist/sweetalert2.all.min.js"></script>
</head>

<section class="banner-area organic-breadcrumb">
	<div class="container">
		<div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
			<div class="col-first">
				<h1>Iniciar sesión</h1>
				<nav class="d-flex align-items-center">
					<a href="index.php">Inicio<span class="lnr lnr-arrow-right"></span></a>
				</nav>
			</div>
		</div>
	</div>
</section>
<!-- Fin del Área de Banner -->

<!--================Área de Caja de Inicio de Sesión =================-->
<section class="login_box_area section_gap">
	<div class="container">
		<div class="row">
			<div class="col-lg-6">
				<div class="login_box_img">
					<img class="img-fluid" src="./assets/img/login.jpg" alt="">
                    <div class="hover">
					<h4>¿Nuevo en nuestro gimnasio?</h4>
					<p>Estamos constantemente mejorando nuestras instalaciones y servicios para ofrecerte la mejor experiencia posible. ¡Únete a nosotros y transforma tu vida!</p>
					<a class="primary-btn" href="index.php?page=registration">Crear una Cuenta</a>
				</div>

				</div>
			</div>
			<div class="col-lg-6">
				<div class="login_form_inner">
					<h3>Inicia sesión para entrar</h3>
					<form class="row login_form"  method="post" id="adminLoginFrm">
						<div class="col-md-12 form-group">
							<input type="text" class="form-control" id="email" name="email" placeholder="Usuario" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Usuario'">
						</div>
						<div class="col-md-12 form-group">
							<input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Contraseña'">
						</div>
						<div class="col-md-12 form-group">
							<div class="creat_account">
								<input type="checkbox" id="f-option2" name="selector">
								<label for="f-option2">Mantenerme conectado</label>
							</div>
						</div>
						<div class="col-md-12 form-group">
							<button type="submit" class="primary-btn">Iniciar Sesión</button>
                            <a href="#" data-toggle="modal" data-target="#forgotPasswordModal">¿Olvidaste tu contraseña?</a>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>


<!-- Modal para restablecer contraseña -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="forgotPasswordModalLabel">Restablecer Contraseña</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="passwordResetRequestFrm">
                    <div class="form-group">
                        <label for="reset_email">Correo Electrónico</label>
                        <input type="email" class="form-control" id="reset_email" name="reset_email" placeholder="Correo Electrónico" required>
                    </div>
                    <button type="submit" class="primary-btn">Enviar enlace de restablecimiento</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="js/login.js"></script> <!-- importa el archivo ajax -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.20/dist/sweetalert2.all.min.js"></script>
<!--================ Fin del Área de Caja de Inicio de Sesión =================-->

<script>
$(document).ready(function() {
    $('#passwordResetRequestFrm').on('submit', function(event) {
        event.preventDefault();
        var email = $('#reset_email').val();

        $.ajax({
            url: './query/password_reset_request.php',
            method: 'POST',
            data: { email: email },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    Swal.fire(
                        '¡Éxito!',
                        'Se ha enviado un enlace de restablecimiento de contraseña a tu correo electrónico.',
                        'success'
                    );
                    $('#forgotPasswordModal').modal('hide');
                } else {
                    Swal.fire(
                        'Error',
                        data.message,
                        'error'
                    );
                }
            }
        });
    });
});

</script>