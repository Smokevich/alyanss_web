<?php
class ControllerExtensionPaymentYandexpluspluscard extends Controller {
	private $error = array();

	public function index() {
		$data['version'] = '1.01';
//////////////////////////////////////////////// DECODED
		$this->load->language("extension/payment/yandexplusplus_card");
		$this->document->setTitle($this->language->get("heading_title"));
		$this->load->model("setting/setting");

		$lickey = "okformat";
		$domain = "name";

		if (($this->request->server["REQUEST_METHOD"] == "POST") && ($this->validate())) {
			$this->model_setting_setting->editSetting("payment_yandexplusplus_card", $this->request->post);
			$this->session->data["success"] = $this->language->get("text_success");
			$this->response->redirect($this->url->link("marketplace/extension", "user_token=" . $this->session->data["user_token"] . "&type=payment", true));
		}
		
		if (isset($this->error["license"])) {
			$data["error_license"] = $this->error["license"];
		} else {
			$data["error_license"] = "";
		}
		if (isset($this->request->post["payment_yandexplusplus_card_license"])) {
			$data["payment_yandexplusplus_card_license"] = $this->request->post["payment_yandexplusplus_card_license"];
		} else {
			$data["payment_yandexplusplus_card_license"] = $this->config->get("payment_yandexplusplus_card_license");
		}

		$data["entry_license"] = $this->language->get("entry_license");
		$data["heading_title"] = $this->language->get("heading_title");
		$data["text_enabled"] = $this->language->get("text_enabled");
		$data["text_disabled"] = $this->language->get("text_disabled");
		$data["text_all_zones"] = $this->language->get("text_all_zones");
		$data["text_liqpay"] = $this->language->get("text_liqpay");
		$data["text_card"] = $this->language->get("text_card");
		$data["text_yes"] = $this->language->get("text_yes");
		$data["text_no"] = $this->language->get("text_no");
		$data["entry_login"] = $this->language->get("entry_login");
		$data["entry_password"] = $this->language->get("entry_password");
		$data["copy_result_url"] = HTTPS_CATALOG . "index.php?route=extension/payment/yandexplusplus/callback";
		$data["entry_payment_yandexplusplus_card_name_tab"] = $this->language->get("entry_payment_yandexplusplus_card_name_tab");
		$data["text_my"] = $this->language->get("text_my");
		$data["text_default"] = $this->language->get("text_default");
		$data["entry_komis"] = $this->language->get("entry_komis");
		$data["entry_maxpay"] = $this->language->get("entry_maxpay");
		$data["entry_style"] = $this->language->get("entry_style");
		$data["entry_on_status"] = $this->language->get("entry_on_status");
		$data["entry_order_status"] = $this->language->get("entry_order_status");
		$data["entry_geo_zone"] = $this->language->get("entry_geo_zone");
		$data["entry_status"] = $this->language->get("entry_status");
		$data["entry_sort_order"] = $this->language->get("entry_sort_order");
		$data["button_save"] = $this->language->get("button_save");
		$data["button_cancel"] = $this->language->get("button_cancel");
		$data["tab_general"] = $this->language->get("tab_general");
		$data["entry_instruction_tab"] = $this->language->get("entry_instruction_tab");
		$data["entry_instruction"] = $this->language->get("entry_instruction");
		$data["entry_mail_instruction_tab"] = $this->language->get("entry_mail_instruction_tab");
		$data["entry_mail_instruction"] = $this->language->get("entry_mail_instruction");
		$data["entry_hrefpage_tab"] = $this->language->get("entry_hrefpage_tab");
		$data["entry_hrefpage"] = $this->language->get("entry_hrefpage");
		$data["entry_success_comment_tab"] = $this->language->get("entry_success_comment_tab");
		$data["entry_success_comment"] = $this->language->get("entry_success_comment");
		$data["entry_name"] = $this->language->get("entry_name");
		$data["entry_success_alert_admin_tab"] = $this->language->get("entry_success_alert_admin_tab");
		$data["entry_success_alert_customer_tab"] = $this->language->get("entry_success_alert_customer_tab");
		$data["entry_success_page_tab"] = $this->language->get("entry_success_page_tab");
		$data["entry_success_page_text"] = $this->language->get("entry_success_page_text");
		$data["entry_waiting_page_tab"] = $this->language->get("entry_waiting_page_tab");
		$data["entry_waiting_page_text"] = $this->language->get("entry_waiting_page_text");
		$data["entry_later"] = $this->language->get("entry_button_later");
		$data["entry_fixen"] = $this->language->get("entry_fixen");
		$data["entry_fixen_order"] = $this->language->get("entry_fixen_order");
		$data["entry_fixen_proc"] = $this->language->get("entry_fixen_proc");
		$data["entry_fixen_fix"] = $this->language->get("entry_fixen_fix");
		$data["entry_fixen_amount"] = $this->language->get("entry_fixen_amount");
		$data["text_createorder_or_notcreate"] = $this->language->get("text_createorder_or_notcreate");

		$domain = "name";

		if (isset($this->error["warning"])) {
			$data["error_warning"] = $this->error["warning"];
		} else {
			$data["error_warning"] = "";
		}
		if (isset($this->error["login"])) {
			$data["error_login"] = $this->error["login"];
		} else {
			$data["error_login"] = "";
		}
		if (isset($this->error["password"])) {
			$data["error_password"] = $this->error["password"];
		} else {
			$data["error_password"] = "";
		}

		$this->load->model("localisation/language");

		$languages = $this->model_localisation_language->getLanguages();

		$data["languages"] = $languages;

		foreach ($languages as $language) {
			if (isset($this->request->post["payment_yandexplusplus_card_name_" . $language["language_id"]])) {
				$data["payment_yandexplusplus_card_name_"][$language["language_id"]] = $this->request->post["payment_yandexplusplus_card_name_" . $language["language_id"]];
			} else {
				$data["payment_yandexplusplus_card_name_"][$language["language_id"]] = $this->config->get("payment_yandexplusplus_card_name_" . $language["language_id"]);
			}
			if (isset($this->request->post["payment_yandexplusplus_card_instruction_" . $language["language_id"]])) {
				$data["payment_yandexplusplus_card_instruction_"][$language["language_id"]] = $this->request->post["payment_yandexplusplus_card_instruction_" . $language["language_id"]];
			} else {
				$data["payment_yandexplusplus_card_instruction_"][$language["language_id"]] = $this->config->get("payment_yandexplusplus_card_instruction_" . $language["language_id"]);
			}
			if (isset($this->request->post["payment_yandexplusplus_card_mail_instruction_" . $language["language_id"]])) {
				$data["payment_yandexplusplus_card_mail_instruction_"][$language["language_id"]] = $this->request->post["payment_yandexplusplus_card_mail_instruction_" . $language["language_id"]];
			} else {
				$data["payment_yandexplusplus_card_mail_instruction_"][$language["language_id"]] = $this->config->get("payment_yandexplusplus_card_mail_instruction_" . $language["language_id"]);
			}
			if (isset($this->request->post["payment_yandexplusplus_card_success_comment_" . $language["language_id"]])) {
				$data["payment_yandexplusplus_card_success_comment_"][$language["language_id"]] = $this->request->post["payment_yandexplusplus_card_success_comment_" . $language["language_id"]];
			} else {
				$data["payment_yandexplusplus_card_success_comment_"][$language["language_id"]] = $this->config->get("payment_yandexplusplus_card_success_comment_" . $language["language_id"]);
			}
			if (isset($this->request->post["payment_yandexplusplus_card_success_page_text_" . $language["language_id"]])) {
				$data["payment_yandexplusplus_card_success_page_text_"][$language["language_id"]] = $this->request->post["payment_yandexplusplus_card_success_page_text_" . $language["language_id"]];
			} else {
				$data["payment_yandexplusplus_card_success_page_text_"][$language["language_id"]] = $this->config->get("payment_yandexplusplus_card_success_page_text_" . $language["language_id"]);
			}
			if (isset($this->request->post["payment_yandexplusplus_card_hrefpage_text_" . $language["language_id"]])) {
				$data["payment_yandexplusplus_card_hrefpage_text_"][$language["language_id"]] = $this->request->post["payment_yandexplusplus_card_hrefpage_text_" . $language["language_id"]];
			} else {
				$data["payment_yandexplusplus_card_hrefpage_text_"][$language["language_id"]] = $this->config->get("payment_yandexplusplus_card_hrefpage_text_" . $language["language_id"]);
			}
			if (isset($this->request->post["payment_yandexplusplus_card_waiting_page_text_" . $language["language_id"]])) {
				$data["payment_yandexplusplus_card_waiting_page_text_"][$language["language_id"]] = $this->request->post["payment_yandexplusplus_card_waiting_page_text_" . $language["language_id"]];
			} else {
				$data["payment_yandexplusplus_card_waiting_page_text_"][$language["language_id"]] = $this->config->get("payment_yandexplusplus_card_waiting_page_text_" . $language["language_id"]);
			}
		}
		if (isset($this->request->post["payment_yandexplusplus_card_komis"])) {
			$data["payment_yandexplusplus_card_komis"] = $this->request->post["payment_yandexplusplus_card_komis"];
		} else {
			$data["payment_yandexplusplus_card_komis"] = $this->config->get("payment_yandexplusplus_card_komis");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_maxpay"])) {
			$data["payment_yandexplusplus_card_maxpay"] = $this->request->post["payment_yandexplusplus_card_maxpay"];
		} else {
			$data["payment_yandexplusplus_card_maxpay"] = $this->config->get("payment_yandexplusplus_card_maxpay");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_style"])) {
			$data["payment_yandexplusplus_card_style"] = $this->request->post["payment_yandexplusplus_card_style"];
		} else {
			$data["payment_yandexplusplus_card_style"] = $this->config->get("payment_yandexplusplus_card_style");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_instruction_attach"])) {
			$data["payment_yandexplusplus_card_instruction_attach"] = $this->request->post["payment_yandexplusplus_card_instruction_attach"];
		} else {
			$data["payment_yandexplusplus_card_instruction_attach"] = $this->config->get("payment_yandexplusplus_card_instruction_attach");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_name_attach"])) {
			$data["payment_yandexplusplus_card_name_attach"] = $this->request->post["payment_yandexplusplus_card_name_attach"];
		} else {
			$data["payment_yandexplusplus_card_name_attach"] = $this->config->get("payment_yandexplusplus_card_name_attach");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_success_alert_admin"])) {
			$data["payment_yandexplusplus_card_success_alert_admin"] = $this->request->post["payment_yandexplusplus_card_success_alert_admin"];
		} else {
			$data["payment_yandexplusplus_card_success_alert_admin"] = $this->config->get("payment_yandexplusplus_card_success_alert_admin");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_success_alert_customer"])) {
			$data["payment_yandexplusplus_card_success_alert_customer"] = $this->request->post["payment_yandexplusplus_card_success_alert_customer"];
		} else {
			$data["payment_yandexplusplus_card_success_alert_customer"] = $this->config->get("payment_yandexplusplus_card_success_alert_customer");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_mail_instruction_attach"])) {
			$data["payment_yandexplusplus_card_mail_instruction_attach"] = $this->request->post["payment_yandexplusplus_card_mail_instruction_attach"];
		} else {
			$data["payment_yandexplusplus_card_mail_instruction_attach"] = $this->config->get("payment_yandexplusplus_card_mail_instruction_attach");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_success_comment_attach"])) {
			$data["payment_yandexplusplus_card_success_comment_attach"] = $this->request->post["payment_yandexplusplus_card_success_comment_attach"];
		} else {
			$data["payment_yandexplusplus_card_success_comment_attach"] = $this->config->get("payment_yandexplusplus_card_success_comment_attach");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_success_page_text_attach"])) {
			$data["payment_yandexplusplus_card_success_page_text_attach"] = $this->request->post["payment_yandexplusplus_card_success_page_text_attach"];
		} else {
			$data["payment_yandexplusplus_card_success_page_text_attach"] = $this->config->get("payment_yandexplusplus_card_success_page_text_attach");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_hrefpage_text_attach"])) {
			$data["payment_yandexplusplus_card_hrefpage_text_attach"] = $this->request->post["payment_yandexplusplus_card_hrefpage_text_attach"];
		} else {
			$data["payment_yandexplusplus_card_hrefpage_text_attach"] = $this->config->get("payment_yandexplusplus_card_hrefpage_text_attach");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_waiting_page_text_attach"])) {
			$data["payment_yandexplusplus_card_waiting_page_text_attach"] = $this->request->post["payment_yandexplusplus_card_waiting_page_text_attach"];
		} else {
			$data["payment_yandexplusplus_card_waiting_page_text_attach"] = $this->config->get("payment_yandexplusplus_card_waiting_page_text_attach");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_button_later"])) {
			$data["payment_yandexplusplus_card_button_later"] = $this->request->post["payment_yandexplusplus_card_button_later"];
		} else {
			$data["payment_yandexplusplus_card_button_later"] = $this->config->get("payment_yandexplusplus_card_button_later");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_fixen"])) {
			$data["payment_yandexplusplus_card_fixen"] = $this->request->post["payment_yandexplusplus_card_fixen"];
		} else {
			$data["payment_yandexplusplus_card_fixen"] = $this->config->get("payment_yandexplusplus_card_fixen");
		}
		if (isset($this->error["fixen"])) {
			$data["error_fixen"] = $this->error["fixen"];
		} else {
			$data["error_fixen"] = "";
		}
		if (isset($this->request->post["payment_yandexplusplus_card_fixen_amount"])) {
			$data["payment_yandexplusplus_card_fixen_amount"] = $this->request->post["payment_yandexplusplus_card_fixen_amount"];
		} else {
			$data["payment_yandexplusplus_card_fixen_amount"] = $this->config->get("payment_yandexplusplus_card_fixen_amount");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_createorder_or_notcreate"])) {
			$data["payment_yandexplusplus_card_createorder_or_notcreate"] = $this->request->post["payment_yandexplusplus_card_createorder_or_notcreate"];
		} else {
			$data["payment_yandexplusplus_card_createorder_or_notcreate"] = $this->config->get("payment_yandexplusplus_card_createorder_or_notcreate");
		}

		$lickey = "okformat";
		$domain = "name";

		$data["breadcrumbs"] = array();
		$data["breadcrumbs"][] = array("text" => $this->language->get("text_home"), "href" => $this->url->link("common/dashboard", "user_token=" . $this->session->data["user_token"], true));
		$data["breadcrumbs"][] = array("text" => $this->language->get("text_payment"), "href" => $this->url->link("marketplace/extension", "user_token=" . $this->session->data["user_token"] . "&type=payment", true));
		$data["breadcrumbs"][] = array("text" => $this->language->get("heading_title"), "href" => $this->url->link("extension/payment/yandexplusplus_card", "user_token=" . $this->session->data["user_token"], true));

		$data["action"] = $this->url->link("extension/payment/yandexplusplus_card", "user_token=" . $this->session->data["user_token"], true);
		$data["cancel"] = $this->url->link("marketplace/extension", "user_token=" . $this->session->data["user_token"] . "&type=payment", true);

		if (isset($this->request->post["payment_yandexplusplus_card_login"])) {
			$data["payment_yandexplusplus_card_login"] = $this->request->post["payment_yandexplusplus_card_login"];
		} else {
			$data["payment_yandexplusplus_card_login"] = $this->config->get("payment_yandexplusplus_card_login");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_password"])) {
			$data["payment_yandexplusplus_card_password"] = $this->request->post["payment_yandexplusplus_card_password"];
		} else {
			if ($this->config->get("payment_yandexplusplus_card_password")) {
				$data["payment_yandexplusplus_card_password"] = "*****";
			} else {
				$data["payment_yandexplusplus_card_password"] = "";
			}
		}
		if (isset($this->request->post["payment_yandexplusplus_card_on_status_id"])) {
			$data["payment_yandexplusplus_card_on_status_id"] = $this->request->post["payment_yandexplusplus_card_on_status_id"];
		} else {
			$data["payment_yandexplusplus_card_on_status_id"] = $this->config->get("payment_yandexplusplus_card_on_status_id");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_order_status_id"])) {
			$data["payment_yandexplusplus_card_order_status_id"] = $this->request->post["payment_yandexplusplus_card_order_status_id"];
		} else {
			$data["payment_yandexplusplus_card_order_status_id"] = $this->config->get("payment_yandexplusplus_card_order_status_id");
		}

		$this->load->model("localisation/order_status");

		$data["order_statuses"] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post["payment_yandexplusplus_card_geo_zone_id"])) {
			$data["payment_yandexplusplus_card_geo_zone_id"] = $this->request->post["payment_yandexplusplus_card_geo_zone_id"];
		} else {
			$data["payment_yandexplusplus_card_geo_zone_id"] = $this->config->get("payment_yandexplusplus_card_geo_zone_id");
		}
		$this->load->model("localisation/geo_zone");

		$data["geo_zones"] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post["payment_yandexplusplus_card_status"])) {
			$data["payment_yandexplusplus_card_status"] = $this->request->post["payment_yandexplusplus_card_status"];
		} else {
			$data["payment_yandexplusplus_card_status"] = $this->config->get("payment_yandexplusplus_card_status");
		}
		if (isset($this->request->post["payment_yandexplusplus_card_sort_order"])) {
			$data["payment_yandexplusplus_card_sort_order"] = $this->request->post["payment_yandexplusplus_card_sort_order"];
		} else {
			$data["payment_yandexplusplus_card_sort_order"] = $this->config->get("payment_yandexplusplus_card_sort_order");
		}
		foreach ($data["languages"] as $key => $language) {
			$data["languages"][$key]["imgsrc"] = "language/" . $language["code"] . "/" . $language["code"] . ".png";
		}

//////////////////////////////////////////////// DECODED
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/yandexplusplus_card', $data));
	}
  public function install() {
     $query = $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "yandexplusplus (yandex_id INT(11) AUTO_INCREMENT, num_order INT(8), sum DECIMAL(15,2), user TEXT, email TEXT, status INT(1), date_created DATETIME, date_enroled DATE, sender TEXT, label TEXT, PRIMARY KEY (yandex_id))");

     if (!$this->model_setting_event->getEventByCode('yandexplusplus_admin_column_left')){
	    $code = "yandexplusplus_admin_column_left";
		$trigger = "admin/view/common/column_left/before";
		$action = "extension/payment/yandexplusplus/amenu";
	 	$this->model_setting_event->addEvent($code, $trigger, $action);
	 

	 	$code = "yandexplusplus_mail_order_add";
		$trigger = "catalog/model/checkout/order/addOrderHistory/before";
		$action = "extension/payment/yandexplusplus/amail";
	 	$this->model_setting_event->addEvent($code, $trigger, $action);

	  	$code = "yandexplusplus_account_order_link_add";
		$trigger = "catalog/view/account/order_list/before";
		$action = "extension/payment/yandexplusplus/acLink";
	 	$this->model_setting_event->addEvent($code, $trigger, $action);
	 }

  }

  public function uninstall() {
	$extensions = $this->model_setting_extension->getInstalled('payment'); 
	
	if (!in_array('yandexplusplus', $extensions)) {	
		$this->model_setting_event->deleteEventByCode('yandexplusplus_admin_column_left');
		$this->model_setting_event->deleteEventByCode('yandexplusplus_mail_order_add');
		$this->model_setting_event->deleteEventByCode('yandexplusplus_account_order_link_add');
	}

  }

  public function status() {
  	$this->load->language('extension/payment/yandexplusplus_card');
	$this->document->setTitle ($this->language->get('heading_title'));
    $data['heading_title'] = $this->language->get('heading_title');
    $data['status_title'] = $this->language->get('status_title');
    
    $this->load->model('extension/payment/yandexplusplus_card');
    $viewstatuses = $this->model_extension_payment_yandexplusplus_card->getStatus();
    $data['viewstatuses'] = $viewstatuses;
    
    $data['breadcrumbs'] = array();
   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      =>  $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_order'),
			'href'      => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true),       		
      		'separator' => ' :: '
   		);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/payment_yandexplusplus_card_view_status.tpl', $data));
    
  		}

		private function validate() {
		
		if (!$this->user->hasPermission('modify', 'extension/payment/yandexplusplus_card')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_yandexplusplus_card_login']) {
			$this->error['login'] = $this->language->get('error_login');
		}

		if (!$this->request->post['payment_yandexplusplus_card_password']) {
			$this->error['password'] = $this->language->get('error_password');
		}

		if ($this->request->post['payment_yandexplusplus_card_password']) {
			$this->load->model('extension/payment/yandexplusplus_card');
			$keyen = $this->config->get('config_encryption');
			if ($this->request->post['payment_yandexplusplus_card_password'] == '*****'){$this->request->post['payment_yandexplusplus_card_password'] = $this->model_extension_payment_yandexplusplus_card->decrypt($this->config->get('payment_yandexplusplus_card_password'), $keyen);}
			$this->request->post['payment_yandexplusplus_card_password'] = $this->model_extension_payment_yandexplusplus_card->encrypt($this->request->post['payment_yandexplusplus_card_password'], $keyen);
  		}

  		if ($this->request->post['payment_yandexplusplus_card_fixen']) {
			if (!$this->request->post['payment_yandexplusplus_card_fixen_amount']) {
				$this->error['fixen'] = $this->language->get('error_fixen');
			}
		}



		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function order() {
		if ($this->config->get('payment_yandexplusplus_card_status')) {

			$this->load->language('extension/payment/yandexplusplus_card');
        	$data['text_href'] = $this->language->get('yandexplusplus_card_text');

			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$order_info = $this->model_sale_order->getOrder($order_id);

			$this->load->model('extension/payment/yandexplusplus');
       		$data['link'] = $this->model_extension_payment_yandexplusplus->getCustomFields($order_info, 'href');

			return $this->load->view('extension/payment/yandexplusplus_order', $data);
		}
	}
}
?>