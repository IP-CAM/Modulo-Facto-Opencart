<?php
class ControllerCheckoutSuccess extends Controller {
	public function index() {
		$facto_arreglo = $this->oml_confirm();
		foreach($facto_arreglo as $key => $valor){
			$data[$key] = $valor;
		}
		
		
		$this->load->language('checkout/success');

		if (isset($this->session->data['order_id'])) {
			$this->cart->clear();

			// Add to activity log
			$this->load->model('account/activity');

			if ($this->customer->isLogged()) {
				$activity_data = array(
					'customer_id' => $this->customer->getId(),
					'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
					'order_id'    => $this->session->data['order_id']
				);

				$this->model_account_activity->addActivity('order_account', $activity_data);
			} else {
				$activity_data = array(
					'name'     => $this->session->data['guest']['firstname'] . ' ' . $this->session->data['guest']['lastname'],
					'order_id' => $this->session->data['order_id']
				);

				$this->model_account_activity->addActivity('order_guest', $activity_data);
			}

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_success'),
			'href' => $this->url->link('checkout/success')
		);

		$data['heading_title'] = $this->language->get('heading_title');

		if ($this->customer->isLogged()) {
			$data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
		} else {
			$data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		}

		$data['button_continue'] = $this->language->get('button_continue');

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}
	
	protected function oml_confirm() {	
		$this->load->model('checkout/order');
		$this->load->model('account/order');
		
		//guardamos los nuevos datos en la bd
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
			$this->db->query($query_create);
			
			$order_id_insert = $this->db->escape($this->session->data['order_id']);
			$tipo_insert = $this->db->escape($this->session->data['facto_frontend_tipo']);
			$rut_insert = $this->db->escape($this->session->data['facto_frontend_rut']);
			$razonsocial_insert = $this->db->escape($this->session->data['facto_frontend_razonsocial']);
			$giro_insert = $this->db->escape($this->session->data['facto_frontend_giro']);
			if($this->cart->hasShipping()) $hasShipping_insert = 1;
			else $hasShipping_insert = 0;
			$shipping_cost_insert = round( ($this->session->data['shipping_method']['cost']), 6);

						
			$query_insert = "INSERT INTO `".DB_PREFIX."oml_facto_cf` (order_id, tipo, rut, razonsocial, giro, hasShipping, shipping_cost) 
							VALUES('".$order_id_insert."', '".$tipo_insert."','".$rut_insert."','".$razonsocial_insert."','".$giro_insert."', ".$hasShipping_insert.", ".$shipping_cost_insert.")";
			$this->db->query($query_insert);
			
			//creamos la tabla para los MP
			$query_create = "
					CREATE TABLE IF NOT EXISTS `".DB_PREFIX."oml_facto_mp` (
					  `code` VARCHAR(255),
					  `fact` VARCHAR(255),
					PRIMARY KEY (`code`)
					)";
			$this->db->query($query_create);

			//vemos la configuración que tiene el método de pago
			$query = "SELECT fact FROM ".DB_PREFIX."oml_facto_mp WHERE code='".$this->session->data['payment_method']['code']."' LIMIT 0,1";
			$resultado = $this->db->query($query);
			
			$fact = "manual";
			
			if($resultado->num_rows > 0) {
				$fact = $resultado->row['fact'];
			}
			
			//creamos una tabla para guardar la órden y el tipo de facturación
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
			$this->db->query($query_create);
						
			if($fact == "auto") 
			{
				//***************************************************
				//******************** WEBSERVICE *******************
				$tipo_de_documento = $this->session->data['facto_frontend_tipo'];
				if($tipo_de_documento == "be") $tipo_dte = 39;
				else if($tipo_de_documento == "bee") $tipo_dte = 41;
				else if($tipo_de_documento == "fe") $tipo_dte = 33;
				else if($tipo_de_documento == "fee") $tipo_dte = 34;

				if($tipo_de_documento == "fe" || $tipo_de_documento=="fee") {
					$rut = $this->session->data['facto_frontend_rut'];
					$razonsocial = $this->session->data['facto_frontend_razonsocial'];
					$giro = $this->session->data['facto_frontend_giro'];
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

				$order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
						
				$receptor_direccion = $order['payment_address_1'];
				$receptor_comuna = $order['payment_city'];
				$receptor_ciudad = $order['payment_city'];
				$receptor_telefono = $order['telephone'];
				$receptor_email = $order['email'];

				$condiciones_pago = '0';
				$orden_compra_num = $this->session->data['order_id'];
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
				$items = $this->model_account_order->getOrderProducts($this->session->data['order_id']);
						
				foreach ($items as $item) {
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

				if ($this->cart->hasShipping()) {			
					//GASTOS DE ENVIO
					$gastos_de_envio = round( $this->session->data['shipping_method']['cost'] * $currencyres->row['value'], $currencyres->row['decimal_place']);
					
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
					$url = $this->getConf('oml_facto_backend_url');
					$user = $this->getConf('oml_facto_backend_user');
					$pass = $this->getConf('oml_facto_backend_pass');
					
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
									<unidad xsi:type='xsd:string'>".$detalle['unidad']."</unidad>
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
					$this->db->query($query_create);
					
					$fecha_insert = $this->db->escape($fecha_emision);
					$request_insert = $this->db->escape($client->request);
					$response_insert = $this->db->escape(print_r($response,true));
					$estado_insert = $this->db->escape($response["resultado"]["status"]);
					
					$query_insert = "INSERT INTO `".DB_PREFIX."oml_facto_log` (fecha, request, response, estado_envio) 
									VALUES('".$fecha_insert."', '".$request_insert."', '".$response_insert."', '".$estado_insert."')";
					$this->db->query($query_insert);
					
					if($err) {
						$query = "INSERT INTO ".DB_PREFIX."oml_facto_order_mp (order_id, fact, estado, enlace)
						    VALUES
						    ('".$this->session->data['order_id']."', '".$fact."', 1, '') ";
						$this->db->query($query);
						return array('facto_error' => 'Ha ocurrido un error al generar el documento.');
					}
					else {
						if($response['enlaces']['dte_pdf'] != "")
						{	
							$query = "INSERT INTO ".DB_PREFIX."oml_facto_order_mp (order_id, fact, estado, enlace)
							    VALUES
							    ('".$this->session->data['order_id']."', '".$fact."', '".$estado_insert."', '".$response['enlaces']['dte_pdf']."') ";
							$this->db->query($query);
							return array('facto_enlace' => $response['enlaces']['dte_pdf']);
						}
						else
						{
						    $query = "INSERT INTO ".DB_PREFIX."oml_facto_order_mp (order_id, fact, estado, enlace)
						    VALUES
						    ('".$this->session->data['order_id']."', '".$fact."', '".$estado_insert."', '') ";
						    $this->db->query($query);
						    return array('facto_error' => 'Ha ocurrido un error al generar el documento.');
						}
					}
				}	
				catch (Exception $e)
				{
					$query = "INSERT INTO ".DB_PREFIX."oml_facto_order_mp (order_id, fact, estado, enlace)
					    VALUES
					    ('".$this->session->data['order_id']."', '".$fact."', 1, '') ";
					$this->db->query($query);
					return array('facto_error' => '- Ha ocurrido un error al generar el documento.');
				}
			}
			else if($fact == "manual") {
				//si es manual simplemente creamos la orden
				$query = "INSERT INTO ".DB_PREFIX."oml_facto_order_mp (order_id, msg, estado, enlace)
				    VALUES
				    ('".$this->session->data['order_id']."', '', 1, '') ";
				
				$this->db->query($query);
				return array('facto_fact' => 'manual');
			}
				
	}
	
	protected function getConf($text) {
	
		$resultado = $this->db->query("SELECT setting FROM ".DB_PREFIX."module WHERE code='oml_facto'");
		return json_decode($resultado->row['setting'])->$text;	
   }
}


