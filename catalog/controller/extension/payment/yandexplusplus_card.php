<?php
class ControllerExtensionPaymentYandexpluspluscard extends Controller {
	public function index() {
		$this->language->load('extension/payment/yandexplusplus_card');
		$data['instructionat'] = $this->config->get('payment_yandexplusplus_card_instruction_attach');
		$data['btnlater'] = $this->config->get('payment_yandexplusplus_card_button_later');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['button_later'] = $this->language->get('button_pay_later');

		if ($this->config->get('payment_yandexplusplus_card_instruction_attach')){
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			$this->load->model('payment/yandexplusplus_card');
			$data['yandexplusplus_cardi'] = htmlspecialchars_decode($this->model_payment_yandexplusplus_card->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_card_instruction_' . $this->config->get('config_language_id'))));
		}

        return $this->load->view('extension/payment/yandexplusplus_card', $data);	 
	}
	
	public function confirm() {
		$json = array();

		if ($this->session->data['payment_method']['code'] == 'yandexplusplus_card') {
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			$action = $this->url->link('extension/payment/yandexplusplus/pay');
			$paymentredir = $action .
				'&paymentType=' . $order_info['payment_code'] .
				'&order_id='	. $order_info['order_id'] . 
				'&first=1';


			if (!$this->config->get('payment_yandexplusplus_card_createorder_or_notcreate') || isset($this->request->get['blater'])){ 

			  	$this->language->load('extension/payment/yandexplusplus_card');
				$this->load->model('extension/payment/yandexplusplus_card');
				if ($this->config->get('payment_yandexplusplus_card_mail_instruction_attach')){
					$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
					$comment = $this->model_extension_payment_yandexplusplus_card->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_card_mail_instruction_' . $this->config->get('config_language_id')));
				    $comment = htmlspecialchars_decode($comment);
				    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_yandexplusplus_card_on_status_id'), $comment, true);
			    }
			    else{
					$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_yandexplusplus_card_on_status_id'), '', true);
				}
			}

			$json['redirect'] = $paymentredir;
			$json['redirectlater'] = $this->url->link('checkout/success');
			
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
?>