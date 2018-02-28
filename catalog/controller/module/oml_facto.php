<?php

class ControllerModuleOmlFacto extends Controller {
//class ControllerCheckoutOmlFacto extends Controller {
	
	public function index() {
		$ruta = $this->request->get['route'];
		
		//var_dump($ruta);		
		//var_dump("MF");
		
		$data['heading_title'] = 'OML FACTO';
		if($ruta == "checkout/checkout") {		
			$this->load->language('checkout/checkout');
		
			$data['tipo_fe'] = $this->getConf('oml_facto_backend_tipo_fe');
			$data['tipo_fee'] = $this->getConf('oml_facto_backend_tipo_fee');
			$data['tipo_be'] = $this->getConf('oml_facto_backend_tipo_be');
			$data['tipo_bee'] = $this->getConf('oml_facto_backend_tipo_bee');
			
			$data['logged'] = $this->customer->isLogged();
					
			$data['text_checkout_option'] = sprintf($this->language->get('text_checkout_option'), 1);
			$data['text_checkout_payment_address'] = sprintf($this->language->get('text_checkout_payment_address'), 1);
					
			return $this->load->view('default/template/module/oml_facto.tpl', $data);
		}
		else if($ruta == "account/order/info") {
			$datos = $this->_account_order_info();
			foreach($datos as $key => $valor) {
				$data[$key] = $valor;
			}
			
			return $this->load->view('default/template/module/oml_facto_account_order_info.tpl', $data);
		}
				
	}
	
	protected function _checkout_success() {		
		
		var_dump("dasd");	
		/*
		unset($this->session->data['facto_frontend_tipo']);
		unset($this->session->data['facto_frontend_rut']);
		unset($this->session->data['facto_frontend_razonsocial']);
		unset($this->session->data['facto_frontend_giro']);
		*/
	}
	
	protected function getConf($text) {
	
		$resultado = $this->db->query("SELECT setting FROM ".DB_PREFIX."module WHERE code='oml_facto'");
		$arregloModulo = $resultado->row['setting'];
		$arregloModulo = str_replace("{", "", $arregloModulo);
		$arregloModulo = str_replace("}", "", $arregloModulo);
		$arregloModulo = explode(",", $arregloModulo);

		foreach($arregloModulo as $setting) {
			$temp = explode(":", $setting);
			$temp[0] = str_replace("\"", "", $temp[0]);
			$temp[1] = str_replace("\"", "", $temp[1]);
			if($temp[0]== $text){  return $temp[1]; }
		}

		return false;	
   }
   
	protected function _account_order_info() {		

	$datos = array();
		
		$query = "SELECT tipo, rut, razonsocial, giro FROM ".DB_PREFIX."oml_facto_cf WHERE order_id=".$this->request->get['order_id'];
		$resultado = $this->db->query($query);
		
		if($resultado->num_rows > 0) {
			if($resultado->row['tipo'] == 'fe') $datos['oml_frontend_tipo'] = "Factuta electr&oacute;nica";
			else if($resultado->row['tipo'] == 'fee') $datos['oml_frontend_tipo'] = "Factuta exenta electr&oacute;nica"; 
			else if($resultado->row['tipo'] == 'be') $datos['oml_frontend_tipo'] = "Boleta electr&oacute;nica"; 
			else if($resultado->row['tipo'] == 'bee') $datos['oml_frontend_tipo'] = "Boleta exenta electr&oacute;nica"; 
			
			$datos['oml_frontend_tipo_code'] = $resultado->row['tipo'];
			
			if($resultado->row['tipo'] == "fe" || $resultado->row['tipo']=="fee") {
				$datos['oml_frontend_rut'] = $resultado->row['rut'];
				$datos['oml_frontend_razonsocial'] = $resultado->row['razonsocial'];
				$datos['oml_frontend_giro'] = $resultado->row['giro'];
			}
		}
		
		//vemos lo de la factura para la orden
		$query = "SELECT estado, msg, enlace FROM ".DB_PREFIX."oml_facto_order_mp WHERE order_id=".$this->request->get['order_id']." LIMIT 0,1";
		$resultado = $this->db->query($query);
			
		if($resultado->num_rows > 0) {
			$datos['oml_status'] = $resultado->row['estado'];
			$datos['oml_msg'] = $resultado->row['msg'];
			$datos['oml_enlace'] = $resultado->row['enlace'];
		}
		else $datos['oml_status'] = false;
		
		return $datos;
	}
}
?>