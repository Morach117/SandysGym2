
<?php
    function obtener_conexion()
    {
        // Obtenemos el nombre del servidor actual
        $servidor = $_SERVER['SERVER_NAME'];

        // Si estamos en local (tu PC)
        if ($servidor === 'localhost' || $servidor === '127.0.0.1') {
            $conexion = mysqli_connect('localhost', 'root', '', 'dbs1756575');
        } 
        // Si estamos en el servidor de producción
        else {
            $conexion = mysqli_connect('db5002171142.hosting-data.io', 'dbu577361', 'Sandys_empresas_2', 'dbs1756575');
        }
        
        // Verificamos si hubo un error de conexión
        if( mysqli_connect_errno() ) {
            return false;
        }
        
        // Si todo salió bien, configuramos el charset y retornamos la conexión
        mysqli_set_charset( $conexion, 'utf8' );
        return $conexion;
    }
?>