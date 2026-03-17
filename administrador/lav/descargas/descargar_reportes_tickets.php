<?php
    ob_end_clean();
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
    
    // Detectar qué botón presionó el usuario (basico o completo)
    $tipo_pdf       = isset($_GET['d']) ? $_GET['d'] : 'pdf_basico';
    
    $datos          = obtene_info_bd( $busqueda, $año, $mes_evaluar, $cliente );
    
    function formato_texto($texto) {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }

    // =========================================================
    // OPCIÓN 1: PDF BÁSICO (Lo que pidió el cliente)
    // =========================================================
    if( $tipo_pdf == 'pdf_basico' ) {
        
        $pdf = new FPDF('P', 'mm', 'A4'); // P = Portrait (Vertical)
        $pdf->AddPage();
        
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, formato_texto('Reporte de Inventario de Folios'), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 16);
        $pdf->Cell(0, 10, formato_texto('Generado el: ' . $fecha_mov), 0, 1, 'C');
        $pdf->Ln(10); 
        
        if( $datos ) {
            $columna = 0;
            foreach( $datos as $detalle ) {
                $pdf->Cell(60, 10, formato_texto($detalle['folio_desc']), 0, 0, 'C');
                $columna++;
                if( $columna == 3 ) {
                    $pdf->Ln(10);
                    $columna = 0;
                }
            }
        } else {
            $pdf->Cell(0, 10, formato_texto('No se encontraron folios.'), 0, 1, 'C');
        }
        
        $pdf->Output('I', "Inventario_Basico.pdf");
        exit;
    } 
    // =========================================================
    // OPCIÓN 2: PDF COMPLETO (La versión profesional mejorada)
    // =========================================================
    else if( $tipo_pdf == 'pdf_completo' ) {
        
        $pdf = new FPDF('L', 'mm', 'A4'); // L = Landscape (Horizontal) para que quepa la tabla
        $pdf->AddPage();
        
        // Título Completo
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, formato_texto('Reporte Detallado de Tickets y Movimientos'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, formato_texto('Fecha de generación: ' . $fecha_mov), 0, 1, 'C');
        $pdf->Ln(5); 

        // Encabezados de la Tabla (Gris claro)
        $pdf->SetFillColor(220, 220, 220);
        $pdf->SetFont('Arial', 'B', 10);
        
        // Celdas: (Ancho, Alto, Texto, Borde, Salto, Alineación, Relleno)
        $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Folio', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Fecha Mov.', 1, 0, 'C', true);
        $pdf->Cell(70, 8, 'Cliente', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Efectivo', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Por Cobrar', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Total', 1, 0, 'C', true);
        $pdf->Cell(55, 8, 'Status', 1, 1, 'C', true); // El "1" del final baja al siguiente renglón
        
        // Contenido de la Tabla
        $pdf->SetFont('Arial', '', 9);
        
        if( $datos ) {
            $i = 1;
            $tot_efectivo = 0;
            $tot_cobrar = 0;
            $tot_general = 0;
            
            foreach( $datos as $d ) {
                // Acortar el nombre del cliente si es muy largo para que no rompa la tabla
                $nombre_cliente = substr($d['cliente'], 0, 35);
                
                $pdf->Cell(10, 8, $i, 1, 0, 'C');
                $pdf->Cell(25, 8, formato_texto($d['folio_desc']), 1, 0, 'C');
                $pdf->Cell(40, 8, formato_texto($d['primer_m']), 1, 0, 'C');
                $pdf->Cell(70, 8, formato_texto($nombre_cliente), 1, 0, 'L');
                $pdf->Cell(25, 8, '$' . number_format($d['efectivo'], 2), 1, 0, 'R');
                $pdf->Cell(25, 8, '$' . number_format($d['credito'], 2), 1, 0, 'R');
                $pdf->Cell(25, 8, '$' . number_format($d['total'], 2), 1, 0, 'R');
                $pdf->Cell(55, 8, formato_texto($d['status_desc']), 1, 1, 'C');
                
                // Sumar totales
                $tot_efectivo += $d['efectivo'];
                $tot_cobrar += $d['credito'];
                $tot_general += $d['total'];
                
                $i++;
            }
            
            // Fila de Totales Generales al pie de la tabla
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(145, 8, 'TOTALES GLOBALES:', 1, 0, 'R', true);
            $pdf->Cell(25, 8, '$' . number_format($tot_efectivo, 2), 1, 0, 'R', true);
            $pdf->Cell(25, 8, '$' . number_format($tot_cobrar, 2), 1, 0, 'R', true);
            $pdf->Cell(25, 8, '$' . number_format($tot_general, 2), 1, 0, 'R', true);
            $pdf->Cell(55, 8, '', 1, 1, 'C', true);
            
        } else {
            $pdf->Cell(275, 10, formato_texto('No se encontraron registros para generar la tabla.'), 1, 1, 'C');
        }
        
        $pdf->Output('I', "Inventario_Completo.pdf");
        exit;
    }
?>