<?php
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');

		$this->load->model('catalog/information');

		$data['informations'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}
		}

		$data['contact'] = $this->url->link('information/contact');

			$data['search'] = $this->url->link('product/search');
			$data['compare'] = $this->url->link('product/compare');
      
		$data['return'] = $this->url->link('account/return/add', '', true);
		$data['sitemap'] = $this->url->link('information/sitemap');
		$data['tracking'] = $this->url->link('information/tracking');
		$data['manufacturer'] = $this->url->link('product/manufacturer');
		$data['voucher'] = $this->url->link('account/voucher', '', true);
		$data['affiliate'] = $this->url->link('affiliate/login', '', true);
		$data['special'] = $this->url->link('product/special');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);

		$data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));

		// Whos Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
		}

		$data['scripts'] = $this->document->getScripts('footer');

		$data['meta_title'] = $this->config->get('config_meta_title');
	    $data['meta_description'] = $this->config->get('config_meta_description');
		$data['meta_title'] = $this->config->get('config_meta_title');
		$data['telephone'] = $this->config->get('config_telephone');
		$data['address'] = $this->config->get('config_address');
		$data['open'] = $this->config->get('config_open');
		$data['owner'] = $this->config->get('config_owner');
		$data['email'] = $this->config->get('config_email');
		$data['search'] = $this->config->get('config_search');
		$data['compare'] = $this->config->get('config_compare');

		$data['text_email'] = $this->language->get('text_email');
		$data['text_address'] = $this->language->get('text_address');
		$data['text_telephone'] = $this->language->get('text_telephone');
		$data['text_open'] = $this->language->get('text_open');
		$data['text_search_title'] = $this->language->get('text_search_title');
		$data['text_compare_title'] = $this->language->get('text_compare_title');
		
		

			$data['pos_map'] = $this->load->controller('common/pos_map');
        
		return $this->load->view('common/footer', $data);
	}
}
