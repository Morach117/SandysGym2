<?php
// NOTA: Aquí es donde deberías incluir tu header.php si tienes uno
// include 'includes/header.php'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago Fallido - Sandys Gym</title>
    <style>
        /* (Aquí pegas tus estilos de .breadcrumb-section, .payment-form, .primary-btn, etc.) */
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; }
        .container { width: 90%; max-width: 1200px; margin: auto; }
        .row { display: flex; flex-wrap: wrap; }
        .col-lg-8 { width: 66.66%; }
        .offset-lg-2 { margin-left: 16.66%; }
        .text-center { text-align: center; }

        .membership-payment { padding: 60px 0; }
        .payment-form {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .payment-form h3 {
            margin-bottom: 20px;
            font-size: 32px;
            color: #dc3545; /* Rojo para error */
        }
        .payment-form p {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .primary-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 0 10px;
        }
    </style>
</head>
<body>

    <section class="breadcrumb-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb-text">
                        <h2>Pago Fallido</h2>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="membership-payment section_gap">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <div class="payment-form text-center">
                        <h3>Tu pago fue rechazado o cancelado.</h3>
                        <p>
                            Lamentablemente, no pudimos procesar tu pago. 
                            Por favor, revisa tus datos bancarios o intenta con un método de pago diferente.
                        </p>
                        
                        <a href="index.php?page=membership" class="primary-btn">Intentar de Nuevo</a>
                        <a href="index.php" class="primary-btn">Volver al Inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php
// NOTA: Aquí es donde deberías incluir tu footer.php si tienes uno
// include 'includes/footer.php'; 
?>
</body>
</html>