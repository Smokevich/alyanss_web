<?php
class ControllerExtensionModulelivemaintenancemode extends Controller {
    private $error = array();
	
    public function index() { 
		$this->load->language('extension/module/livemaintenancemode');		
		$this->load->model('localisation/language');	
		$this->load->model('setting/setting');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
 		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('livemaintenancemode', $this->request->post);
			
			$this->model_setting_setting->editSettingValue('config', 'config_maintenance', $this->request->post['config_maintenance']);		
			
			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}	
		
		$data['heading_title'] = $this->language->get('heading_title');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['entry_livemmode_message'] = $this->language->get('entry_livemmode_message');
		$data['entry_livemmode_bottop'] = $this->language->get('entry_livemmode_bottop');
		$data['entry_livemmode_message_help'] = $this->language->get('entry_livemmode_message_help');
		$data['entry_we_recommend'] = $this->language->get('entry_we_recommend');
		$data['entry_show_we_recommend'] = $this->language->get('entry_show_we_recommend');
		$data['module_description'] = $this->language->get('module_description');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		
		// << редактор
		$data['ckeditor'] = $this->config->get('config_editor_default');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['lang'] = $this->language->get('lang');
		// редактор >>

		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->session->data['success'])) {
			$data['text_success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/livemaintenancemode', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['action'] = $this->url->link('extension/module/livemaintenancemode', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		
		$data['modules'] = array();
		
		if (isset($this->request->post['livemaintenancemode'])) {
			$data['modules'] = $this->request->post['livemaintenancemode'];
		} elseif ($this->config->get('livemaintenancemode')) { 
			$data['modules'] = $this->config->get('livemaintenancemode');
		}
		
		$this->load->model('design/layout');
		
		$data['layouts'] = $this->model_design_layout->getLayouts();

		$this->template = 'extension/module/livemaintenancemode';
		$this->children = array(
			'common/header',
			'common/footer'
		);
		
		// << config_maintenance
		$this->load->language('setting/setting');
		$data['entry_maintenance'] = $this->language->get('entry_maintenance');
		if (isset($this->request->post['config_maintenance'])) {
			$data['config_maintenance'] = $this->request->post['config_maintenance'];
		} else {
			$data['config_maintenance'] = $this->config->get('config_maintenance');
		}
		// config_maintenance >>
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');		
			
		$this->response->setOutput($this->load->view('extension/module/livemaintenancemode', $data));
    }
	
	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/livemaintenancemode')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}	
}
?>