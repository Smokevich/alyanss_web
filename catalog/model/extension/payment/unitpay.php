<?php
class ModelExtensionPaymentUnitpay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/unitpay');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_unitpay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
		
		if ($this->config->get('payment_unitpay_total') > 0 && $this->config->get('payment_unitpay_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('payment_unitpay_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}
		
		if ($this->config->get('payment_unitpay_title' . $this->config->get('config_language_id'))) {
			$title = html_entity_decode($this->config->get('payment_unitpay_title' . $this->config->get('config_language_id')), ENT_QUOTES, 'UTF-8');
		} else {
			$title = $this->language->get('text_title');
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'unitpay',
				'title'      => $title,
				'terms'      => '',
				'sort_order' => $this->config->get('payment_unitpay_sort_order')
			);
		}
		
		return $method_data;
	}
}