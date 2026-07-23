<style>
    .payment-failed-wrapper {
        background-color: #050505;
        color: #a1a1aa;
        font-family: 'Muli', sans-serif;
        padding: 40px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 200px);
        width: 100%;
    }

    .payment-wrapper {
        width: 100%;
        max-width: 600px;
        padding: 20px;
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .payment-card {
        background-color: #1a1a1a;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        padding: 40px 32px;
        text-align: center;
    }

    .icon-wrap {
        width: 80px; height: 80px; 
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: 24px; 
        font-size: 36px; 
        color: #fff;
        box-shadow: 0 10px 20px rgba(0,0,0,.3);
        background-color: #ef4444;
    }

    .payment-card h3 {
        font-family: 'Oswald', sans-serif;
        font-size: 28px;
        margin-bottom: 12px;
        font-weight: 700;
        color: #ffffff;
        text-transform: uppercase;
    }
    
    .payment-card p {
        font-size: 16px;
        line-height: 1.6;
        margin-bottom: 24px;
    }

    .btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 26px;
        border-radius: 50rem;
        font-family: 'Oswald', sans-serif;
        text-transform: uppercase;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
    }
    
    .btn i { margin-right: 8px; }

    .btn-primary { background-color: #ef4444; color: #fff; }
    .btn-primary:hover { background-color: #dc2626; transform: translateY(-2px); color: #fff; text-decoration: none; }

    .btn-secondary { background-color: #2a2a2a; color: #ffffff; }
    .btn-secondary:hover { background-color: #333; transform: translateY(-2px); color: #ffffff; text-decoration: none; }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="payment-failed-wrapper">
    <div class="payment-wrapper">
        <div class="payment-card">
            <div class="icon-wrap">
                <i class="fa-solid fa-xmark"></i>
            </div>
            <h3>Pago Fallido</h3>
            <p>
                Lamentablemente, no pudimos procesar tu pago. 
                Por favor, revisa tus datos bancarios o intenta con un método de pago diferente.
            </p>
            <div class="btns">
                <a href="index.php?page=user_pago_membresia" class="btn btn-primary"><i class="fa-solid fa-rotate-right"></i> Intentar de Nuevo</a>
                <a href="index.php?page=user_home" class="btn btn-secondary"><i class="fa-solid fa-house"></i> Volver al Inicio</a>
            </div>
        </div>
    </div>
</div>