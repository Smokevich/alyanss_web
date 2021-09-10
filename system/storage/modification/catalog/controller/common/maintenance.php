<?php
class ControllerCommonMaintenance extends Controller {
	public function index() {
		$this->load->language('common/maintenance');

		$this->document->setTitle($this->language->get('heading_title'));

		if ($this->request->server['SERVER_PROTOCOL'] == 'HTTP/1.1') {
			$this->response->addHeader('HTTP/1.1 503 Service Unavailable');
		} else {
			$this->response->addHeader('HTTP/1.0 503 Service Unavailable');
		}

		$this->response->addHeader('Retry-After: 3600');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_maintenance'),
			'href' => $this->url->link('common/maintenance')
		);

		$data['message'] = $this->language->get('text_message');

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		$this->load->model('setting/setting');
		$mod_settings = $this->model_setting_setting->getSetting('livemaintenancemode');
		if (!empty($mod_settings['livemaintenancemode']['livemmode_message'])) {
			$data['livemmode_message'] = html_entity_decode($mod_settings['livemaintenancemode']['livemmode_message'], ENT_QUOTES, 'UTF-8');
		} else {
			$data['livemmode_message'] = $data['message'];
		}
		
		if (!empty($mod_settings['livemaintenancemode']['livemmode_bottop'])) {
			$data['header'] = '';
			$data['footer'] = '';
		}

		$this->response->setOutput($this->load->view('common/maintenance', $data));
	}
}
