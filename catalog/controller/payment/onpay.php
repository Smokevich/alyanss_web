<?php
class ControllerPaymentOnpay extends Controller 
{
	public function index() 
	{
  	$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');
		
		$order_id = $this->session->data['order_id'];
		
		$this->model_checkout_order->addOrderHistory($order_id, 1);
		
		$order_info = $this->model_checkout_order->getOrder($order_id);
    
    $data['pay_for'] = $order_id;
    $data['currency'] = 'RUR';
    $data['url_success'] = $this->url->link('payment/onpay/success', '', 'SSL');
    $data['url_fail'] = $this->url->link('payment/onpay/fail', '', 'SSL');
    $data['pay_mode'] = 'fix';
    
    $data['price'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data['price'] = $this->onpay_to_float($data['price']);
    
    $data['user_email'] = $order_info['email'];
    
    $onpay_key = $this->config->get('onpay_security');
    $data['md5'] = md5($data['pay_mode'].";".$data['price'].";" . $data['currency'] . ";" . $data['pay_for'] . ";".$this->config->get('onpay_convert').";$onpay_key");

    $data['pay_url'] = "http://secure.onpay.ru/pay/".$this->config->get('onpay_login').
    "?price_final=".$this->config->get('onpay_price_final').
    "&ln=".$this->config->get('onpay_ln').
    "&f=".$this->config->get('onpay_form_id').
    "&pay_mode=".$data['pay_mode'].
    "&pay_for=".$data['pay_for'].
    "&price=".$data['price'].
    "&ticker=".$data['currency'].
    "&convert=".$this->config->get('onpay_convert').
    "&md5=".$data['md5'].
    "&user_email=".urlencode($data['user_email']).
    "&url_success_enc=".urlencode($data['url_success']).
    "&url_fail_enc=".urlencode($data['url_fail']);
    
    /*echo ($data['pay_mode'].";".$data['price'].";" . $data['currency'] . ";" . $data['pay_for'] . ";".$this->config->get('onpay_convert').";$onpay_key");
    echo '<br>-------------<br>';
    echo $data['md5'];
    echo '<br>-------------<br>';
    echo $data['pay_url'];
    die;*/
    
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/onpay.tpl')) 
		{
			return $this->load->view($this->config->get('config_template') . '/template/payment/onpay.tpl', $data);
		}
		else
		{
			return $this->load->view('default/template/payment/onpay.tpl', $data);
		}
	}
  
  public function onpay_to_float($sum) {
    $sum = floatval($sum);
		if (strpos($sum, ".")) {
			$sum = round($sum, 2);
		} else {
			$sum = $sum.".0";
		}
		return $sum;
  }
  
  public function onpay_check($request) {
    $check = array(
      'type' => 'check',
      'pay_for' => intval($request['pay_for']),
      'amount' => $this->onpay_to_float($request['amount']),
      'currency' => trim($request['way']),
      'mode' => trim($request['mode']),
      'key' => $this->config->get('onpay_security'),
    );
    $check['signature_string'] = implode(";", $check);
    $check['signature'] = sha1($check['signature_string']);
    $checkOut = array(
      'type' => 'check',
      'status' => 'false',
      'pay_for' => intval($request['pay_for']),
      'key' => $this->config->get('onpay_security'),
    );
    $amount = floatval($request['amount']);
    if($this->onpay_validate($request, $check['signature'])) {
      $pay_for = $request['pay_for'];
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($pay_for);
      
      if( $order_info['order_status_id'] != $this->config->get('onpay_order_status_id')) 
			{
          $checkOut['status'] = 'true';
			}
    }
    $this->onpay_response($checkOut, $request);
  }

  function onpay_pay($request) {
    $_request = $request;
    $pay = array(
      'type' => 'pay',
      'pay_for' => intval($request['pay_for']),
      'payment.amount' => $this->onpay_to_float($request['payment']['amount']),
      'payment.currency' => trim($request['payment']['way']),
      'amount' => $this->onpay_to_float($request['balance']['amount']),
      'currency' => trim($request['balance']['way']),
      'key' => $this->config->get('onpay_security'),
    );
    $pay['signature_string'] = implode(";", $pay);
    $pay['signature'] = sha1($pay['signature_string']);
    $payOut = array(
      'type' => 'pay',
      'status' => 'false',
      'pay_for' => intval($request['pay_for']),
      'key' => $this->config->get('onpay_security'),
    );
    $amount = floatval($request['balance']['amount']);
    if($this->onpay_validate($request, $pay['signature'])) {
      $pay_for = $request['pay_for'];
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($pay_for);
      
      if( $order_info['order_status_id'] != $this->config->get('onpay_order_status_id')) 
			{
					$this->model_checkout_order->addOrderHistory($pay_for, $this->config->get('onpay_order_status_id'));
          $payOut['status'] = 'true';
			}
    }
    $this->onpay_response($payOut, $request);
  }
  
  public function onpay_response($response, $request) {
    $response['signature_string'] = implode(";", $response);
    $response['signature'] = sha1($response['signature_string']);
    $out = "{\"status\":{$response['status']},\"pay_for\":\"{$response['pay_for']}\",\"signature\":\"{$response['signature']}\"}";
    
    header("Content-type: application/json; charset=utf-8");
    echo iconv("cp1251", "utf-8", $out);
    die;
  }
  
  public function onpay_validate($request, $signature) {
    $request['pay_for'] = intval($request['pay_for']);
    if($request['signature'] != $signature) {
      return false;
    }
    return true;
  }

  public function onpay_get_data() {
    $ret = false;
    if(function_exists('json_decode')) {
      if(isset($GLOBALS['__inputData'])) {
        $ret = $GLOBALS['__inputData'];
      } elseif($hSource = fopen('php://input', 'r')) {
        $input = "";
        while (!feof($hSource)) {
          $input .= fread($hSource, 1024);
        }
        fclose($hSource);
        $input = trim($input);

        $ret = json_decode($input, true);
        if(is_null($ret)) $ret = json_decode(iconv("cp1251", "utf-8", $input), true);

        $GLOBALS['__inputData'] = $ret;
      }
    }
    return $ret;
  }
	
	public function status() 
	{
    $request = $this->onpay_get_data(); 
  
    if ($request['type'] == 'check') {
      $this->onpay_check($request);
    } else if($request['type'] == 'pay') {
      $this->onpay_pay($request);
    }
  
  
  
  
		if (isset($_POST["m_operation_id"]) && isset($_POST["m_sign"]))
		{
			$m_key = $this->config->get('onpay_security');
			$arHash = array(
				$_POST['m_operation_id'],
				$_POST['m_operation_ps'],
				$_POST['m_operation_date'],
				$_POST['m_operation_pay_date'],
				$_POST['m_shop'],
				$_POST['m_orderid'],
				$_POST['m_amount'],
				$_POST['m_curr'],
				$_POST['m_desc'],
				$_POST['m_status'],
				$m_key
			);
			$sign_hash = strtoupper(hash('sha256', implode(":", $arHash)));
			
			// проверка принадлежности ip списку доверенных ip
			$list_ip_str = str_replace(' ', '', $this->config->get('onpay_list_ip'));
			
			if ($list_ip_str != '') 
			{
				$list_ip = explode(',', $list_ip_str);
				$this_ip = $_SERVER['REMOTE_ADDR'];
				$this_ip_field = explode('.', $this_ip);
				$list_ip_field = array();
				$i = 0;
				$valid_ip = FALSE;
				foreach ($list_ip as $ip)
				{
					$ip_field[$i] = explode('.', $ip);
					if ((($this_ip_field[0] ==  $ip_field[$i][0]) or ($ip_field[$i][0] == '*')) and
						(($this_ip_field[1] ==  $ip_field[$i][1]) or ($ip_field[$i][1] == '*')) and
						(($this_ip_field[2] ==  $ip_field[$i][2]) or ($ip_field[$i][2] == '*')) and
						(($this_ip_field[3] ==  $ip_field[$i][3]) or ($ip_field[$i][3] == '*')))
						{
							$valid_ip = TRUE;
							break;
						}
					$i++;
				}
			}
			else
			{
				$valid_ip = TRUE;
			}
			
			// запись в логи если требуется
			$log_text = 
				"--------------------------------------------------------\n".
				"operation id		" . $_POST["m_operation_id"] . "\n".
				"operation ps		" . $_POST["m_operation_ps"] . "\n".
				"operation date		" . $_POST["m_operation_date"] . "\n".
				"operation pay date	" . $_POST["m_operation_pay_date"] . "\n".
				"shop				" . $_POST["m_shop"] . "\n".
				"order id			" . $_POST["m_orderid"] . "\n".
				"amount				" . $_POST["m_amount"] . "\n".
				"currency			" . $_POST["m_curr"] . "\n".
				"description		" . base64_decode($_POST["m_desc"]) . "\n".
				"status				" . $_POST["m_status"] . "\n".
				"sign				" . $_POST["m_sign"] . "\n\n";
			
			$log_file = $this->config->get('onpay_log_value');
			
			if (!empty($log_file))
			{
				file_put_contents($_SERVER['DOCUMENT_ROOT'] . $log_file, $log_text, FILE_APPEND);
			}
			
			$order_id = $_POST['m_orderid'];
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($order_id);
			
			// проверка цифровой подписи и ip сервера
			if ($_POST["m_sign"] == $sign_hash && $_POST['m_status'] == "success" && $valid_ip)
			{
				if( $order_info['order_status_id'] != $this->config->get('onpay_order_status_id')) 
				{
					$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('onpay_order_status_id'));
				}
				
				exit ($order_id . '|success');
			}
			else
			{	
				$this->model_checkout_order->addOrderHistory($order_id, 7);
				$to = $this->config->get('onpayonpay_admin_email');
				$subject = "Payment error";
				$message = "Failed to make the payment through the system Free-kassa for the following reasons:\n\n";
				
				if ($_POST["m_sign"] != $sign_hash)
				{
					$message .= " - Do not match the digital signature\n";
				}
				
				if ($_POST['m_status'] != "success")
				{
					$message .= " - The payment status is not success\n";
				}
				
				if (!$valid_ip)
				{
					$message .= " - the ip address of the server is not trusted\n";
					$message .= "   trusted ip: " . $this->config->get('list_ip') . "\n";
					$message .= "   ip of the current server: " . $_SERVER['REMOTE_ADDR'] . "\n";
				}
				
				$message .= "\n" . $log_text;
				$headers = "From: no-reply@" . $_SERVER['HTTP_SERVER']."\r\nContent-type: text/plain; charset=utf-8 \r\n";
				mail($to, $subject, $message, $headers);
				
				exit ($order_id . '|error');
			}
		}
	}
	
	public function fail()
	{
		$this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
		
		return TRUE;
	}
	
	public function success()
	{
		$this->response->redirect($this->url->link('checkout/success', '', 'SSL'));
		
		return TRUE;
	}
}
?>