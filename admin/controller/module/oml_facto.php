<?php
class ControllerModuleOmlFacto extends Controller {
	private $error = array();
	private $hayPost = false;	
	
	public function index() {
		$this->load->language('module/oml_facto');
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
		$this->load->model('extension/module');
				
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate())
		{
			$this->hayPost = true;
			$this->request->post['name'] = "oml_facto";
			$this->request->post['module_description'] = "Modulo de integracion con FACTO";
			$this->request->post['status'] = 1;
			
			if (!isset($this->request->post['oml_facto_backend_tipo_fe']))
			{
			    $this->request->post['oml_facto_backend_tipo_fe'] = "no";
			}
			if (!isset($this->request->post['oml_facto_backend_tipo_fee']))
			{
			    $this->request->post['oml_facto_backend_tipo_fee'] = "no";
			}
			if (!isset($this->request->post['oml_facto_backend_tipo_be'])) 
			{
			    $this->request->post['oml_facto_backend_tipo_be'] = "no";
			}
			if (!isset($this->request->post['oml_facto_backend_tipo_bee']))
			{
			    $this->request->post['oml_facto_backend_tipo_bee'] = "no";
			}

			//MP
			$method_data = array();
			$this->load->model('extension/extension');
			$results = $this->model_extension_extension->getInstalled('payment');
		
			foreach ($results as $result) {
				if ($this->config->get($result . '_status')) {
					$method_data[] = $result;
				}
			}
		
			foreach($method_data as $metodo) {
				$fact = $this->request->post['oml_'.$metodo.'_fact'];
				unset($this->request->post['oml_'.$metodo.'_fact']);
				
				$query = "INSERT INTO ".DB_PREFIX."oml_facto_mp 
							(code, fact) VALUES('".$metodo."', '".$fact."') 
							ON DUPLICATE KEY UPDATE fact='".$fact."'; ";
							
				$this->db->query($query);
			}
			
			// Guardemos los datos de los tipos de documento
			
			
			if (!isset($this->request->get['module_id']))
			{		
				$arregloModulo = $this->model_extension_module->getModulesByCode('oml_facto');
				if(!$arregloModulo)
				{
					$this->model_setting_setting->editSetting('oml_facto', $this->request->post);				
					$this->model_extension_module->addModule('oml_facto', $this->request->post);
					
					$arregloModulo = $this->model_extension_module->getModulesByCode('oml_facto');
					$arregloModulo = $arregloModulo[0];
					
					$this->db->query("INSERT INTO " . DB_PREFIX . "layout SET name='oml_facto_order_info_layout'");
					$inserted_layout_id = $this->db->getLastId();
					
					$this->db->query("INSERT INTO " . DB_PREFIX . "layout_route SET 
										layout_id='".$inserted_layout_id."',
										store_id='".$this->config->get('config_store_id')."',
										route='account/order/info'
					");
					
					$this->db->query("
                              INSERT INTO " . DB_PREFIX . "layout_module SET
                                layout_id = '7',
                                code = 'oml_facto.".$arregloModulo['module_id']."',
                                position = 'content_bottom',
                                sort_order = '999'
                            ");
							
					$this->db->query("
                              INSERT INTO " . DB_PREFIX . "layout_module SET
                                layout_id = '".$inserted_layout_id."',
                                code = 'oml_facto.".$arregloModulo['module_id']."',
                                position = 'content_bottom',
                                sort_order = '999'
                            ");
							
					$this->session->data['success'] = $this->language->get('Se ha creado un modulo para FACTO.');
				} else {
					$this->session->data['success'] = $this->language->get('Se modificaron los datos.');
					$this->model_extension_module->editModule($arregloModulo[0]["module_id"], $this->request->post);
				}
			}
			    
			    $this->model_setting_setting->editSetting('oml_facto', $this->request->post);
			    
				$this->session->data['success'] = $this->language->get('Se modificaron los datos.');
				$this->model_extension_module->editModule($this->request->get['module_id'], $this->request->post);
			

			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], true));
		}
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_edit'] = $this->language->get('text_edit');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		/* breadcrumbs */
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], true)
		);

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('module/oml_facto', 'token=' . $this->session->data['token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('module/oml_facto', 'token=' . $this->session->data['token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}
		/* breadcrumbs */
		
		/* warning */
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		/* warning */
		
		//form		
		/* action */
		
		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('module/oml_facto', 'token=' . $this->session->data['token'], true);
		} else {
			$data['action'] = $this->url->link('module/oml_facto', 'token=' . $this->session->data['token'] . '&module_id=' . $this->request->get['module_id'], true);
		}
		
		$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], true);
		
		//campos
		if($this->request->server['REQUEST_METHOD'] == 'POST')
		{
			if (isset($this->request->post['oml_facto_backend_tipo_fe'])) {
				$data['oml_facto_backend_tipo_fe'] = $this->request->post['oml_facto_backend_tipo_fe'];
			}
			else 
			{
			    $data['oml_facto_backend_tipo_fe'] = "";	
			}
			
			if (isset($this->request->post['oml_facto_backend_tipo_fee'])) {
				$data['oml_facto_backend_tipo_fee'] = $this->request->post['oml_facto_backend_tipo_fee'];
			}
			else
			{
			    $data['oml_facto_backend_tipo_fee'] = "";	
			}
			
			if (isset($this->request->post['oml_facto_backend_tipo_be'])) {
				$data['oml_facto_backend_tipo_be'] = $this->request->post['oml_facto_backend_tipo_be'];
			}
			else 
			{
			    $data['oml_facto_backend_tipo_be'] = "";	
			}
			
			if (isset($this->request->post['oml_facto_backend_tipo_bee'])) {
				$data['oml_facto_backend_tipo_bee'] = $this->request->post['oml_facto_backend_tipo_bee'];
			}
			else
			{
			    $data['oml_facto_backend_tipo_bee'] = "";	
			}
		}
		else {
			$data['oml_facto_backend_tipo_fe'] = $this->config->get('oml_facto_backend_tipo_fe');
			$data['oml_facto_backend_tipo_fee'] = $this->config->get('oml_facto_backend_tipo_fee');
			$data['oml_facto_backend_tipo_be'] = $this->config->get('oml_facto_backend_tipo_be');
			$data['oml_facto_backend_tipo_bee'] = $this->config->get('oml_facto_backend_tipo_bee');
		}
		
		
		/* error tipo */
		if (isset($this->error['oml_facto_backend_tipo'])) {
			$data['error_oml_facto_backend_tipo'] = $this->error['oml_facto_backend_tipo'];
		} else {
			$data['error_oml_facto_backend_tipo'] = '';
		}
				
			
		/* campo oml_facto_backend_rut */
		if (isset($this->error['oml_facto_backend_rut'])) {
			$data['error_oml_facto_backend_rut'] = $this->error['oml_facto_backend_rut'];
		} else {
			$data['error_oml_facto_backend_rut'] = '';
		}
		
		if (isset($this->request->post['oml_facto_backend_rut'])) {
			$data['oml_facto_backend_rut'] = $this->request->post['oml_facto_backend_rut'];
		} else {
			$data['oml_facto_backend_rut'] = $this->config->get('oml_facto_backend_rut');
		}
		
		/* campo oml_facto_backend_user */
		if (isset($this->error['oml_facto_backend_user'])) {
			$data['error_oml_facto_backend_user'] = $this->error['oml_facto_backend_user'];
		} else {
			$data['error_oml_facto_backend_user'] = '';
		}
		
		if (isset($this->request->post['oml_facto_backend_user'])) {
			$data['oml_facto_backend_user'] = $this->request->post['oml_facto_backend_user'];
		} else {
			$data['oml_facto_backend_user'] = $this->config->get('oml_facto_backend_user');
		}
		
		/* campo oml_facto_backend_pass */
		if (isset($this->error['oml_facto_backend_pass'])) {
			$data['error_oml_facto_backend_pass'] = $this->error['oml_facto_backend_pass'];
		} else {
			$data['error_oml_facto_backend_pass'] = '';
		}
		
		if (isset($this->request->post['oml_facto_backend_pass'])) {
			$data['oml_facto_backend_pass'] = $this->request->post['oml_facto_backend_pass'];
		} else {
			$data['oml_facto_backend_pass'] = $this->config->get('oml_facto_backend_pass');
		}
		
		/* campo oml_facto_backend_url */
		if (isset($this->error['oml_facto_backend_url'])) {
			$data['error_oml_facto_backend_url'] = $this->error['oml_facto_backend_url'];
		} else {
			$data['error_oml_facto_backend_url'] = '';
		}
		
		if (isset($this->request->post['oml_facto_backend_url'])) {
			$data['oml_facto_backend_url'] = $this->request->post['oml_facto_backend_url'];
		} else {
			$data['oml_facto_backend_url'] = $this->config->get('oml_facto_backend_url');
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$data['method_data'] = $this->getListaMetodosPago();
		
		$this->response->setOutput($this->load->view('module/oml_facto.tpl', $data));
	}
   
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/oml_facto')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$fe = $fee = $be = $bee = false; 
		if(isset($this->request->post['oml_facto_backend_tipo_fe']) && $this->request->post['oml_facto_backend_tipo_fe']=="fe") $fe = true;
		if(isset($this->request->post['oml_facto_backend_tipo_fee']) && $this->request->post['oml_facto_backend_tipo_fee']=="fee") $fee = true;
		if(isset($this->request->post['oml_facto_backend_tipo_be']) && $this->request->post['oml_facto_backend_tipo_be']=="be") $be = true;
		if(isset($this->request->post['oml_facto_backend_tipo_bee']) && $this->request->post['oml_facto_backend_tipo_bee']=="bee") $bee = true;
		
		if(!($fe || $fee || $be || $bee)){
			$this->error['warning'] = "Debe seleccionar por lo menos un tipo de documento.";
		}
		
		if(!isset($this->request->post['oml_facto_backend_rut']) || $this->request->post['oml_facto_backend_rut']=="") {
			$this->error['warning'] = "Debe ingresar el RUT del vendedor.";
		}
		
		if(!isset($this->request->post['oml_facto_backend_user']) || $this->request->post['oml_facto_backend_user']=="") {
			$this->error['warning'] = "Debe ingresar nombre de usuario solicitado por FACTO.";
		}
		
		if(!isset($this->request->post['oml_facto_backend_pass']) || $this->request->post['oml_facto_backend_pass']=="") {
			$this->error['warning'] = "Debe ingresar la contrase&ntilde;a solicitada por FACTO.";
		}
		
		if(!isset($this->request->post['oml_facto_backend_url']) || filter_var($this->request->post['oml_facto_backend_url'], FILTER_VALIDATE_URL) === false) {
			$this->error['warning'] = "Debe ingresar un URL v&aacute;lida.";
		}
		
		return !$this->error;
   }
   
	
	/*
	protected function getConf($text) {
	
		$resultado = $this->db->query("SELECT setting FROM ".DB_PREFIX."module WHERE code='oml_facto'");
		$arregloModulo = $resultado->row['setting'];
		
		print_r(json_decode($arregloModulo)->oml_facto_backend_tipo_fe);
		
		$arregloModulo = str_replace("{", "", $arregloModulo);
		$arregloModulo = str_replace("}", "", $arregloModulo);
		$arregloModulo = explode(",", $arregloModulo);

		foreach($arregloModulo as $setting) {
			$pos = strrpos( $setting , $text );
			
			if($pos) {
				$valor = substr($setting, ($pos+strlen($text)+3), (strlen($setting)- strlen($text) - 5));
				return $valor;
			}
		}

		return false;	
   }*/
   
	protected function getConf($text) {
	
		$resultado = $this->db->query("SELECT setting FROM ".DB_PREFIX."module WHERE code='oml_facto'");
		if($resultado->num_rows == 0) return '';
		else return json_decode($resultado->row['setting'])->$text;
   }
   
	public function install() {
		// obtenemos la lista de todos los grupos
		$grupos = array();
		$resultado = $this->db->query("SELECT customer_group_id FROM ".DB_PREFIX."customer_group");
		if($resultado->num_rows > 0) {
			foreach($resultado->rows as $fila) {
				$grupos[] = $fila['customer_group_id'];
			}
		}
		
		//agregamos el custom field
		$this->db->query("INSERT INTO " . DB_PREFIX . "custom_field SET type='select', location='address', status=1, sort_order=0");
		$inserted_custom_field_id = $this->db->getLastId();
		
		//para cada grupo agregamos el custom field
		foreach($grupos as $grupo_id) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "custom_field_customer_group SET customer_group_id=".$grupo_id.", custom_field_id=".$inserted_custom_field_id.", required=0");
		}
		
		//obtenemos todos los languages
		$languages = array();
		$resultado = $this->db->query("SELECT language_id FROM ".DB_PREFIX."language");
		if($resultado->num_rows > 0) {
			foreach($resultado->rows as $fila) {
				$languages[] = $fila['language_id'];
			}
		}
		
		//para cada language agregamos description
		foreach($languages as $language_id) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "custom_field_description SET custom_field_id=".$inserted_custom_field_id.", name='comuna', language_id=".$language_id);
		}
	
		//agregamos los valores
		$comunas = $this->getComunas();
		foreach($comunas as $key => $valor) {
			//valor
			$this->db->query("INSERT INTO " . DB_PREFIX . "custom_field_value SET custom_field_id=".$inserted_custom_field_id.", sort_order=0");
			$inserted_custom_field_value_id = $this->db->getLastId();
			
			//para cada language
			foreach($languages as $language_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "custom_field_value_description SET custom_field_id=".$inserted_custom_field_id.", language_id=".$language_id.", custom_field_value_id=".$inserted_custom_field_value_id.", name='".$this->db->escape($valor)."'");
			}
		}
	
		//creamos la tabla para los MP
		$query_create = "
					CREATE TABLE IF NOT EXISTS `".DB_PREFIX."oml_facto_mp` (
					  `code` VARCHAR(255),
					  `fact` VARCHAR(255),
					PRIMARY KEY (`code`)
					)";
			$this->db->query($query_create);
			
			$this->load->model('extension/event');
			$this->model_extension_event->addEvent('oml_facto_admin_sale_order_info', 'admin/controller/sale/order/info/after', 'module/oml_facto/admin_sale_order_info');
   }
   
   public function uninstall() {
	   $resultado = $this->db->query("SELECT layout_id FROM " . DB_PREFIX . "layout WHERE name='oml_facto_order_info_layout'");
	   if($resultado->num_rows > 0) {
		   $layout_id = $resultado->row['layout_id'];
		   
		   $this->db->query("DELETE FROM " . DB_PREFIX . "layout_route WHERE layout_id=".$layout_id);
		   $this->db->query("DELETE FROM " . DB_PREFIX . "layout WHERE layout_id=".$layout_id);
		}
		
		// Borremos el campo custom creado
		$sql = "SELECT custom_field_id FROM " . DB_PREFIX . "custom_field_description WHERE name='comuna'";
		$resultado = $this->db->query($sql);
		if($resultado->num_rows > 0) {
		    foreach($resultado->rows as $fila)
		    {
		      $this->db->query("DELETE FROM " . DB_PREFIX . "custom_field_value_description WHERE custom_field_id = '".$fila['custom_field_id']."'");
		      
		      $this->db->query("DELETE FROM " . DB_PREFIX . "custom_field_value WHERE custom_field_id = '".$fila['custom_field_id']."'");
		      
		      $this->db->query("DELETE FROM " . DB_PREFIX . "custom_field_customer_group WHERE custom_field_id = '".$fila['custom_field_id']."'");
		      
		      $this->db->query("DELETE FROM " . DB_PREFIX . "custom_field WHERE custom_field_id = '".$fila['custom_field_id']."'");
		      
		    }
		}
		
   }
   
   public function admin_sale_order_info() {
		//print "<h1>david</h1>";
	}
	
   
   protected function getComunas() {
		$comunas = array(
			'100' => 'Algarrobo',
			'101' => 'Alhué',
			'102' => 'Alto Biobío',
			'103' => 'Alto del Carmen',
			'104' => 'Alto Hospicio',
			'105' => 'Ancud',
			'106' => 'Andacollo',
			'107' => 'Angol',
			'108' => 'Antártica',
			'109' => 'Antofagasta',
			'110' => 'Antuco',
			'111' => 'Arauco',
			'112' => 'Arica',
			'113' => 'Aysén',
			'114' => 'Buin',
			'115' => 'Bulnes',
			'116' => 'Cabildo',
			'117' => 'Cabo de Hornos',
			'118' => 'Cabrero',
			'119' => 'Calama',
			'120' => 'Calbuco',
			'121' => 'Caldera',
			'122' => 'Calera de Tango',
			'123' => 'Calle Larga',
			'124' => 'Camarones',
			'125' => 'Camiña',
			'126' => 'Canela',
			'127' => 'Cañete',
			'128' => 'Carahue',
			'129' => 'Cartagena',
			'130' => 'Casablanca',
			'131' => 'Castro',
			'132' => 'Catemu',
			'133' => 'Cauquenes',
			'134' => 'Cerrillos',
			'135' => 'Cerro Navia',
			'136' => 'Chaitén',
			'137' => 'Chanco',
			'138' => 'Chañaral',
			'139' => 'Chépica',
			'140' => 'Chiguayante',
			'141' => 'Chile Chico',
			'142' => 'Chillán',
			'143' => 'Chillán Viejo',
			'144' => 'Chimbarongo',
			'145' => 'Cholchol',
			'146' => 'Chonchi',
			'147' => 'Cisnes',
			'148' => 'Cobquecura',
			'149' => 'Cochamó',
			'150' => 'Cochrane',
			'151' => 'Codegua',
			'152' => 'Coelemu',
			'153' => 'Coihueco',
			'154' => 'Coinco',
			'155' => 'Colbún',
			'156' => 'Colchane',
			'157' => 'Colina',
			'158' => 'Collipulli',
			'159' => 'Coltauco',
			'160' => 'Combarbalá',
			'161' => 'Concepción',
			'162' => 'Conchalí',
			'163' => 'Concón',
			'164' => 'Constitución',
			'165' => 'Contulmo',
			'166' => 'Copiapó',
			'167' => 'Coquimbo',
			'168' => 'Coronel',
			'169' => 'Corral',
			'170' => 'Coyhaique',
			'171' => 'Cunco',
			'172' => 'Curacautín',
			'173' => 'Curacaví',
			'174' => 'Curaco de Vélez',
			'175' => 'Curanilahue',
			'176' => 'Curarrehue',
			'177' => 'Curepto',
			'178' => 'Curicó',
			'179' => 'Dalcahue',
			'180' => 'Diego de Almagro',
			'181' => 'Doñihue',
			'182' => 'El Bosque',
			'183' => 'El Carmen',
			'184' => 'El Monte',
			'185' => 'El Quisco',
			'186' => 'El Tabo',
			'187' => 'Empedrado',
			'188' => 'Ercilla',
			'189' => 'Estación Central',
			'190' => 'Florida',
			'191' => 'Freire',
			'192' => 'Freirina',
			'193' => 'Fresia',
			'194' => 'Frutillar',
			'195' => 'Futaleufú',
			'196' => 'Futrono',
			'197' => 'Galvarino',
			'198' => 'General Lagos',
			'199' => 'Gorbea',
			'200' => 'Graneros',
			'201' => 'Guaitecas',
			'202' => 'Hijuelas',
			'203' => 'Hualaihué',
			'204' => 'Hualañé',
			'205' => 'Hualpén',
			'206' => 'Hualqui',
			'207' => 'Huara',
			'208' => 'Huasco',
			'209' => 'Huechuraba',
			'210' => 'Illapel',
			'211' => 'Independencia',
			'212' => 'Iquique',
			'213' => 'Isla de Maipo',
			'214' => 'Isla de Pascua',
			'215' => 'Juan Fernández',
			'216' => 'La Calera',
			'217' => 'La Cisterna',
			'218' => 'La Cruz',
			'219' => 'La Estrella',
			'220' => 'La Florida',
			'221' => 'La Granja',
			'222' => 'La Higuera',
			'223' => 'La Ligua',
			'224' => 'La Pintana',
			'225' => 'La Reina',
			'226' => 'La Serena',
			'227' => 'La Unión',
			'228' => 'Lago Ranco',
			'229' => 'Lago Verde',
			'230' => 'Laguna Blanca',
			'231' => 'Laja',
			'232' => 'Lampa',
			'233' => 'Lanco',
			'234' => 'Las Cabras',
			'235' => 'Las Condes',
			'236' => 'Lautaro',
			'237' => 'Lebu',
			'238' => 'Licantén',
			'239' => 'Limache',
			'240' => 'Linares',
			'241' => 'Litueche',
			'242' => 'Llanquihue',
			'243' => 'Llay Llay',
			'244' => 'Lo Barnechea',
			'245' => 'Lo Espejo',
			'246' => 'Lo Prado',
			'247' => 'Lolol',
			'248' => 'Loncoche',
			'249' => 'Longaví',
			'250' => 'Lonquimay',
			'251' => 'Los Álamos',
			'252' => 'Los Andes',
			'253' => 'Los Ángeles',
			'254' => 'Los Lagos',
			'255' => 'Los Muermos',
			'256' => 'Los Sauces',
			'257' => 'Los Vilos',
			'258' => 'Lota',
			'259' => 'Lumaco',
			'260' => 'Machalí',
			'261' => 'Macul',
			'262' => 'Máfil',
			'263' => 'Maipú',
			'264' => 'Malloa',
			'265' => 'Marchihue',
			'266' => 'María Elena',
			'267' => 'María Pinto',
			'268' => 'Mariquina',
			'269' => 'Maule',
			'270' => 'Maullín',
			'271' => 'Mejillones',
			'272' => 'Melipeuco',
			'273' => 'Melipilla',
			'274' => 'Molina',
			'275' => 'Monte Patria',
			'276' => 'Mostazal',
			'277' => 'Mulchén',
			'278' => 'Nacimiento',
			'279' => 'Nancagua',
			'280' => 'Natales',
			'281' => 'Navidad',
			'282' => 'Negrete',
			'283' => 'Ninhue',
			'284' => 'Nogales',
			'285' => 'Nueva Imperial',
			'286' => 'Ñiquén',
			'287' => 'Ñuñoa',
			'288' => 'O\'Higgins',
			'289' => 'Olivar',
			'290' => 'Ollagüe',
			'291' => 'Olmué',
			'292' => 'Osorno',
			'293' => 'Ovalle',
			'294' => 'Padre Hurtado',
			'295' => 'Padre las Casas',
			'296' => 'Paihuano',
			'297' => 'Paillaco',
			'298' => 'Paine',
			'299' => 'Palena',
			'300' => 'Palmilla',
			'301' => 'Panguipulli',
			'302' => 'Panquehue',
			'303' => 'Papudo',
			'304' => 'Paredones',
			'305' => 'Parral',
			'306' => 'Pedro Aguirre Cerda',
			'307' => 'Pelarco',
			'308' => 'Pelluhue',
			'309' => 'Pemuco',
			'310' => 'Pencahue',
			'311' => 'Penco',
			'312' => 'Peñaflor',
			'313' => 'Peñalolén',
			'314' => 'Peralillo',
			'315' => 'Perquenco',
			'316' => 'Petorca',
			'317' => 'Peumo',
			'318' => 'Pica',
			'319' => 'Pichidegua',
			'320' => 'Pichilemu',
			'321' => 'Pinto',
			'322' => 'Pirque',
			'323' => 'Pitrufquén',
			'324' => 'Placilla',
			'325' => 'Portezuelo',
			'326' => 'Porvenir',
			'327' => 'Pozo Almonte',
			'328' => 'Primavera',
			'329' => 'Providencia',
			'330' => 'Puchuncaví',
			'331' => 'Pucón',
			'332' => 'Pudahuel',
			'333' => 'Puente Alto',
			'334' => 'Puerto Montt',
			'335' => 'Puerto Octay',
			'336' => 'Puerto Varas',
			'337' => 'Pumanque',
			'338' => 'Punitaqui',
			'339' => 'Punta Arenas',
			'340' => 'Puqueldón',
			'341' => 'Purén',
			'342' => 'Purranque',
			'343' => 'Putaendo',
			'344' => 'Putre',
			'345' => 'Puyehue',
			'346' => 'Queilén',
			'347' => 'Quellón',
			'348' => 'Quemchi',
			'349' => 'Quilaco',
			'350' => 'Quilicura',
			'351' => 'Quilleco',
			'352' => 'Quillón',
			'353' => 'Quillota',
			'354' => 'Quilpué',
			'355' => 'Quinchao',
			'356' => 'Quinta de Tilcoco',
			'357' => 'Quinta Normal',
			'358' => 'Quintero',
			'359' => 'Quirihue',
			'360' => 'Rancagua',
			'361' => 'Ránquil',
			'362' => 'Rauco',
			'363' => 'Recoleta',
			'364' => 'Renaico',
			'365' => 'Renca',
			'366' => 'Rengo',
			'367' => 'Requínoa',
			'368' => 'Retiro',
			'369' => 'Rinconada',
			'370' => 'Río Bueno',
			'371' => 'Río Claro',
			'372' => 'Río Hurtado',
			'373' => 'Río Ibáñez',
			'374' => 'Río Negro',
			'375' => 'Río Verde',
			'376' => 'Romeral',
			'377' => 'Saavedra',
			'378' => 'Sagrada Familia',
			'379' => 'Salamanca',
			'380' => 'San Antonio',
			'381' => 'San Bernardo',
			'382' => 'San Carlos',
			'383' => 'San Clemente',
			'384' => 'San Esteban',
			'385' => 'San Fabián',
			'386' => 'San Felipe',
			'387' => 'San Fernando',
			'388' => 'San Gregorio',
			'389' => 'San Ignacio',
			'390' => 'San Javier',
			'391' => 'San Joaquín',
			'392' => 'San José de Maipo',
			'393' => 'San Juan de la Costa',
			'394' => 'San Miguel',
			'395' => 'San Nicolás',
			'396' => 'San Pablo',
			'397' => 'San Pedro',
			'398' => 'San Pedro de Atacama',
			'399' => 'San Pedro de la Paz',
			'400' => 'San Rafael',
			'401' => 'San Ramón',
			'402' => 'San Rosendo',
			'403' => 'San Vicente',
			'404' => 'Santa Bárbara',
			'405' => 'Santa Cruz',
			'406' => 'Santa Juana',
			'407' => 'Santa María',
			'408' => 'Santiago',
			'409' => 'Santo Domingo',
			'410' => 'Sierra Gorda',
			'411' => 'Talagante',
			'412' => 'Talca',
			'413' => 'Talcahuano',
			'414' => 'Taltal',
			'415' => 'Temuco',
			'416' => 'Teno',
			'417' => 'Teodoro Schmidt',
			'418' => 'Tierra Amarilla',
			'419' => 'Tiltil',
			'420' => 'Timaukel',
			'421' => 'Tirúa',
			'422' => 'Tocopilla',
			'423' => 'Toltén',
			'424' => 'Tomé',
			'425' => 'Torres del Paine',
			'426' => 'Tortel',
			'427' => 'Traiguén',
			'428' => 'Treguaco',
			'429' => 'Tucapel',
			'430' => 'Valdivia',
			'431' => 'Vallenar',
			'432' => 'Valparaíso',
			'433' => 'Vichuquén',
			'434' => 'Victoria',
			'435' => 'Vicuña',
			'436' => 'Vilcún',
			'437' => 'Villa Alegre',
			'438' => 'Villa Alemana',
			'439' => 'Villarrica',
			'440' => 'Viña del Mar',
			'441' => 'Vitacura',
			'442' => 'Yerbas Buenas',
			'443' => 'Yumbel',
			'444' => 'Yungay',
			'445' => 'Zapallar'
		);
		
		return $comunas;
	}
	
	protected function getListaMetodosPago() {
		$method_data = array();
		$this->load->model('extension/extension');
		$results = $this->model_extension_extension->getInstalled('payment');
		
		foreach ($results as $result) {		
			if ($this->config->get($result . '_status')) {
				$method_data[] = $result;
			}
		}
		
		$retorno = array();
		//para cada MP hacemos un select
		//creamos la tabla para los MP
		$query_create = "
					CREATE TABLE IF NOT EXISTS `".DB_PREFIX."oml_facto_mp` (
					  `code` VARCHAR(255),
					  `fact` VARCHAR(255),
					PRIMARY KEY (`code`)
					)";
			$this->db->query($query_create);
		
		foreach($method_data as $metodo) {
			$query = "SELECT fact FROM ".DB_PREFIX."oml_facto_mp WHERE code='".$metodo."' LIMIT 0,1";
			$resultado = $this->db->query($query);
			
			if($resultado->num_rows > 0) {
				$sel = $resultado->row['fact'];
			} else {
				$sel = false;
			}
			
			$retorno[] = array('metodo' => $metodo, 'fact' => $sel);
		}

		return $retorno;
	}
	
	
}
?>