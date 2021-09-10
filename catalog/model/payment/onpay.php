<?php 
class ModelPaymentOnpay extends Model 
{
  public function getMethod($address) {
		$this->load->language('payment/onpay');
    
    $method_data = array();
    
		if ($this->config->get('onpay_status')) {
			$method_data = array(
				'code'       => 'onpay',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('onpay_sort_order')
			);
    }
      
    return $method_data;
	}
}
?>