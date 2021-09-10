<?php
class ControllerExtensionPaymentYandexplusplus extends Controller {
	public function index() {
		$this->language->load('extension/payment/yandexplusplus');
		$data['instructionat'] = $this->config->get('payment_yandexplusplus_instruction_attach');
		$data['btnlater'] = $this->config->get('payment_yandexplusplus_button_later');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['button_later'] = $this->language->get('button_pay_later');

		if ($this->config->get('payment_yandexplusplus_instruction_attach')){
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			$this->load->model('extension/payment/yandexplusplus');
			$data['yandexplusplusi'] = htmlspecialchars_decode($this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_instruction_' . $this->config->get('config_language_id'))));
		}

        return $this->load->view('extension/payment/yandexplusplus', $data);	 
	}
	
	public function confirm() {
		$json = array();

		if ($this->session->data['payment_method']['code'] == 'yandexplusplus') {
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			$action = $this->url->link('extension/payment/yandexplusplus/pay');
			$paymentredir = $action .
				'&paymentType=' . $order_info['payment_code'] .
				'&order_id='	. $order_info['order_id'] . 
				'&first=1';


			if (!$this->config->get('payment_yandexplusplus_createorder_or_notcreate') || isset($this->request->get['blater'])){ 

			  	$this->language->load('extension/payment/yandexplusplus');
				$this->load->model('extension/payment/yandexplusplus');
				if ($this->config->get('payment_yandexplusplus_mail_instruction_attach')){
					$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
					$comment = $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_mail_instruction_' . $this->config->get('config_language_id')));
				    $comment = htmlspecialchars_decode($comment);
				    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_yandexplusplus_on_status_id'), $comment, true);
			    }
			    else{
					$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_yandexplusplus_on_status_id'), '', true);
				}
			}

			$json['redirect'] = $paymentredir;
			$json['redirectlater'] = $this->url->link('checkout/success');
			
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function go() {

      $this->load->model('extension/payment/yandexplusplus');

      if (isset($this->request->get['code']) & isset($this->request->get['order']) || isset($this->request->get['code']) & isset($this->request->get['order_id'])){

      	if (isset($this->request->get['order'])){
      		$inv_id = $this->request->get['order'];
      	}

      	if (isset($this->request->get['order_id'])){
      		$inv_id = $this->request->get['order_id'];
      	}

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($inv_id);
      	$platp = substr(md5($order_info['order_id'] . $this->config->get('config_encryption')), 0, 12);
        if ($this->request->get['code'] != $platp) {
        	$this->response->redirect($this->url->link('error/not_found'));
        }
        if ($order_info['order_id'] == 0){$this->response->redirect($this->url->link('error/not_found'));}
        if (!$this->customer->isLogged()) {
          $data['back'] = $this->url->link('common/home');
        }
        else{
          $data['back'] = $this->url->link('account/order');
        }
	      $action = $this->url->link('extension/payment/yandexplusplus/pay');
    
    		$data['merchant_url'] = $action .

							'&order_id=' 		. $order_info['order_id'] .
              '&paymentType=' . $order_info['payment_code'] . '&code=' . substr(md5($order_info['order_id'] . $this->config->get('config_encryption')), 0, 12);

        $this->load->model('extension/payment/yandexplusplus');
        $paystat = $this->model_extension_payment_yandexplusplus->getPaymentStatus($order_info['order_id']);
        if (!isset($paystat['status'])){$paystat['status'] = 0;}

        $data['paystat'] = $paystat['status'];
        $this->load->language('extension/payment/'.$order_info['payment_code']);
        $data['button_pay'] = $this->language->get('button_pay');
		$data['button_back'] = $this->language->get('button_back');
		$data['heading_title'] = $this->language->get('heading_title');

        if ($paystat['status'] != 1){

	        	if ($this->config->get($order_info['payment_code'].'_fixen')) {
					if ($this->config->get($order_info['payment_code'].'_fixen') == 'fix'){
					    $out_summ = $this->config->get($order_info['payment_code'].'_fixen_amount');
					}
					else{
					    $out_summ = $order_info['total'] * $this->config->get($order_info['payment_code'].'_fixen_amount') / 100;
					}
				}
				else{
					$out_summ = $order_info['total'];
				}
		        if ($this->config->get($order_info['payment_code'].'_hrefpage_text_attach')) {
		          $data['hrefpage_text'] = '';
		          $data['hrefpage_text'] = $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get($order_info['payment_code'].'_hrefpage_text_' . $this->config->get('config_language_id')));
		        }
		        else{        
		        $data['send_text'] = $this->language->get('send_text');
		        $data['send_text2'] = $this->language->get('send_text2');
		        $data['inv_id'] = $inv_id;
		        $data['out_summ'] = $this->currency->format($out_summ, $order_info['currency_code'], $order_info['currency_value'], true);
		        }
		}
		else{
			$data['hrefpage_text'] = $this->language->get('oplachen');
		}

	    $data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('extension/payment/yandexplusplus_go', $data));
    
      }
      else{
        echo "No data";
      }
  }

  public function pay() {
  	  if (isset($this->session->data['order_id'])){
  	  	$sesionord = $this->session->data['order_id'];
  	  }
  	  else{
  	  	$sesionord = '';
  	  }

      if ($this->request->get['order_id'] != $sesionord){
        if(!isset($this->request->post['nesyandexa'])){
        	if(isset($this->request->get['paymentType'])){
        		if(isset($this->request->get['first'])){$first = '&first=1';}else{$first = '';}
        		if($this->request->get['paymentType'] == 'yandexplusplus'){
        			$this->response->redirect($this->url->link('extension/payment/yandexplusplus/status&order_id='.$this->request->get['order_id'].$first));
        		}
        		if($this->request->get['paymentType'] == 'yandexplusplus_card'){
        			$this->response->redirect($this->url->link('extension/payment/yandexplusplus/status&order_id='.$this->request->get['order_id'].$first));
        		}
        	}
        	else{
        		$this->response->redirect($this->url->link('common/home'));
        	}
        }
      }
      
      if ($this->request->get['paymentType'] == 'yandexplusplus' || $this->request->get['paymentType'] == 'yandexplusplus_card'){

      	$this->load->model('checkout/order');
      	$order_info = $this->model_checkout_order->getOrder($this->request->get['order_id']);

      	$data['confname'] = $this->config->get('config_name');

        if ($this->request->get['paymentType'] == 'yandexplusplus'){

      	  $this->language->load('extension/payment/yandexplusplus');
      	  $data['order_text'] = $this->language->get('pay_order_text');
      	  $data['order_text_target'] = $this->language->get('pay_order_text_target');
          $data['paymentType'] = 'PC';
          $data['receiver'] = $this->config->get('payment_yandexplusplus_login');
          if(!$this->config->get('payment_yandexplusplus_createorder_or_notcreate')){
	          if (isset($this->session->data['order_id'])){
		        if ($this->request->get['order_id'] == $this->session->data['order_id']){
		            $this->cart->clear();
		            
		            unset($this->session->data['shipping_method']);
		            unset($this->session->data['shipping_methods']);
		            unset($this->session->data['payment_method']);
		            unset($this->session->data['payment_methods']);
		            unset($this->session->data['guest']);
		            unset($this->session->data['comment']);
		            unset($this->session->data['order_id']);  
		            unset($this->session->data['coupon']);
		            unset($this->session->data['reward']);
		            unset($this->session->data['voucher']);
		            unset($this->session->data['vouchers']);
		        }
		      }
		  }
          if ($this->config->get('payment_yandexplusplus_komis')){$proc=$this->config->get('payment_yandexplusplus_komis');}
	      if ($this->config->get('payment_yandexplusplus_fixen')) {
				if ($this->config->get('payment_yandexplusplus_fixen') == 'fix'){
				    $out_summ = $this->config->get('payment_yandexplusplus_fixen_amount');
				}
				else{
				    $out_summ = $order_info['total'] * $this->config->get('payment_yandexplusplus_fixen_amount') / 100;
				}
		  }
		  else{
				$out_summ = $order_info['total'];
		  }
        }
        if ($this->request->get['paymentType'] == 'yandexplusplus_card'){

      	  $this->language->load('extension/payment/yandexplusplus_card');
      	  $data['order_text'] = $this->language->get('pay_order_text');
      	  $data['order_text_target'] = $this->language->get('pay_order_text_target');
          $data['paymentType'] = 'AC';
          $data['receiver'] = $this->config->get('payment_yandexplusplus_card_login');
          if(!$this->config->get('payment_yandexplusplus_card_createorder_or_notcreate')){
	          if (isset($this->session->data['order_id'])){
		        if ($this->request->get['order_id'] == $this->session->data['order_id']){
		            $this->cart->clear();
		            
		            unset($this->session->data['shipping_method']);
		            unset($this->session->data['shipping_methods']);
		            unset($this->session->data['payment_method']);
		            unset($this->session->data['payment_methods']);
		            unset($this->session->data['guest']);
		            unset($this->session->data['comment']);
		            unset($this->session->data['order_id']);  
		            unset($this->session->data['coupon']);
		            unset($this->session->data['reward']);
		            unset($this->session->data['voucher']);
		            unset($this->session->data['vouchers']);
		        }
		      }
		  }
          if ($this->config->get('payment_yandexplusplus_card_komis')){$proc=$this->config->get('payment_yandexplusplus_card_komis');}
          	if ($this->config->get('payment_yandexplusplus_card_fixen')) {
				if ($this->config->get('payment_yandexplusplus_card_fixen') == 'fix'){
			    	$out_summ = $this->config->get('payment_yandexplusplus_card_fixen_amount');
				}
				else{
			    	$out_summ = $order_info['total'] * $this->config->get('payment_yandexplusplus_card_fixen_amount') / 100;
				}
		  	}
		  	else{
				$out_summ = $order_info['total'];
		  	}
          }
      }
      else{
        echo 'error: no payment method';
        exit();
      }
      if (is_numeric($out_summ)){
        if ($this->currency->has('RUB')){
          $totalrub = $out_summ;
          if (isset($proc)){$data['total'] = $this->currency->format(($totalrub*$proc/100)+$totalrub, 'RUB', $this->currency->getValue('RUB'), false);}
          else{$data['total'] = $this->currency->format($totalrub, 'RUB', $this->currency->getValue('RUB'), false);}
        }
        else{echo 'No currency RUB'; exit();}
      }
      else{
        echo 'error: no total sum';
        exit();
      }

      if (is_numeric($this->request->get['order_id'])){
        $data['order_id'] = $this->request->get['order_id'];
      }
      else{
        echo 'error: no order id';
        exit();
      }


      if (isset($this->session->data['order_id'])){
        if ($this->request->get['order_id'] == $this->session->data['order_id']){
		    unset($this->session->data['order_id']); 
		}
      }


		$this->response->setOutput($this->load->view('extension/payment/yandexplusplus_pay', $data));
    }

  public function callback() {
  if (isset($this->request->post['operation_id']) && isset($this->request->post['amount']) && ($this->request->post['codepro'] == 'false')){

  	$this->load->model('checkout/order');

	$order_info = $this->model_checkout_order->getOrder($this->request->post['label']);

	if ($this->request->post['notification_type'] == 'p2p-incoming'){
		if ($this->config->get('payment_yandexplusplus_fixen')) {
			if ($this->config->get('payment_yandexplusplus_fixen') == 'fix'){
			    $totalrub = $this->config->get('payment_yandexplusplus_fixen_amount');
			}
			else{
			    $totalrub = $order_info['total'] * $this->config->get('payment_yandexplusplus_fixen_amount') / 100;
			}
		}
		else{
			$totalrub = $order_info['total'];
		}
		$secret_key = $this->config->get('payment_yandexplusplus_password');
		if ($this->config->get('payment_yandexplusplus_komis')){
			$youpayment = $this->currency->format(($totalrub * $this->config->get('payment_yandexplusplus_komis')/100) + $totalrub, 'RUB', $this->currency->getValue('RUB'), false);
		}
		else{
			$youpayment = $this->currency->format($totalrub, 'RUB', $this->currency->getValue('RUB'), false);
		}
	}
	if ($this->request->post['notification_type'] == 'card-incoming'){
		if ($this->config->get('payment_yandexplusplus_card_fixen')) {
			if ($this->config->get('payment_yandexplusplus_card_fixen') == 'fix'){
			    $totalrub = $this->config->get('payment_yandexplusplus_card_fixen_amount');
			}
			else{
			    $totalrub = $order_info['total'] * $this->config->get('payment_yandexplusplus_card_fixen_amount') / 100;
			}
		}
		else{
			$totalrub = $order_info['total'];
		}
		$secret_key = $this->config->get('payment_yandexplusplus_card_password');
		if ($this->config->get('payment_yandexplusplus_card_komis')){
			$youpayment = $this->currency->format(($totalrub * $this->config->get('payment_yandexplusplus_card_komis')/100) + $totalrub, 'RUB', $this->currency->getValue('RUB'), false);
		}
		else{
			$youpayment = $this->currency->format($totalrub, 'RUB', $this->currency->getValue('RUB'), false);
		}
	}

	$payments = number_format($this->request->post['withdraw_amount'],2);
	$youpayment = number_format($youpayment,2);
	$this->load->model('extension/payment/yandexplusplus');
	if($youpayment == $payments){
		$yahash = $this->request->post['sha1_hash'];
		$myhash = $this->request->post['notification_type'].'&'.$this->request->post['operation_id'].'&'.$this->request->post['amount'].'&'.$this->request->post['currency'].'&'.$this->request->post['datetime'].'&'.$this->request->post['sender'].'&'.$this->request->post['codepro'].'&'.$this->model_extension_payment_yandexplusplus->decrypt($secret_key, $this->config->get('config_encryption')).'&'.$this->request->post['label'];
	  	$myhash = hash("sha1", $myhash);
		

		if($yahash == $myhash) {

			

			$paystat = $this->model_extension_payment_yandexplusplus->getPaymentStatus($this->request->post['label']);
		        if (!isset($paystat['status'])){$paystat['status'] = 0;}
		        if ($paystat['status'] != 1){
		  		echo "HTTP 200 OK";
		  		}
		  		else{
		  			exit();
		  		}				
		  		 
			$query = $this->db->query ("INSERT INTO `" . DB_PREFIX . "yandexplusplus` SET `num_order` = '".(int)$order_info['order_id']."' , `sum` = '".$this->db->escape($this->request->post['withdraw_amount'])."' , `date_enroled` = '".$this->db->escape($this->request->post['datetime'])."', `date_created` = '".$this->db->escape($order_info['date_added'])."', `user` = '".$this->db->escape($order_info['payment_firstname'])." ".$this->db->escape($order_info['payment_lastname'])."', `email` = '".$this->db->escape($order_info['email'])."', `status` = '1', `sender` = '".(int)$this->request->post['operation_id']."' ");
			if ($this->request->post['notification_type'] == 'p2p-incoming'){

				if($this->config->get('payment_yandexplusplus_createorder_or_notcreate') && $order_info['order_status_id'] != $this->config->get('payment_yandexplusplus_on_status_id')){
					$this->language->load('extension/payment/yandexplusplus');
						if ($this->config->get('payment_yandexplusplus_mail_instruction_attach')){
							$inv_id = $order_info['order_id'];
							if ($this->config->get('payment_yandexplusplus_fixen')) {
								if ($this->config->get('payment_yandexplusplus_fixen') == 'fix'){
								    $out_summ = $this->config->get('payment_yandexplusplus_fixen_amount');
								}
								else{
								    $out_summ = $order_info['total'] * $this->config->get('payment_yandexplusplus_fixen_amount') / 100;
								}
							}
							else{
								$out_summ = $order_info['total'];
							}
							$action= $order_info['store_url'] . 'index.php?route=extension/payment/yandexplusplus';
							$online_url = $action .

							'&order_id='	. $order_info['order_id'];

					    	$comment  = $this->language->get('text_instruction') . "\n\n";
							$comment .= $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_mail_instruction_' . $order_info['language_id']));
					    	$comment = htmlspecialchars_decode($comment);
					    	$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_order_status_id'), $comment, true);
				    	}
				    	else{
							$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_order_status_id'), true);
						}

						if ($this->config->get('payment_yandexplusplus_success_alert_customer')){
				        	if ($this->config->get('payment_yandexplusplus_success_comment_attach')) {
				          		$message = $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_success_comment_' . $order_info['language_id']));
				          		$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_order_status_id'), $message, true);
				        	}
				        	else{
				          		$message = '';
				          		$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_order_status_id'), $message, true);
				        	}
				    	}
				}

				else{

					$yandexpay_status_id = $this->config->get('payment_yandexplusplus_order_status_id');
			    	if ($this->config->get('payment_yandexplusplus_success_alert_customer')){
			        	if ($this->config->get('payment_yandexplusplus_success_comment_attach')) {
			          		$message = $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_success_comment_' . $order_info['language_id']));
			          		$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_order_status_id'), $message, true);
			        	}
			        	else{
			          		$message = '';
			          		$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_order_status_id'), $message, true);
			        	}
			    	}
			    	else{
			      			$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_order_status_id'), false);
			    	}

		    	}
			

		    	if ($this->config->get('payment_yandexplusplus_success_alert_admin')) {
		      
		       		$subject = sprintf(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'), $order_info['order_id']);
		        
		        	// Text 
			        $this->load->language('extension/payment/yandexplusplus');
			        $text = sprintf($this->language->get('success_admin_alert'), $order_info['order_id']) . "\n";
			        
			        
			      
			        $mail = new Mail(); 
			        $mail->protocol = $this->config->get('config_mail_protocol');
					$mail->parameter = $this->config->get('config_mail_parameter');
					$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
					$mail->smtp_username = $this->config->get('config_mail_smtp_username');
					$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
					$mail->smtp_port = $this->config->get('config_mail_smtp_port');
					$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
			        $mail->setTo($this->config->get('config_email'));
			        $mail->setFrom($this->config->get('config_email'));
			        $mail->setSender($order_info['store_name']);
			        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
			        $mail->setText(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
			        $mail->send();
		        
		        	// Send to additional alert emails
		        	$emails = explode(',', $this->config->get('config_alert_emails'));
		        
		        	foreach ($emails as $email) {
		          		if ($email && preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $email)) {
		            		$mail->setTo($email);
		            		$mail->send();
		          		}
		        	}
		    	}
		    }
		    if ($this->request->post['notification_type'] == 'card-incoming'){

		    	if($this->config->get('payment_yandexplusplus_card_createorder_or_notcreate') && $order_info['order_status_id'] != $this->config->get('payment_yandexplusplus_card_on_status_id')){

		    		$this->language->load('extension/payment/yandexplusplus_card');
						if ($this->config->get('payment_yandexplusplus_card_mail_instruction_attach')){
							$inv_id = $order_info['order_id'];
							if ($this->config->get('payment_yandexplusplus_card_fixen')) {
								if ($this->config->get('payment_yandexplusplus_card_fixen') == 'fix'){
								    $out_summ = $this->config->get('payment_yandexplusplus_card_fixen_amount');
								}
								else{
								    $out_summ = $order_info['total'] * $this->config->get('payment_yandexplusplus_card_fixen_amount') / 100;
								}
							}
							else{
								$out_summ = $order_info['total'];
							}
							$action= $order_info['store_url'] . 'index.php?route=extension/payment/yandexplusplus';
							$online_url = $action .

							'&order_id='	. $order_info['order_id'];

					    	$comment  = $this->language->get('text_instruction') . "\n\n";
							$comment .= $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_card_mail_instruction_' . $order_info['language_id']));
					    	$comment = htmlspecialchars_decode($comment);
					    	$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_card_order_status_id'), $comment, true);
				    	}
				    	else{
							$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_card_order_status_id'), true);
						}

						if ($this->config->get('payment_yandexplusplus_card_success_alert_customer')){
				        	if ($this->config->get('payment_yandexplusplus_card_success_comment_attach')) {
				          		$message = $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_card_success_comment_' . $order_info['language_id']));
				          		$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_card_order_status_id'), $message, true);
				        	}
				        else{
				          $message = '';
				          $this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_card_order_status_id'), $message, true);
				        }
				    }

		    	}

		    	else {

			    	$yandexpay_status_id = $this->config->get('payment_yandexplusplus_card_order_status_id');
			    	if ($this->config->get('payment_yandexplusplus_card_success_alert_customer')){
			        	if ($this->config->get('payment_yandexplusplus_card_success_comment_attach')) {
			          		$message = $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_card_success_comment_' . $order_info['language_id']));
			          		$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_card_order_status_id'), $message, true);
			        	}
			        else{
			          $message = '';
			          $this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_card_order_status_id'), $message, true);
			        }
			    }
			    else{
			      	$this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_yandexplusplus_card_order_status_id'), false);
			      	
			    }

			}

		    if ($this->config->get('payment_yandexplusplus_card_success_alert_admin')) {
		      
		        $subject = sprintf(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'), $order_info['order_id']);
		        
		        // Text 
		        $this->load->language('extension/payment/yandexplusplus_card');
		        $text = sprintf($this->language->get('success_admin_alert'), $order_info['order_id']) . "\n";
		        
		        
		      
		        $mail = new Mail(); 
		        $mail->protocol = $this->config->get('config_mail_protocol');
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
				$mail->smtp_username = $this->config->get('config_mail_smtp_username');
				$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail->smtp_port = $this->config->get('config_mail_smtp_port');
				$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
		        $mail->setTo($this->config->get('config_email'));
		        $mail->setFrom($this->config->get('config_email'));
		        $mail->setSender($order_info['store_name']);
		        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
		        $mail->setText(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
		        $mail->send();
		        
		        // Send to additional alert emails
		        $emails = explode(',', $this->config->get('config_alert_emails'));
		        
		        foreach ($emails as $email) {
		          if ($email && preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $email)) {
		            $mail->setTo($email);
		            $mail->send();
		          }
		        }
		   	} 
		  }
		}
		  else{
		  	echo "bad sign\n";
		  	$this->log->write('YandexPlusPlus Error: Hash not equal');
		  exit();
		  } 
		}
		else{
		echo "Order sum false\n";
		$this->log->write('YandexPlusPlus Error: Amount of payment not equal');
	  exit();
	}
  }
  else{
  echo "No data";
  }
}
  public function status() {
  	if (isset($this->request->get['order_id'])){
   		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->request->get['order_id']);
  	}
  	else{
   		echo 'No order';
   		exit();
  	}

  	if ($this->request->get['order_id'] == $order_info['order_id']){
      $inv_id = $order_info['order_id'];
      $this->load->language('extension/payment/yandexplusplus');
      $data['heading_title'] = $this->language->get('heading_title');
      $this->document->setTitle($this->language->get('heading_title'));
      $data['button_continue'] = $this->language->get('button_ok');
      $data['inv_id'] = $order_info['order_id'];

      $platp = substr(md5($order_info['order_id'] . $this->config->get('config_encryption')), 0, 12);
        if ($this->request->get['code'] != $platp) {
        	$this->response->redirect($this->url->link('error/not_found'));
        }

      $this->session->data['mspage_order_id_plus'] = $order_info['order_id'];

      $action= $this->url->link('extension/payment/yandexplusplus/go');
				$online_url = $action .
				'&code=' . substr(md5($order_info['order_id'] . $this->config->get('config_encryption')), 0, 12) .
				'&order_id=' . $order_info['order_id'];

      $data['text_message'] = '';

      $this->load->model('extension/payment/yandexplusplus');

      
      if($order_info['payment_code'] == 'yandexplusplus'){
      	if ($this->config->get('payment_yandexplusplus_fixen')) {
			if ($this->config->get('payment_yandexplusplus_fixen') == 'fix'){
			    $out_summ = $this->config->get('payment_yandexplusplus_fixen_amount');
			}
			else{
			    $out_summ = $order_info['total'] * $this->config->get('payment_yandexplusplus_fixen_amount') / 100;
			}
		}
		else{
			$out_summ = $order_info['total'];
		}
      	if ($order_info['order_status_id'] == $this->config->get('payment_yandexplusplus_order_status_id')) {

	      	if (isset($this->request->get['first'])) {
		        $data['text_message'] .=  $this->language->get('success_text_first');
		    }

      	  	if($this->config->get('payment_yandexplusplus_createorder_or_notcreate')  && isset($this->request->get['first'])){
		        
			            $this->cart->clear();
			            
			            unset($this->session->data['shipping_method']);
			            unset($this->session->data['shipping_methods']);
			            unset($this->session->data['payment_method']);
			            unset($this->session->data['payment_methods']);
			            unset($this->session->data['guest']);
			            unset($this->session->data['comment']);
			            unset($this->session->data['order_id']);  
			            unset($this->session->data['coupon']);
			            unset($this->session->data['reward']);
			            unset($this->session->data['voucher']);
			            unset($this->session->data['vouchers']);
		    }

	      if ($this->config->get('payment_yandexplusplus_success_page_text_attach')) {

	      $data['text_message'] .= $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_success_page_text_' . $this->config->get('config_language_id')));
	      }
	      else{
	          $data['text_message'] .=  sprintf($this->language->get('success_text'), $inv_id);
	      }
	    }
	    else{

	      	if (isset($this->request->get['first']) && $order_info['order_status_id'] == $this->config->get('payment_yandexplusplus_on_status_id')) {
		        $data['text_message'] .=  $this->language->get('success_text_first');
		    }

	    	if ($this->config->get('payment_yandexplusplus_waiting_page_text_attach')) {

	      		$data['text_message'] .= $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_waiting_page_text_attach' . $this->config->get('config_language_id')));
	      }
	      else{
	      		if($order_info['order_status_id'] == $this->config->get('payment_yandexplusplus_on_status_id')){
	         		$data['text_message'] .=  sprintf($this->language->get('success_text_wait'), $inv_id, $online_url);
	         	}
	         	else{
	          		$data['text_message'] .=  sprintf($this->language->get('success_text_wait_noorder'), $online_url);
	          	}
	      }
	    }
	  }
	  if($order_info['payment_code'] == 'yandexplusplus_card'){
	  	
	  	if ($this->config->get('payment_yandexplusplus_card_fixen')) {
			if ($this->config->get('payment_yandexplusplus_card_fixen') == 'fix'){
			    $out_summ = $this->config->get('payment_yandexplusplus_card_fixen_amount');
			}
			else{
			    $out_summ = $order_info['total'] * $this->config->get('payment_yandexplusplus_card_fixen_amount') / 100;
			}
		}
		else{
			$out_summ = $order_info['total'];
		}
	      if ($order_info['order_status_id'] == $this->config->get('payment_yandexplusplus_card_order_status_id')) {

	      	if (isset($this->request->get['first'])) {
			    $data['text_message'] .=  $this->language->get('success_text_first');
			}

	      	if($this->config->get('payment_yandexplusplus_card_createorder_or_notcreate')){
		        
		            		$this->cart->clear();
		            
				            unset($this->session->data['shipping_method']);
				            unset($this->session->data['shipping_methods']);
				            unset($this->session->data['payment_method']);
				            unset($this->session->data['payment_methods']);
				            unset($this->session->data['guest']);
				            unset($this->session->data['comment']);
				            unset($this->session->data['order_id']);  
				            unset($this->session->data['coupon']);
				            unset($this->session->data['reward']);
				            unset($this->session->data['voucher']);
				            unset($this->session->data['vouchers']);
		    		}

	      if ($this->config->get('payment_yandexplusplus_card_success_page_text_attach')) {
	             
	      	$data['text_message'] .= $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_card_success_page_text_' . $this->config->get('config_language_id')));
	      }
	      else{
	          $data['text_message'] .=  sprintf($this->language->get('success_text'), $inv_id);
	      }
	    }
	    else{

	    	if (isset($this->request->get['first']) && $order_info['order_status_id'] == $this->config->get('payment_yandexplusplus_card_on_status_id')) {
				$data['text_message'] .=  $this->language->get('success_text_first');
			}
			
	    	if ($this->config->get('payment_yandexplusplus_card_waiting_page_text_attach')) {

	      		$data['text_message'] .= $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, $this->config->get('payment_yandexplusplus_card_waiting_page_text_' . $this->config->get('config_language_id')));
	      }
	      else{
	      	if($order_info['order_status_id'] == $this->config->get('payment_yandexplusplus_card_on_status_id')){
	        	$data['text_message'] .=  sprintf($this->language->get('success_text_wait'), $inv_id, $online_url);
	      	}
	      	else{
	      		$data['text_message'] .=  sprintf($this->language->get('success_text_wait_noorder'), $online_url);
	      	}
	      }
	    }
	  }
	  
      if ($this->customer->isLogged()) {
        
        		if($order_info['payment_code'] == 'yandexplusplus'){
        			if(!$this->config->get('payment_yandexplusplus_createorder_or_notcreate')){ 
        				$data['text_message'] .=  sprintf($this->language->get('success_text_loged'), $this->url->link('account/order', '', 'SSL'), $this->url->link('account/order/info&order_id=' . $inv_id, '', 'SSL'));
        			}
        			else{
        				if ($order_info['order_status_id'] == $this->config->get('payment_yandexplusplus_order_status_id')) {
        					$data['text_message'] .=  sprintf($this->language->get('success_text_loged'), $this->url->link('account/order', '', 'SSL'), $this->url->link('account/order/info&order_id=' . $inv_id, '', 'SSL'));
        				}
        			}
	        		if ($order_info['order_status_id'] != $this->config->get('payment_yandexplusplus_order_status_id')) {
	        			if($order_info['order_status_id'] == $this->config->get('payment_yandexplusplus_on_status_id')){
	        				$data['text_message'] .=  sprintf($this->language->get('waiting_text_loged'), $this->url->link('account/order', '', 'SSL'));
	        			}
	        		}
	    		}
	    		if($order_info['payment_code'] == 'payment_yandexplusplus_card'){
	    			if(!$this->config->get('payment_yandexplusplus_card_createorder_or_notcreate')){ 
	    				$data['text_message'] .=  sprintf($this->language->get('success_text_loged'), $this->url->link('account/order', '', 'SSL'), $this->url->link('account/order/info&order_id=' . $inv_id, '', 'SSL'));
	    			}
	    			else{
        				if ($order_info['order_status_id'] == $this->config->get('payment_yandexplusplus_card_order_status_id')) {
        					$data['text_message'] .=  sprintf($this->language->get('success_text_loged'), $this->url->link('account/order', '', 'SSL'), $this->url->link('account/order/info&order_id=' . $inv_id, '', 'SSL'));
        				}
        			}
	    			if ($order_info['order_status_id'] != $this->config->get('payment_yandexplusplus_card_order_status_id')) {
	    				if($order_info['order_status_id'] == $this->config->get('payment_yandexplusplus_card_on_status_id')){
	        				$data['text_message'] .=  sprintf($this->language->get('waiting_text_loged'), $this->url->link('account/order', '', 'SSL'));
	        			}
	        		}
	    		}
      }
      
      $data['breadcrumbs'] = array();

      $data['breadcrumbs'][] = array(
        'text'      => $this->language->get('text_home'),
        'href'      => $this->url->link('common/home')
      );
      
      if (isset($this->request->get['first'])) {
        $this->language->load('checkout/success');
        $data['breadcrumbs'][] = array(
          'href'      => $this->url->link('checkout/cart'),
          'text'      => $this->language->get('text_basket')
        );
        
        $data['breadcrumbs'][] = array(
          'href'      => $this->url->link('checkout/checkout', '', 'SSL'),
          'text'      => $this->language->get('text_checkout')
        );
        $data['continue'] = $this->url->link('common/home');
      }
      else{
        if ($this->customer->isLogged()) {
          $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('lich'),
            'href'      => $this->url->link('extension/payment/payment', '', 'SSL')
          );

          $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('history'),
            'href'      => $this->url->link('account/order', '', 'SSL')
          );
          $data['continue'] = $this->url->link('account/order', '', 'SSL');
        }
        else{
          $data['continue'] = $this->url->link('common/home');
        }
      }

      	$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
    }
    else{
      echo "No data";
    }
  }

  public function amail(&$route, &$args) {
  		if (isset($args[0])) {
			$order_id = $args[0];
		} else {
			$order_id = 0;
		}

		if (isset($args[1])) {
			$order_status_id = $args[1];
		} else {
			$order_status_id = 0;
		}	
		
		if (isset($args[3])) {
			$notify = $args[3];
		} else {
			$notify = '';
		}
						

		$order_info = $this->model_checkout_order->getOrder($order_id);
		
		if ($order_info) {
			if ($order_info['order_status_id'] && $order_status_id ) {
					if ($this->config->get('payment_yandexplusplus_on_status_id') == $order_status_id && $order_info['payment_code'] == 'yandexplusplus' || $this->config->get('payment_yandexplusplus_card_on_status_id') == $order_status_id && $order_info['payment_code'] == 'yandexplusplus_card' ) {
			            $this->load->model('extension/payment/yandexplusplus');
			            $this->language->load('extension/payment/'.$order_info['payment_code']);
			            $merchant_url = $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, 'href');
			            $merchant_url = "<a href=' ".$merchant_url."'>" . $merchant_url . "</a>";
			            $merchant_url = strip_tags(html_entity_decode($merchant_url, ENT_QUOTES, 'UTF-8'));
			            $message = sprintf($this->language->get('text_stat'), $merchant_url);


						$args[2] = $message . $args[2];
			            
			        }

			}		
		}

  }

  public function aclink (&$route, &$args, &$output) {

  	$this->load->model('extension/payment/yandexplusplus');

  	foreach ($args['orders'] as $order) {
  		$method = $this->model_extension_payment_yandexplusplus->getOrderMethod($order['order_id']);
  		if ($method['payment_code'] == 'yandexplusplus' || $method['payment_code'] == 'yandexplusplus_card') {

  			if ($this->config->get('payment_' . $method['payment_code']. '_status')){

	  			$ostatus = $this->model_extension_payment_yandexplusplus->getOrderStatus($this->config->get('payment_' . $method['payment_code'].'_on_status_id'));
		  		if ($ostatus['name'] == $order['status']){

		  			$this->language->load('extension/payment/'. $method['payment_code']);

		  			if($this->config->get('payment_' . $method['payment_code'].'_style')) {
	                    $yu_style = 'btn btn-info';
	                } else {
	                    $yu_style = 'yandex_button';
	                }

		  			//$args['content_top'] .= '<script> $(document).ready(function(){
		            //        $(\'a.btn-info[href$="order_id='.$order['order_id'].'"]\').before("<a class=\"'.$yu_style.'\" href=\"index.php?route=extension/payment/yandexplusplus/go&code=' . substr(md5($order['order_id'] . $this->config->get('config_encryption')), 0, 12) .'&order_id='.$order['order_id'].'\" >'. $this->language->get('pay_text').'</a> "); }); </script>';

					$data['order_id'] = $order['order_id'];
					$data['yu_style'] = $yu_style ;
					$data['href'] = 'index.php?route=extension/payment/yandexplusplus/go&code=' . substr(md5($order['order_id'] . $this->config->get('config_encryption')), 0, 12) .'&order_id='.$order['order_id'];
					$data['pay_text'] = $this->language->get('pay_text');

					$args['content_top'] .= $this->load->view('extension/payment/yandexplusplus_aclink', $data);

				}
			}
		}

	}

  }

}
?>