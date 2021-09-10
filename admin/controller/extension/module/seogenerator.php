<?php
class ControllerExtensionModuleSeogenerator extends Controller {
	private $error = array(); 
	
	public function index() {   
		$this->language->load('extension/module/seogenerator');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
		$this->load->model('extension/module/seogenerator');
		$this->load->model('setting/module');
				
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('seogenerator', $this->request->post);		
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}
			$this->model_setting_setting->editSetting('seogenerator', $this->request->post);
			
			$this->session->data['success'] = $this->language->get('text_success');
						
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}
/*		
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_layout'] = $this->language->get('text_layout');
		$data['text_status'] = $this->language->get('text_status');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_categories'] = $this->language->get('text_categories');
		$data['text_products'] = $this->language->get('text_products');
		$data['text_manufacturers'] = $this->language->get('text_manufacturers');
		$data['text_informations'] = $this->language->get('text_informations');
		$data['text_categories_desc'] = $this->language->get('text_categories_desc');
		$data['text_products_desc'] = $this->language->get('text_products_desc');
		$data['text_manufacturers_desc'] = $this->language->get('text_manufacturers_desc');
		$data['text_informations_desc'] = $this->language->get('text_informations_desc');
		$data['text_title'] = $this->language->get('text_title');
		$data['text_description'] = $this->language->get('text_description');
		$data['text_keywords'] = $this->language->get('text_keywords');
		$data['text_h1'] = $this->language->get('text_h1');
		$data['text_sef'] = $this->language->get('text_sef');
		$data['text_dash'] = $this->language->get('text_dash');
		$data['text_underscore'] = $this->language->get('text_underscore');
		$data['text_sef_category_auto'] = $this->language->get('text_sef_category_auto');
		$data['text_sef_product_auto'] = $this->language->get('text_sef_product_auto');
		$data['text_sef_manufacturer_auto'] = $this->language->get('text_sef_manufacturer_auto');
		$data['text_sef_information_auto'] = $this->language->get('text_sef_information_auto');
		$data['text_sef_product_update'] = $this->language->get('text_sef_product_update');
		$data['text_sef_category_update'] = $this->language->get('text_sef_category_update');
		$data['text_sef_manufacturer_update'] = $this->language->get('text_sef_manufacturer_update');
		$data['text_sef_information_update'] = $this->language->get('text_sef_information_update');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_sef_product'] = $this->language->get('button_sef_product');
		$data['button_sef_category'] = $this->language->get('button_sef_category');
		$data['button_sef_manufacturer'] = $this->language->get('button_sef_manufacturer');
		$data['button_sef_information'] = $this->language->get('button_sef_information');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_generate_success'] = $this->language->get('text_generate_success');
		$data['error_generate'] = $this->language->get('generate_error');
	*/	
 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/seogenerator', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		
		
		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/seogenerator', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/seogenerator', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}		
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		
		$data['modules'] = array();
		
		if (isset($this->request->post['seogenerator_module'])) {
			$data['seogenerator_module'] = $this->request->post['seogenerator_module'];
		} elseif (isset($this->request->get['module_id'])) { 
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
			$data['seogenerator_module'] = $module_info['seogenerator_module'];		
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/module/seogenerator', $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/seogenerator')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}

}
?>