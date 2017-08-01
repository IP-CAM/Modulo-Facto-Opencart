<?php

/*
 * INFORMACION DEL CRON
 * Este cron permite generar masivamente la facturación de todos los pedidos que
 * se encuentren en estado aceptado. Se puede utilizar para casos en que el checkout
 * está modificado por otros plugins y no se puede generar el documento de manera inmediata.
 * La limitación está en que al no poder modificar el checkout, no se maneja los datos
 * de facturación, lo cual limita la posibilidad a sólo emitir boletas y no facturas.
 * Adicionalmente, sólo se emitirán los pedidos que tengan como forma de pago, aquellas
 * que estén en modo AUTOMATICO en el administrador del módulo.
 */

//----------------------------
// CONFIGURACIÓN
//----------------------------
// Tipo de documento
// fe = fact electrónica
// fee = fact electrónica exenta
// be = boleta electrónica
// bee = boleta electrónica exenta
$CONFIG['tipodocumento'] = "bee";

// Estados en los que consideraremos que la orden ha sido aceptada para ser facturada
$CONFIG['estadoaceptado'] = array(5);

// Cantidad máxima a procesar por cada ejecución. Se recomienda máximo 50 por ejecución. En caso de que existan
// más de 50 documentos, puede volver a ejecutarse a los 5 minutos
$CONFIG['maximo_a_procesar'] = "50";


// Sólo se procesarán números de orden estrictamente mayores a este número. Debe usarse cuando la tienda ya tiene 
// ordenes anteriores, las cuales no deben facturarse
$CONFIG['orden_mayor_a'] = "0";

// Monedas aceptadas
$CONFIG['monedasaceptadas'] = array("'CLP'");

// Email en caso de errores
$CONFIG['emailerrores'] = "email@email.com";

// Mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// No mostrar errores
//error_reporting(E_NONE);
//ini_set('display_errors', 0);

// Tiempo máximo de ejecución en segundos
set_time_limit(60*10);


//----------------------------
// PROCESO
//----------------------------
$GLOBALS['logfile'] = dirname(__FILE__)."/logs/".date("Y-m-d-H-i-s").".txt";
$GLOBALS['errorpresente'] = false;

/*
 * $tipomensaje = "info", "alerta", "error", "exito"
 */
function logear($tipomensaje,$texto)
{
    file_put_contents($GLOBALS['logfile'], $texto."\n", FILE_APPEND);
    
    if ($tipomensaje == "info")
    {
        print "INFO: ";
    }    
    elseif ($tipomensaje == "exito")
    {
        print "EXITO: ";
    }
    elseif ($tipomensaje == "alerta")
    {
        print "ALERTA: ";
    }
    elseif ($tipomensaje == "error")
    {
        print "ERROR: ";
        $GLOBALS['errorpresente'] = true;
    }
    
    
    print $texto."<br/>";
}


// Conectamos a base de datos
require "../config.php";
$linkdb = mysqli_connect(DB_HOSTNAME,DB_USERNAME,DB_PASSWORD,DB_DATABASE);

if ($linkdb == false)
{
    logear("error","Error conexión MYSQL ".mysqli_connect_error());
    exit();
}

$query_create = "
					CREATE TABLE IF NOT EXISTS `".DB_PREFIX."oml_facto_mp` (
					  `code` VARCHAR(255),
					  `fact` VARCHAR(255),
					PRIMARY KEY (`code`)
					)";
$res = mysqli_query($linkdb, $query_create);

if ($res == false)
{
    logear("error","Error mysql: ".mysqli_error($linkdb));
    exit();
}

$query_create = "
					CREATE TABLE IF NOT EXISTS `".DB_PREFIX."oml_facto_cf` (
					  `order_id` int(11),
					  `tipo` VARCHAR(3),
					  `rut` VARCHAR(255),
					  `razonsocial` VARCHAR(255),
					  `giro` VARCHAR(255),
					  `hasShipping` int(1),
					  `shipping_cost` float,
					PRIMARY KEY (`order_id`)
					)";
$res = mysqli_query($linkdb, $query_create);

if ($res == false)
{
    logear("error","Error mysql: ".mysqli_error($linkdb));
    exit();
}

$query_create = "
					CREATE TABLE IF NOT EXISTS `".DB_PREFIX."oml_facto_order_mp` (
					  `order_id` INT,
						`msg` VARCHAR(255),
					  `fact` VARCHAR(255),
					  `estado` INT,
					  `enlace` VARCHAR(255),
					  `error` TEXT,
					PRIMARY KEY (`order_id`)
					)";
$res = mysqli_query($linkdb, $query_create);

if ($res == false)
{
    logear("error","Error mysql: ".mysqli_error($linkdb));
    exit();
}

// Obtengamos los datos de configuración del webservice
$sql = "SELECT oc.key, oc.value
            FROM ".DB_PREFIX."setting oc
                WHERE oc.key IN ('oml_facto_backend_user','oml_facto_backend_pass','oml_facto_backend_url')";

$settingsres = mysqli_query($linkdb, $sql);

if ($settingsres == false)
{
    logear("error","Error mysql: ".mysqli_error($linkdb));
    exit();
}

while ($settingsrow = mysqli_fetch_assoc($settingsres))
{
    $SETTINGS[$settingsrow['key']] = $settingsrow['value'];
}

// Busquemos los pedidos que estén en estado completado y que tienen modo de pago en automático
// y que no tienen un envío exitoso
$sql = "SELECT cf.tipo, cf.rut, cf.razonsocial, cf.giro, ord.order_id, ord.currency_id,
    payment_address_1, payment_city, telephone, email, mp.fact, ord.currency_value
    FROM
    ".DB_PREFIX."order ord
    INNER JOIN ".DB_PREFIX."oml_facto_mp mp
        ON mp.code = ord.payment_code
    LEFT JOIN ".DB_PREFIX."oml_facto_cf cf
        ON cf.order_id = ord.order_id
    LEFT JOIN ".DB_PREFIX."oml_facto_order_mp omp
        ON omp.order_id = ord.order_id AND enlace <> '' 
        WHERE
        mp.fact = 'auto' AND omp.order_id IS NULL
        AND ord.order_status_id IN (".implode(",",$CONFIG['estadoaceptado']).")
          AND ord.order_id > ".$CONFIG['orden_mayor_a']."
              AND ord.currency_code IN (".implode(",",$CONFIG['monedasaceptadas']).")
            ORDER BY date_added ASC
            LIMIT ".$CONFIG['maximo_a_procesar'];
$documentos = mysqli_query($linkdb, $sql);

if ($documentos == false)
{
    logear("error","Error mysql: ".mysqli_error($linkdb));
    exit();
}

logear("info",mysqli_num_rows($documentos)." pedidos encontrados");

// Generamos los documentos
while ($documento = mysqli_fetch_assoc($documentos))
{

    // Veamos si tenemos despacho
    $sql = "SELECT value
        FROM ".DB_PREFIX."order_total
            WHERE code = 'shipping'
            AND order_id = '".mysqli_real_escape_string($linkdb, $documento['order_id'])."'";
    $shippingres = mysqli_query($linkdb, $sql);
    
    if ($shippingres == false)
    {
        logear("error","Error mysql: ".mysqli_error($linkdb));
        exit();
    }
    
    if ($shippinginfo = mysqli_fetch_assoc($shippingres))
    {
        $tieneshipping = "1";
        $valorshipping = $shippinginfo['value'];
    }
    else
    {
        $tieneshipping = "0";
        $valorshipping = "0";
    }
    
    // Guardamos la información de datos de facturación
    $query_insert = "INSERT INTO `".DB_PREFIX."oml_facto_cf`
        (order_id, tipo, rut, razonsocial, giro, hasShipping, shipping_cost)
		VALUES
        ('".mysqli_real_escape_string($linkdb, $documento['order_id'])."',
            '".mysqli_real_escape_string($linkdb, $CONFIG['tipodocumento'])."',
            '".mysqli_real_escape_string($linkdb, $documento['rut'])."',
            '".mysqli_real_escape_string($linkdb, $documento['razonsocial'])."',
            '".mysqli_real_escape_string($linkdb, $documento['giro'])."',
            '".mysqli_real_escape_string($linkdb, $tieneshipping)."',
            '".mysqli_real_escape_string($linkdb, $valorshipping)."')";
    $res = mysqli_query($linkdb, $sql);
    
    if ($res == false)
    {
        logear("error","Error mysql: ".mysqli_error($linkdb));
        exit();
    }
    
    //***************************************************
    //******************** WEBSERVICE *******************
    $tipo_de_documento = $CONFIG['tipodocumento'];
    if($tipo_de_documento == "be") $tipo_dte = 39;
    else if($tipo_de_documento == "bee") $tipo_dte = 41;
    else if($tipo_de_documento == "fe") $tipo_dte = 33;
    else if($tipo_de_documento == "fee") $tipo_dte = 34;
    
    if($tipo_de_documento == "fe" || $tipo_de_documento=="fee") {
        $rut = $documento['rut'];
        $razonsocial = $documento['razonsocial'];
        $giro = $documento['giro'];
    }
    else {
        $rut = "";
        $razonsocial = "";
        $giro = "";
    }
    
    $fecha_emision = date('Y-m-d');
    $receptor_rut = str_replace(".", "", $rut);
    $receptor_razon = $razonsocial;
    $receptor_giro = $giro;
    
        
    $receptor_direccion = $documento['payment_address_1'];
    $receptor_comuna = $documento['payment_city'];
    $receptor_ciudad = $documento['payment_city'];
    $receptor_telefono = $documento['telephone'];
    $receptor_email = $documento['email'];
    
    $condiciones_pago = '0';
    $orden_compra_num = $documento['order_id'];
    $orden_compra_fecha = date('Y-m-d');
    
    $descuentorecargo_global_tipo= '0';
    $descuentorecargo_global_valor= '0';
    
    $total_exento = 0;
    $total_afecto = 0;
    
    // Veamos la información de tipo de cambio
    $sql = "SELECT value, decimal_place FROM
		    ".DB_PREFIX."currency
		    WHERE currency_id = '".$documento['currency_id']."'";
    $currencyres = mysqli_query($linkdb, $sql);
    
    if ($currencyres == false)
    {
        logear("error","Error mysql: ".mysqli_error($linkdb));
        exit();
    }
    
    $currencyinfo = mysqli_fetch_assoc($currencyres);
    
    //detalles
    $detalles = array();
    
    $sql = "SELECT quantity, model, name, price FROM ".DB_PREFIX."order_product
    WHERE order_id = '".mysqli_real_escape_string($linkdb, $documento['order_id'])."'";
    $itemsres = mysqli_query($linkdb, $sql);
        
    if ($itemsres == false)
    {
        logear("error","Error mysql: ".mysqli_error($linkdb));
        exit();
    }
    
    while ($item = mysqli_fetch_assoc($itemsres))
    {
        $detalle = array();
    
        $detalle['cantidad'] = (int)$item["quantity"];
        $detalle['unidad'] = "";
        $detalle['glosa'] = $item['name'];
        $detalle['monto_unitario'] = round( $item['price'] * $documento['currency_value'], $currencyinfo['decimal_place']);
        $detalle['descuentorecargo_monto'] = 0;
        $detalle['descuentorecargo_porcentaje'] = 0;
    
        if($tipo_dte == 34 || $tipo_dte == 41) {
            $detalle['exento_afecto'] = 0;
            $total_exento += $detalle['cantidad'] * $detalle['monto_unitario'];
        }
        else {
            $detalle['exento_afecto'] = 1;
            $total_afecto += $detalle['cantidad'] * $detalle['monto_unitario'];
        }
        	
        array_push($detalles, $detalle);
    }
    
    if ($tieneshipping == "1")
    {
        //GASTOS DE ENVIO
        $gastos_de_envio = round( $valorshipping * $documento['currency_value'], $currencyinfo['decimal_place']);
        	
        if($gastos_de_envio>0) {
            $detalle = array();
    
            $detalle['cantidad'] = 1;
            $detalle['unidad'] = "";
            $detalle['glosa'] = "Gastos de envío";
            $detalle['descuentorecargo_monto'] = 0;
            $detalle['descuentorecargo_porcentaje'] = 0;
            	
            if($tipo_dte == 34 || $tipo_dte == 41) {
                $detalle['monto_unitario'] = $gastos_de_envio;
                $detalle['exento_afecto'] = 0;
                $total_exento += $detalle['monto_unitario'];
            }
            else {
                $gastos_de_envio = round( ($gastos_de_envio / 1.19), 6);
                $detalle['monto_unitario'] = $gastos_de_envio;
                $detalle['exento_afecto'] = 1;
                $total_afecto += $detalle['monto_unitario'];
            }
            array_push($detalles, $detalle);
        }
    }
    
    
    // Veamos si hay otros recargos por forma de pago
    $sql = "SELECT value FROM ".DB_PREFIX."order_total
    WHERE order_id = '".mysqli_real_escape_string($linkdb, $documento['order_id'])."'
    AND code = 'payment_based_fee'";
    $recargores = mysqli_query($linkdb, $sql);
    
    while ($item = mysqli_fetch_assoc($recargores))
    {
        $detalle = array();
    
        $detalle['cantidad'] = 1;
        $detalle['unidad'] = "";
        $detalle['glosa'] = "Recargo forma de pago";
        $detalle['monto_unitario'] = round( $item['value'] * $documento['currency_value'], $currencyinfo['decimal_place']);
        $detalle['descuentorecargo_monto'] = 0;
        $detalle['descuentorecargo_porcentaje'] = 0;
    
        if($tipo_dte == 34 || $tipo_dte == 41) {
            $detalle['exento_afecto'] = 0;
            $total_exento += $detalle['cantidad'] * $detalle['monto_unitario'];
        }
        else {
            $detalle['exento_afecto'] = 1;
            $total_afecto += $detalle['cantidad'] * $detalle['monto_unitario'];
        }
         
        array_push($detalles, $detalle);
    }
    
    
    $total_iva = round(($total_afecto * 0.19), 6);
    $total_exento = round($total_exento);
    $total_afecto = round($total_afecto);
    $total_final = $total_iva + $total_afecto + $total_exento;
    
    require_once(DIR_SYSTEM.'../nusoap/nusoap.php');
    
    
    try {
        
        $url = $SETTINGS['oml_facto_backend_url'];
        $user = $SETTINGS['oml_facto_backend_user'];
        $pass = $SETTINGS['oml_facto_backend_pass'];
                	
        $client = new nusoap_client($url);
        $client->setCredentials($user, $pass, "basic");
    
        $cadena_xml = "
						<documento xsi:type='urn:emitir_dte'>
							<encabezado xsi:type='urn:encabezado'>
								<tipo_dte xsi:type='xsd:string'>".$tipo_dte."</tipo_dte>
								<fecha_emision xsi:type='xsd:date'>".$fecha_emision."</fecha_emision>
								<receptor_rut xsi:type='xsd:string'>".$receptor_rut."</receptor_rut>
								<receptor_razon xsi:type='xsd:string'><![CDATA[".utf8_decode($receptor_razon)."]]></receptor_razon>
								<receptor_direccion xsi:type='xsd:string'><![CDATA[".utf8_decode($receptor_direccion)."]]></receptor_direccion>
								<receptor_comuna xsi:type='xsd:string'><![CDATA[".utf8_decode($receptor_comuna)."]]></receptor_comuna>
								<receptor_ciudad xsi:type='xsd:string'><![CDATA[".utf8_decode($receptor_ciudad)."]]></receptor_ciudad>
    
								<receptor_telefono xsi:type='xsd:string'><![CDATA[".utf8_decode($receptor_telefono)."]]></receptor_telefono>
								<receptor_giro xsi:type='xsd:string'><![CDATA[".utf8_decode($receptor_giro)."]]></receptor_giro>
								<condiciones_pago xsi:type='xsd:string'><![CDATA[".utf8_decode($condiciones_pago)."]]></condiciones_pago>
								<receptor_email xsi:type='xsd:string'><![CDATA[".utf8_decode($receptor_email)."]]></receptor_email>
								<orden_compra_num xsi:type='xsd:string'>".$orden_compra_num."</orden_compra_num>
								<orden_compra_fecha xsi:type='xsd:date'>".$orden_compra_fecha."</orden_compra_fecha>
							</encabezado>
    
							<detalles xsi:type='urn:detalles'>";
        foreach ($detalles as $key => $detalle){
            $cadena_xml .= "
								<detalle xsi:type='urn:detalle'>
									<cantidad xsi:type='xsd:int'>".$detalle['cantidad']."</cantidad>
									<unidad xsi:type='xsd:string'>".$detalle['unidad']."</unidad>
									<glosa xsi:type='xsd:string'><![CDATA[".utf8_decode($detalle['glosa'])."]]></glosa>
									<monto_unitario xsi:type='xsd:decimal'>".$detalle['monto_unitario']."</monto_unitario>
									<exento_afecto xsi:type='xsd:boolean'>".$detalle['exento_afecto']."</exento_afecto>
								</detalle>";
        }
    
        $cadena_xml .= "
							</detalles>
                            <referencias xsi:type='urn:referencias'>
                               <referencia xsi:type='urn:referencia'>
                                  <docreferencia_tipo xsi:type='xsd:int'>802</docreferencia_tipo>
                                  <docreferencia_folio xsi:type='xsd:int'>".$documento['order_id']."</docreferencia_folio>
                                  <codigo_referencia xsi:type='xsd:int'>5</codigo_referencia>
                                  <descripcion xsi:type='xsd:string'>Num. Pedido</descripcion>
                               </referencia>
                            </referencias>
							<totales xsi:type='urn:totales'>
								<total_exento xsi:type='xsd:int'>".$total_exento."</total_exento>
								<total_afecto xsi:type='xsd:int'>".$total_afecto."</total_afecto>
								<total_iva xsi:type='xsd:int'>".$total_iva."</total_iva>
								<total_final xsi:type='xsd:int'>".$total_final."</total_final>
							</totales>
						</documento>";
        	
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = false;
        $response = $client->call("emitirDocumento", $cadena_xml);
        $err = $client->getError();
        	
        //************************************************
        //******************* LOG ************************
        $query_create = "
							CREATE TABLE IF NOT EXISTS `".DB_PREFIX."oml_facto_log` (
							  `id` int(11) NOT NULL AUTO_INCREMENT,
							  `fecha` date,
							  `request` text,
							  `response` text,
							  `estado_envio` int(11),
							PRIMARY KEY (`id`)
							)";
        $res = mysqli_query($linkdb, $query_create);
        
        if ($res == false)
        {
            logear("error","Error mysql: ".mysqli_error($linkdb));
            exit();
        }
        
        	
        $fecha_insert = mysqli_real_escape_string($linkdb,$fecha_emision);
        $request_insert = mysqli_real_escape_string($linkdb,$client->request);
        $response_insert = mysqli_real_escape_string($linkdb,print_r($response,true));
        $estado_insert = mysqli_real_escape_string($linkdb,$response["resultado"]["status"]);
        	
        $query_insert = "INSERT INTO `".DB_PREFIX."oml_facto_log`
            (fecha, request, response, estado_envio)
			VALUES
            ('".$fecha_insert."', '".$request_insert."', '".$response_insert."', '".$estado_insert."')";
        $res = mysqli_query($linkdb,$query_insert);
        	
        if ($res == false)
        {
            logear("error","Error mysql: ".mysqli_error($linkdb));
            exit();
        }
        
        if($err)
        {
            
            $query = "REPLACE INTO ".DB_PREFIX."oml_facto_order_mp
                (order_id, fact, estado, enlace)
                VALUES
                ('".$documento['order_id']."', '".$documento['fact']."', 1, '') ";
            $res = mysqli_query($linkdb,$query);
            
            if ($res == false)
            {
                logear("error","Error mysql: ".mysqli_error($linkdb));
                exit();
            }
            
            logear("error","ORDER: ".$documento['order_id']." - Ha ocurrido un error en comunicación webservice - ".$err);
        }
        else
        {
            
            if($response['enlaces']['dte_pdf'] != "")
            {
                $query = "REPLACE INTO ".DB_PREFIX."oml_facto_order_mp
                    (order_id, fact, estado, enlace)
                    VALUES
                    ('".$documento['order_id']."', '".$documento['fact']."', '".$estado_insert."', '".$response['enlaces']['dte_pdf']."') ";
                $res = mysqli_query($linkdb,$query);
                
                if ($res == false)
                {
                    logear("Error mysql: ".mysqli_error($linkdb));
                    exit();
                }
                
                logear("exito","ORDER: ".$documento['order_id']." - Documento generado ".$response['enlaces']['dte_pdf']." - ".$receptor_email);
            }
            else
            {
                
                $query = "REPLACE INTO ".DB_PREFIX."oml_facto_order_mp
                    (order_id, fact, estado, enlace)
                    VALUES
                    ('".$documento['order_id']."', '".$documento['fact']."', '".$estado_insert."', '') ";
                $res = mysqli_query($linkdb,$query);
                
                if ($res == false)
                {
                    logear("Error mysql: ".mysqli_error($linkdb));
                    exit();
                }
                
                logear("error","ORDER: ".$documento['order_id']." - Ha ocurrido un error al generar el documento - ".$response['resultado']['mensaje_error']);
            }
        }
    }
    catch (Exception $e)
    {
        $query = "REPLACE INTO ".DB_PREFIX."oml_facto_order_mp
            (order_id, fact, estado, enlace)
            VALUES
            ('".$documento['order_id']."', '".$documento['fact']."', 1, '') ";
        $res = mysqli_query($linkdb,$query);
        
        if ($res == false)
        {
            logear("error","Error mysql: ".mysqli_error($linkdb));
            exit();
        }
        
        logear("error","ORDER: ".$documento['order_id']." - Ha ocurrido un error al generar el documento");
    }
    
}

// Veamos si tenemos errores presentes
if ($GLOBALS['errorpresente'] == true)
{
    $mensaje = file_get_contents($GLOBALS['logfile']);
    
    mail($CONFIG['emailerrores'], "Error detectado CRON Tienda", $mensaje);
    
}