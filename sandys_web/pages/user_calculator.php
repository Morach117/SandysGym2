<section class="breadcrumb-section set-bg" data-setbg="./assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Calculadora de IMC</h2>
                    <div class="bt-option">
                        <a href="./index.html">Inicio</a>
                        <a href="#">Páginas</a>
                        <span>Calculadora de IMC</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sección de Calculadora de IMC Inicio -->
<section class="bmi-calculator-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <div class="section-title chart-title">
                    <span>Revisa tu cuerpo</span>
                    <h2>GRÁFICA DE CALCULADORA DE IMC</h2>
                </div>
                <div class="chart-table">
                    <table>
                        <thead>
                            <tr>
                                <th>IMC</th>
                                <th>ESTADO DE PESO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="point">Menos de 18.5</td>
                                <td>Peso insuficiente</td>
                            </tr>
                            <tr>
                                <td class="point">18.5 - 24.9</td>
                                <td>Saludable</td>
                            </tr>
                            <tr>
                                <td class="point">25.0 - 29.9</td>
                                <td>Sobrepeso</td>
                            </tr>
                            <tr>
                                <td class="point">30.0 - y Más</td>
                                <td>Obeso</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-title chart-calculate-title">
                    <span>Revisa tu cuerpo</span>
                    <h2>CALCULA TU IMC</h2>
                </div>
                <div class="chart-calculate-form">
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Quis ipsum suspendisse ultrices gravida. Risus commodo viverra maecenas accumsan lacus vel facilisis.</p>
                    <form id="bmi-form">
                        <div class="row">
                            <div class="col-sm-6">
                                <input type="number" id="height" placeholder="Altura / cm" required>
                            </div>
                            <div class="col-sm-6">
                                <input type="number" id="weight" placeholder="Peso / kg" required>
                            </div>
                            <div class="col-sm-6">
                                <input type="number" id="age" placeholder="Edad">
                            </div>
                            <div class="col-sm-6">
                                <select id="gender" required>
                                    <option value="">Selecciona Sexo</option>
                                    <option value="male">Masculino</option>
                                    <option value="female">Femenino</option>
                                </select>
                            </div>
                            <div class="col-lg-12">
                                <button type="submit">Calcular</button>
                            </div>
                        </div>
                    </form>
                    <div id="result" style="margin-top: 20px;"></div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Sección de Calculadora de IMC Fin -->

<script>
    document.getElementById('bmi-form').addEventListener('submit', function(event) {
    event.preventDefault(); // Evita el envío del formulario

    // Obtener los valores de los campos
    const height = parseFloat(document.getElementById('height').value) / 100; // Convertir cm a metros
    const weight = parseFloat(document.getElementById('weight').value);
    const gender = document.getElementById('gender').value;

    if (isNaN(height) || isNaN(weight) || height <= 0 || weight <= 0) {
        alert('Por favor ingresa valores válidos para altura y peso.');
        return;
    }

    // Calcular el IMC
    const bmi = weight / (height * height);

    // Determinar el estado del peso
    let bmiCategory = '';
    if (bmi < 18.5) {
        bmiCategory = 'Peso insuficiente';
    } else if (bmi >= 18.5 && bmi < 24.9) {
        bmiCategory = 'Saludable';
    } else if (bmi >= 25.0 && bmi < 29.9) {
        bmiCategory = 'Sobrepeso';
    } else {
        bmiCategory = 'Obeso';
    }

    // Mostrar el resultado
    document.getElementById('result').innerHTML = `
        <h3>Resultado</h3>
        <p>Tu IMC es: ${bmi.toFixed(2)}</p>
        <p>Estado de peso: ${bmiCategory}</p>
    `;
});

</script>