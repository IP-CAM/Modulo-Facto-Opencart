<?php
class ControllerOmlFactoDatosFacto extends Controller {
	public function index() {
		
	}

	public function save() {
		
		$json = array();
	
		$json['tipo'] = $_POST['tipo'];
		$this->session->data['facto_frontend_tipo'] = $_POST['tipo'];
		
		if($_POST['tipo'] == "fe" || $_POST['tipo'] == "fee") {
			$json['rut'] = $_POST['rut'];
			$this->session->data['facto_frontend_rut'] = $_POST['rut'];
			
			$json['razonsocial'] = $_POST['razonsocial'];
			$this->session->data['facto_frontend_razonsocial'] = $_POST['razonsocial'];
			
			$json['giro'] = $_POST['giro'];
			$this->session->data['facto_frontend_giro'] = $_POST['giro'];
		}
				
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}