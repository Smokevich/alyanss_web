<?php
class ModelExtensionPaymentYandexplusplus extends Model { 
public function getMethod($address, $total) {
		$this->load->language('extension/payment/yandexplusplus');
    if ($this->config->get('payment_yandexplusplus_status')) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_yandexplusplus_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

			if (!$this->config->get('payment_yandexplusplus_geo_zone_id')) {
				$status = TRUE;
				if ($this->config->get('payment_yandexplusplus_maxpay')){
						if ($this->currency->format($total, 'RUB', $this->currency->getValue('RUB'), false) <= $this->config->get('payment_yandexplusplus_maxpay')) {
							$status = true;
							if ($total > 0) {
								$status = true;
							} else {
								$status = false;
							}
						} else {
							$status = false;
						}
				}
				else{
					if ($total > 0) {
						$status = true;
					} else {
						$status = false;
					}
				}
			} elseif ($query->num_rows) {
				$status = TRUE;
				if ($this->config->get('payment_yandexplusplus_maxpay')){
						if ($this->currency->format($total, 'RUB', $this->currency->getValue('RUB'), false) <= $this->config->get('payment_yandexplusplus_maxpay')) {
							$status = true;
							if ($total > 0) {
								$status = true;
							} else {
								$status = false;
							}
						} else {
							$status = false;
						}
				}
				else{
					if ($total > 0) {
						$status = true;
					} else {
						$status = false;
					}
				}

			} else {
				$status = FALSE;
			}
		} else {
			$status = FALSE;
		}
		
    
		$method_data = array();
		if ($status) {  
			if ($this->config->get('payment_yandexplusplus_name_attach')) {
				$metname = htmlspecialchars_decode($this->config->get('payment_yandexplusplus_name_' . $this->config->get('config_language_id')));
			}
			else{
				$metname = $this->language->get('text_title');
			}
			$method_data = array( 
				'code'       => 'yandexplusplus',
				'title'      => $metname,
				'terms'      => '',
				'sort_order' => $this->config->get('payment_yandexplusplus_sort_order')
			);
		}
		
    	return $method_data;
  	}


  	public function encrypt($value, $key) {
		return $value;
	}
	
	public function decrypt($value, $key) {
		return $value;
	}

	public function getOrderStatus($order_status_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
		
		return $query->row;
	}

	public function getPaymentStatus($order_id) {
		$query = $this->db->query("SELECT `status` FROM " . DB_PREFIX . "yandexplusplus WHERE num_order = '" . (int)$order_id . "' ");
		
		return $query->row;
	}


    public function getOrderMethod($order_id) {
        $query = $this->db->query("SELECT `payment_code` FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "' ");
        
        return $query->row;
    }

	public function getCustomFields($order_info, $varabliesd) {
            $instros = explode('~', $varabliesd);
            $instroz = "";

            if ($this->config->get('yandexplusplus_fixen')) {
                if ($this->config->get('yandexplusplus_fixen') == 'fix'){
                    $out_summ = $this->config->get('yandexplusplus_fixen_amount');
                }
                else{
                    $out_summ = $order_info['total'] * $this->config->get('yandexplusplus_fixen_amount') / 100;
                }
            }
            else{
                $out_summ = $order_info['total'];
            }

            foreach ($instros as $instro) {
                if ($instro == 'checkouthref' || $instro == 'href' || $instro == 'orderid' || $instro == 'itogo' ||  $instro == 'itogobez' ||  $instro == 'itogozakaz' || $instro == 'komis' || $instro == 'total-komis' || $instro == 'plus-komis' || $instro == 'totals' || $instro == 'nds' || $instro == 'bvnds' || isset($order_info[$instro]) || substr_count($instro, "ordercustom_") || substr_count($instro, "shippingAddresscustom_") || substr_count($instro, "paymentAddresscustom_") || substr_count($instro, "customercustom_") || substr_count($instro, "paymentsimple4_") || substr_count($instro, "shippingsimple4_") || substr_count($instro, "simple4_")){
                    if ($instro == 'checkouthref'){
                        
                        $instro_other = $order_info['store_url'] . 'index.php?route=checkout/success_yandexplusplus&code=' . $this->getSecureCode($order_info['order_id']) . '&order='.$order_info['order_id'];
                       
                    }
                    if ($instro == 'href'){
                        
                        $instro_other = $order_info['store_url'] . 'index.php?route=extension/payment/yandexplusplus/go&code=' . $this->getSecureCode($order_info['order_id']) . '&order='.$order_info['order_id'];
                        
                    }
                    if ($instro == 'orderid'){
                        $instro_other = $order_info['order_id'];
                    }
                    if ($instro == 'itogo'){
                        $instro_other = $this->currency->format($out_summ, 'RUB', $this->currency->getValue('RUB'), true);
                    }
                    if ($instro == 'itogobez'){
                        $instro_other = $this->currency->format($out_summ, 'RUB', $this->currency->getValue('RUB'), false);
                    }
                    if ($instro == 'itogozakaz'){
                        $instro_other = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
                    }
                    if ($instro == 'komis'){
                        if($this->config->get('yandexplusplus_komis')){
                            $instro_other = $this->config->get('yandexplusplus_komis') . '%';
                        }
                        else{$instro_other = '';}
                    }
                    if ($instro == 'total-komis'){
                        if($this->config->get('yandexplusplus_komis')){
                            $instro_other = $this->currency->format($out_summ * $this->config->get('yandexplusplus_komis')/100,  'RUB', $this->currency->getValue('RUB'), true);
                        }
                        else{$instro_other = '';}
                    }
                    if ($instro == 'plus-komis'){
                        if($this->config->get('yandexplusplus_komis')){
                            $instro_other = $this->currency->format($out_summ + ($out_summ * $this->config->get('yandexplusplus_komis')/100),  'RUB', $this->currency->getValue('RUB'), true);
                        }
                        else{$instro_other = '';}
                    }
                    if ($instro == 'totals'){
                        $instro_other = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], true);
                    }
                    
                    if(isset($order_info[$instro])){
                        $instro_other = $order_info[$instro];
                    }

                    if (substr_count($instro, "ordercustom_")){
                        $this->load->model('tool/simplecustom');
                        $instro = ltrim($instro, 'order');
                        $customx = $this->model_tool_simplecustom->getOrderField($order_info['order_id'], $instro);
                        if ($customx){
                             $instro_other = $customx;
                        }
                        
                    }
                    if (substr_count($instro, "shippingAddresscustom_")){
                        $this->load->model('tool/simplecustom');
                        $instro = ltrim($instro, 'shippingAddress');
                        $customx = $this->model_tool_simplecustom->getShippingAddressField($order_info['order_id'], $instro);
                        if ($customx){
                            $instro_other = $customx;
                        }
                    }
                    if (substr_count($instro, "paymentAddresscustom_")){
                        $this->load->model('tool/simplecustom');
                        $instro = ltrim($instro, 'shippingAddress');
                        $customx = $this->model_tool_simplecustom->getPaymentAddressField($order_info['order_id'], $instro);
                        if ($customx){
                            $instro_other = $customx;
                        }
                    }
                    if (substr_count($instro, "customercustom_")){
                        $this->load->model('tool/simplecustom');
                        $instro = ltrim($instro, 'customer');
                        $customx = $this->model_tool_simplecustom->getCustomerField($order_info['customer_id'], $instro);
                        if ($customx){
                            $instro_other = $customx;
                        }
                    }

                    if (substr_count($instro, "paymentsimple4_") ){
                        $this->load->model('tool/simplecustom');
                        $customx = $this->model_tool_simplecustom->getCustomFields('order', $order_info['order_id']);
                        $pole = ltrim($instro, 'paymentsimple4');
                        $pole = substr($pole, 1);
                        if (array_key_exists($pole , $customx) == true){
                        $instro_other = $customx[$pole];
                        }
                        if (array_key_exists('payment_' . $pole , $customx) == true){
                          $instro_other = $customx['payment_' . $pole];
                        }
                    }
                    if (substr_count($instro, "shippingsimple4_") ){
                        $this->load->model('tool/simplecustom');
                        $customx = $this->model_tool_simplecustom->getCustomFields('order', $order_info['order_id']);
                        $pole = ltrim($instro, 'shippingsimple4');
                        $pole = substr($pole, 1);
                        if (array_key_exists($pole , $customx) == true){
                        $instro_other = $customx[$pole];
                        }
                        if (array_key_exists('shipping_' . $pole , $customx) == true){
                          $instro_other = $customx['shipping_' . $pole];
                        }
                    }
                    if (substr_count($instro, "simple4_") ){
                        $this->load->model('tool/simplecustom');
                        $customx = $this->model_tool_simplecustom->getCustomFields('order', $order_info['order_id']);
                        $pole = ltrim($instro, 'simple4');
                        $pole = substr($pole, 1);
                        if (array_key_exists($pole , $customx) == true){
                        $instro_other = $customx[$pole];
                        }
                    }
                    
                }
                else {
                    $instro_other = htmlspecialchars_decode($instro);
                }
                    $instroz .=  $instro_other;
            }
            return $instroz;
    }

    public function getSecureCode($order_id) {
        $code = substr(md5($order_id . $this->config->get('config_encryption')), 0, 12);
        return $code;
    }
}
?>