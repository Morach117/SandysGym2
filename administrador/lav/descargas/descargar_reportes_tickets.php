<?php
    ob_end_clean();
    // Apagamos los errores en pantalla para que no rompan el PDF
    error_reporting(E_ALL);
    ini_set('display_errors', FALSE);
    ini_set('display_startup_errors', FALSE);
    ini_set('memory_limit','512M');
    set_time_limit(95);

    if (PHP_SAPI == 'cli')
        die('This example should only be run from a Web Browser');
    
    require_once '../../funciones_globales/fpdf/fpdf.php';
    
    $año            = request_var( 'anio', date( 'Y' ) );
    $busqueda       = request_var( 'busqueda', '' );
    $mes_evaluar    = request_var( 'mes_evaluar', '' );
    $cliente        = request_var( 'nombre_cliente', '' );
    $fecha_mov      = date( 'd/m/Y h:i A' ); 
    
    $datos          = obtene_info_bd( $busqueda, $año, $mes_evaluar, $cliente );
    
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Función auxiliar compatible con PHP 8 para los acentos
    function formato_texto($texto) {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }

    // --- ENCABEZADO ---
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, formato_texto('Reporte de Inventario de Folios'), 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 16);
    $pdf->Cell(0, 10, formato_texto('Generado el: ' . $fecha_mov), 0, 1, 'C');
    $pdf->Ln(10); 
    
    // --- LISTA DE FOLIOS (3 COLUMNAS) ---
    if( $datos )
    {
        $columna = 0;
        foreach( $datos as $detalle )
        {
            $pdf->Cell(60, 10, formato_texto($detalle['folio_desc']), 0, 0, 'C');
            
            $columna++;
            
            if( $columna == 3 )
            {
                $pdf->Ln(10);
                $columna = 0;
            }
        }
    }
    else
    {
        $pdf->Cell(0, 10, formato_texto('No se encontraron folios en esta búsqueda.'), 0, 1, 'C');
    }
    
    $pdf->Output('I', "Inventario_Tickets.pdf");
    exit;
?>