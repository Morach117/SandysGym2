	<!-- start footer Area -->
	<footer class="footer-area section_gap">
		<div class="container">
			<div class="row">
				<div class="col-lg-3 col-md-6 col-sm-6">
					<div class="single-footer-widget">
						<h6>Sobre Nosotros</h6>
						<p>
							Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore
							magna aliqua.
						</p>
					</div>
				</div>
				<div class="col-lg-4 col-md-6 col-sm-6">
					<div class="single-footer-widget">
						<h6>Boletín</h6>
						<p>Mantente actualizado con nuestras últimas noticias y promociones.</p>
						<div class="" id="mc_embed_signup">
							<form target="_blank" novalidate="true" action="#" method="get" class="form-inline">
								<div class="d-flex flex-row">
									<input class="form-control" name="EMAIL" placeholder="Ingresa tu correo electrónico" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Ingresa tu correo electrónico'" required="" type="email">
									<button class="click-btn btn btn-default"><i class="fa fa-long-arrow-right" aria-hidden="true"></i></button>
									<div style="position: absolute; left: -5000px;">
										<input name="b_36c4fd991d266f23781ded980_aefe40901a" tabindex="-1" value="" type="text">
									</div>
								</div>
								<div class="info"></div>
							</form>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-6 col-sm-6">
					<div class="single-footer-widget mail-chimp">
						<h6 class="mb-20">Feed de Instagram</h6>
						<ul class="instafeed d-flex flex-wrap">
							<li><img src="img/gym1.jpg" alt=""></li>
							<li><img src="img/gym2.jpg" alt=""></li>
							<li><img src="img/gym3.jpg" alt=""></li>
							<li><img src="img/gym4.jpg" alt=""></li>
							<li><img src="img/gym5.jpg" alt=""></li>
							<li><img src="img/gym6.jpg" alt=""></li>
							<li><img src="img/gym7.jpg" alt=""></li>
							<li><img src="img/gym8.jpg" alt=""></li>
						</ul>
					</div>
				</div>
				<div class="col-lg-2 col-md-6 col-sm-6">
					<div class="single-footer-widget">
						<h6>Síguenos</h6>
						<p>Conéctate con nosotros en redes sociales</p>
						<div class="footer-social d-flex align-items-center">
							<a href="#"><i class="fa fa-facebook"></i></a>
							<a href="#"><i class="fa fa-twitter"></i></a>
							<a href="#"><i class="fa fa-instagram"></i></a>
							<a href="#"><i class="fa fa-linkedin"></i></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</footer>
	
	
	<!-- End footer Area -->

	<script>
document.addEventListener("DOMContentLoaded", function() {
    var userDropdown = document.getElementById("userDropdown");
    var userMenu = document.getElementById("userMenu");
    
    // Agregar un evento clic al icono de usuario
    userDropdown.addEventListener("click", function(event) {
        event.stopPropagation(); // Evitar que el clic se propague al contenedor del menú
        
        // Alternar la clase 'show' en el menú desplegable para mostrar u ocultar el menú
        userMenu.classList.toggle("show");
    });
    
    // Cerrar el menú desplegable cuando el usuario haga clic en cualquier parte de la página
    document.addEventListener("click", function(event) {
        if (!userDropdown.contains(event.target)) {
            userMenu.classList.remove("show");
        }
    });
});
</script>

	<script src="./assets/js/vendor/jquery-2.2.4.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4"
	 crossorigin="anonymous"></script>
	<script src="./assets/js/vendor/bootstrap.min.js"></script>
	<script src="./assets/js/jquery.ajaxchimp.min.js"></script>
	<script src="./assets/js/jquery.nice-select.min.js"></script>
	<script src="./assets/js/jquery.sticky.js"></script>
	<script src="./assets/js/nouislider.min.js"></script>
	<script src="./assets/js/countdown.js"></script>
	<script src="./assets/js/jquery.magnific-popup.min.js"></script>
	<script src="./assets/js/owl.carousel.min.js"></script>
	<!--gmaps Js-->
	<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjCGmQ0Uq4exrzdcL6rvxywDDOvfAu6eE"></script>
	<script src="./assets/js/gmaps.min.js"></script>
	<script src="./assets/js/main.js"></script>
</body>

</html>