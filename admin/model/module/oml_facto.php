<?php
class ModelModuleOmlFacto extends Model {
	public function mostrarDatosFacto($order_id) {
		$retorno = array();
		$retorno['facto_tipo'] = '';
		$retorno['facto_rut'] = '';
		$retorno['facto_razonsocial'] = '';
		$retorno['facto_giro'] = '';
		$retorno['facto_status'] = '';
		$retorno['facto_msg'] = '';
		
		$query = "SELECT tipo, rut, razonsocial, giro FROM ".DB_PREFIX."oml_facto_cf WHERE order_id=".$order_id." LIMIT 0,1";
		$resultado = $this->db->query($query);
		
		if($resultado->num_rows > 0) {
			if($resultado->row['tipo'] == "fe" || $resultado->row['tipo']=="fee") {
				$retorno['facto_rut'] = $resultado->row['rut'];
				$retorno['facto_razonsocial'] = $resultado->row['razonsocial'];
				$retorno['facto_giro'] = $resultado->row['giro'];
			}
			
			if($resultado->row['tipo'] == "be"){
				$retorno['facto_tipo'] = 'Boleta electr&oacute;nica';
			}
			else if($resultado->row['tipo'] == "bee"){
				$retorno['facto_tipo'] = 'Boleta exenta electr&oacute;nica';
			}
			else if($resultado->row['tipo'] == "fe"){
				$retorno['facto_tipo'] = 'Factura electr&oacute;nica';
			}
			else if($resultado->row['tipo'] == "fee"){
				$retorno['facto_tipo'] = 'Factura exenta electr&oacute;nica';
			}
		}
		
		if ($retorno['facto_tipo'] == "")
		{
		    $sql = "SELECT oc.key, oc.value
                FROM ".DB_PREFIX."setting oc
                WHERE (oc.key = 'oml_facto_backend_tipo_be' AND oc.value = 'be')
                    OR oc.key = 'oml_facto_backend_tipo_bee' AND oc.value = 'bee'";
		    $resultado2 = $this->db->query($sql);
		     
		    if ($resultado2->row['value'] == "be")
		    {
		        $retorno['facto_tipo'] = 'Datos no ingresado. Se asume boleta electr&oacute;nica';
		    }
		    elseif ($resultado2->row['value'] == "bee")
		    {
		         
		        $retorno['facto_tipo'] = 'Datos no ingresado. Se asume boleta exenta electr&oacute;nica';
		    }
		    else
		    {
		        $retorno['facto_tipo'] = 'Datos no ingresado. No hay boleta o boleta exenta activa. No es posible facturar';
		    }
		}
		
		
		$query = "SELECT estado, msg, enlace FROM ".DB_PREFIX."oml_facto_order_mp WHERE order_id='".$order_id."'";
		$resultado = $this->db->query($query);
		if($resultado->num_rows > 0) {
			$retorno['facto_status'] = $resultado->row['estado'];
			$retorno['facto_msg'] = $resultado->row['msg'];
			$retorno['facto_enlace'] = $resultado->row['enlace'];
		}
		
		
		return $retorno;
	}
	
	public function facturar($order_id)
	{
	    
	    $sql = "SELECT value
        FROM ".DB_PREFIX."order_total
            WHERE code = 'shipping'
            AND order_id = '".$order_id."'";
	    $shippingres = $this->db->query($query);
	    
	    if ($shippingres->row['value'] != "")
	    {
	        $tieneshipping = "1";
	        $valorshipping = $shippingres->row['value'];
	    }
	    else
	    {
	        $tieneshipping = "0";
	        $valorshipping = "0";
	    }
	    
	    
		$query = "SELECT tipo, rut, razonsocial, giro, hasShipping, shipping_cost
		    FROM ".DB_PREFIX."oml_facto_cf WHERE order_id=".$order_id." LIMIT 0,1";
		$resultado = $this->db->query($query);
			
		
		$tipo_de_documento = $resultado->row['tipo'];
		
		if($tipo_de_documento == "be")
		{
		    $tipo_dte = 39;
		}
		else if($tipo_de_documento == "bee")
		{
		    $tipo_dte = 41;
		}
		else if($tipo_de_documento == "fe")
		{
		    $tipo_dte = 33;
		}
		else if($tipo_de_documento == "fee")
		{
		    $tipo_dte = 34;
		}
		else
		{
		    // No hay ningún tipo seleccionado
		    $sql = "SELECT oc.key, oc.value
            FROM ".DB_PREFIX."setting oc
            WHERE (oc.key = 'oml_facto_backend_tipo_be' AND oc.value = 'be')
                OR oc.key = 'oml_facto_backend_tipo_bee' AND oc.value = 'bee'";
		    $resultado2 = $this->db->query($sql);
		     
		    if ($resultado2->row['value'] == "be")
		    {
		        $tipo_dte = 39;
		    }
		    elseif ($resultado2->row['value'] == "bee")
		    {
		         
		        $tipo_dte = 41;
		    }
		    else
		    {
		        
		    }
		    
		}
		
		if($tipo_de_documento == "fe" || $tipo_de_documento=="fee") {
			$rut = $resultado->row['rut'];
			$razonsocial = $resultado->row['razonsocial'];
			$giro = $resultado->row['giro'];
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
		
		$this->load->model('sale/order');
		$order = $this->model_sale_order->getOrder($order_id);
					
		$receptor_direccion = $order['payment_address_1'];
		$receptor_comuna = $order['payment_custom_field'][1];
		$receptor_ciudad = $order['payment_city'];
		$receptor_telefono = $order['telephone'];
		$receptor_email = $order['email'];
		
		$condiciones_pago = '0';
		$orden_compra_num = $order_id;
		$orden_compra_fecha = date('Y-m-d');

		$descuentorecargo_global_tipo= '0';
		$descuentorecargo_global_valor= '0';
				
		$total_exento = 0;
		$total_afecto = 0;
	
		// Veamos la información de tipo de cambio
		$sql = "SELECT value, decimal_place FROM
		    ".DB_PREFIX."currency
		    WHERE currency_id = '".$order['currency_id']."'";
		$currencyres = $this->db->query($sql);
		
		
		//detalles
		$detalles = array();
		$items = $this->model_sale_order->getOrderProducts($order_id);
							
		foreach ($items as $item)
		{
		    
			$detalle = array();

			$detalle['cantidad'] = (int)$item["quantity"];
			$detalle['unidad'] = "";
			$detalle['glosa'] = $item['name'];
			$detalle['monto_unitario'] = round( $item['price'] * $currencyres->row['value'], $currencyres->row['decimal_place']);
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
		
		if ($tieneshipping == 1) {			
			//GASTOS DE ENVIO
			$gastos_de_envio = round( $valorshipping * $currencyres->row['value'], $currencyres->row['decimal_place']);
			
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
		
		$total_iva = round(($total_afecto * 0.19), 6);
		$total_exento = round($total_exento);
		$total_afecto = round($total_afecto);
		$total_final = $total_iva + $total_afecto + $total_exento;
		
		require_once(DIR_SYSTEM.'../nusoap/nusoap.php');
		
		try {
			$url = $this->config->get('oml_facto_backend_url');
			$user = $this->config->get('oml_facto_backend_user');
			$pass = $this->config->get('oml_facto_backend_pass');	
			
			$client = new nusoap_client($url);
					$client->setCredentials($user, $pass, "basic");
					
					$cadena_xml = "
					<documento xsi:type='urn:emitir_dte'>
						<encabezado xsi:type='urn:encabezado'>
							<tipo_dte xsi:type='xsd:string'>".$tipo_dte."</tipo_dte>
							<fecha_emision xsi:type='xsd:date'>".$fecha_emision."</fecha_emision>
							<receptor_rut xsi:type='xsd:string'>".$receptor_rut."</receptor_rut>
							<receptor_razon xsi:type='xsd:string'>".htmlspecialchars($receptor_razon)."</receptor_razon>
							<receptor_direccion xsi:type='xsd:string'>".htmlspecialchars($receptor_direccion)."</receptor_direccion>
							<receptor_comuna xsi:type='xsd:string'>".htmlspecialchars($receptor_comuna)."</receptor_comuna>
							<receptor_ciudad xsi:type='xsd:string'>".htmlspecialchars($receptor_ciudad)."</receptor_ciudad>

							<receptor_telefono xsi:type='xsd:string'>".htmlspecialchars($receptor_telefono)."</receptor_telefono>
							<receptor_giro xsi:type='xsd:string'>".htmlspecialchars($receptor_giro)."</receptor_giro>
							<condiciones_pago xsi:type='xsd:string'>".htmlspecialchars($condiciones_pago)."</condiciones_pago>
							<receptor_email xsi:type='xsd:string'>".htmlspecialchars($receptor_email)."</receptor_email>
							<orden_compra_num xsi:type='xsd:string'>".$orden_compra_num."</orden_compra_num>
							<orden_compra_fecha xsi:type='xsd:date'>".$orden_compra_fecha."</orden_compra_fecha>
						</encabezado>
									
						<detalles xsi:type='urn:detalles'>";
			foreach ($detalles as $key => $detalle){
				$cadena_xml .= "
							<detalle xsi:type='urn:detalle'>
								<cantidad xsi:type='xsd:int'>".$detalle['cantidad']."</cantidad>
								<unidad xsi:type='xsd:string'>".substr($detalle['unidad'], 0 , 4)."</unidad>
								<glosa xsi:type='xsd:string'>".htmlspecialchars($detalle['glosa'])."</glosa>
								<monto_unitario xsi:type='xsd:decimal'>".$detalle['monto_unitario']."</monto_unitario>
								<exento_afecto xsi:type='xsd:boolean'>".$detalle['exento_afecto']."</exento_afecto>
							</detalle>";
			}

			$cadena_xml .= "
						</detalles>
					
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
					
					
					
					$status = $response['resultado']['status'];
					$msg = '';
					$enlace = '';
					
					if($err) {
						$status = 1;
						$msg = $err;
					}
					else if($status==0) {
						$enlace = $response['enlaces']['dte_pdf'];
					}
					else if($status==1) {
						$msg = $response['resultado']['mensaje_error'];
					}
					else if($status==2) {
						$msg = $response['resultado']['mensaje_error'];
						$enlace = $response['enlaces']['dte_pdf'];
					}
					
					
					$query = "REPLACE INTO ".DB_PREFIX."oml_facto_order_mp 
					    (order_id,estado,msg,enlace)
					    VALUES
					    ('".$order_id."','".$status."','".$msg."','".$enlace."')";
					$this->db->query($query);
		}
		catch (Exception $e)
		{
		    $query = "REPLACE INTO ".DB_PREFIX."oml_facto_order_mp
					    (order_id,estado,msg,enlace)
					    VALUES
					    ('".$order_id."','1','".print_r($e,true)."','')";
		    $this->db->query($query);
		}
	}
	
	protected function getConf($text) {
	
		$resultado = $this->db->query("SELECT setting FROM ".DB_PREFIX."module WHERE code='oml_facto'");
		if($resultado->num_rows == 0) return '';
		else return json_decode($resultado->row['setting'])->$text;
   }
}