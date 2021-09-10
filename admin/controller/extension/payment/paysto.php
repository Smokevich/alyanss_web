<?php

/**
 * Payment plagin for Paysto gateway
 *
 * @cms    Opencart
 * @author     dev@agaxx.ru (Alexey Agafonov)
 * @version    3.3.1
 * @license
 * @copyright  Copyright (c) 2018 Paysto (http://www.paysto.ru)
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Constant declaration
 */
define('paystoTitle', 'Payment with Paysto gateway');
define('paystoTitleDesc', 'Paysto gateway - paymant in Opencart 3.');
define('paystoDesc', 'Payment with Paysto gateway');
define('titleEdit', 'Edit');
define('textPayment', 'Payment');


/**
 * Class ControllerExtensionPaymentPaysto
 */
class ControllerExtensionPaymentPaysto extends Controller
{
    
    
    /** @var array Devafult servers */
    private $defaultServers = array(
        '95.213.209.218',
        '95.213.209.219',
        '95.213.209.220',
        '95.213.209.221',
        '95.213.209.222'
    );
    
    
    private $error = array();

    /**
     * Declaration of class client
     *
     * @var object
     */


    /**
     * Constructor
     *
     * ControllerExtensionPaymentPaysto constructor.
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $currentLanguage = $this->language->get('code');
    }


    /**
     * Install
     */
    public function install()
    {


    }


    /**
     * Admin plugin
     */
    public function index()
    {
        $this->load->language('extension/payment/paysto');
        $this->document->setTitle = $this->language->get('heading_title');
        $this->document->setTitle(paystoTitle);

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $this->model_setting_setting->editSetting('payment_paysto', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }


        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        //a version php check
        if (PHP_VERSION_ID < 50400) {
            $data['errorPhpVersion'] = paystoErrorPhpVersion;
        }

        //text for headings
        $data['headingTitle'] = paystoTitle;
        $data['heading_title'] = paystoTitle;
        $data['titleEdit'] = titleEdit;


        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_card'] = $this->language->get('text_card');

        $data['entry_x_login'] = $this->language->get('entry_x_login');
        $data['entry_secret_key'] = $this->language->get('entry_secret_key');
        $data['entry_description'] = $this->language->get('entry_description');
        $data['entry_useOnlyList'] = $this->language->get('entry_useOnlyList');
        $data['entry_serversList'] = $this->language->get('entry_serversList');

        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_tax'] = $this->language->get('entry_tax');
        $data['entry_log'] = $this->language->get('entry_log');
        $data['entry_class_tax'] = $this->language->get('entry_class_tax');
        $data['entry_text_tax'] = $this->language->get('entry_text_tax');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_add'] = $this->language->get('button_add');

        $data['tab_general'] = $this->language->get('tab_general');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['x_login'])) {
            $data['error_x_login'] = $this->error['x_login'];
        } else {
            $data['error_x_login'] = '';
        }

        if (isset($this->error['secret_key'])) {
            $data['error_secret_key'] = $this->error['secret_key'];
        } else {
            $data['error_secret_key'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => textPayment,
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => paystoTitle,
            'href' => $this->url->link('extension/payment/paysto', 'user_token=' . $this->session->data['user_token'], true)
        );


        $data['action'] = $this->url->link('extension/payment/paysto', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);


        if (isset($this->request->post['payment_paysto_x_login'])) {
            $data['payment_paysto_x_login'] = $this->request->post['payment_paysto_x_login'];
        } else {
            $data['payment_paysto_x_login'] = $this->config->get('payment_paysto_x_login');
        }

        if (isset($this->request->post['payment_paysto_secret_key'])) {
            $data['payment_paysto_secret_key'] = $this->request->post['payment_paysto_secret_key'];
        } else {
            $data['payment_paysto_secret_key'] = $this->config->get('payment_paysto_secret_key');
        }

        if (isset($this->request->post['payment_paysto_description'])) {
            $data['payment_paysto_description'] = $this->request->post['payment_paysto_description'];
        } else {
            $data['payment_paysto_description'] = $this->config->get('payment_paysto_description');
        }

        if (isset($this->request->post['paysto_useOnlyList'])) {
            $data['payment_paysto_useOnlyList'] = $this->request->post['payment_paysto_useOnlyList'];
        } else {
            $data['payment_paysto_useOnlyList'] = $this->config->get('payment_paysto_useOnlyList');
        }
        
        if (isset($this->request->post['payment_paysto_serversList'])) {
            $data['payment_paysto_serversList'] = $this->request->post['payment_paysto_serversList'];
        } else {
            if (!$this->config->get('paysto_serversList')) {
                $data['payment_paysto_serversList'] = implode("\n", $this->defaultServers);
            } else {
                $data['payment_paysto_serversList'] = $this->config->get('payment_paysto_serversList');
            }
        }
        
        if (isset($this->request->post['payment_paysto_order_status_id'])) {
            $data['payment_paysto_order_status_id'] = $this->request->post['payment_paysto_order_status_id'];
        } else {
            $data['payment_paysto_order_status_id'] = $this->config->get('payment_paysto_order_status_id');
        }

        $this->load->model('localisation/order_status');


        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_paysto_geo_zone_id'])) {
            $data['payment_paysto_geo_zone_id'] = $this->request->post['payment_paysto_geo_zone_id'];
        } else {
            $data['payment_paysto_geo_zone_id'] = $this->config->get('payment_paysto_geo_zone_id');
        }



        if (isset($this->request->post['payment_paysto_log'])) {
            $data['payment_paysto_log'] = $this->request->post['payment_paysto_log'];
        } else {
            $data['payment_paysto_log'] = $this->config->get('payment_paysto_log');
        }

        if (isset($this->request->post['payment_paysto_classes'])) {
            $data['payment_paysto_classes'] = $this->request->post['payment_paysto_classes'];
        } elseif ($this->config->get('payment_paysto_classes')) {
            $data['payment_paysto_classes'] = $this->config->get('payment_paysto_classes');
        } else {
            $data['payment_paysto_classes'] = array(
                array(
                    'payment_paysto_nalog' => 1,
                    'payment_paysto_tax_rule' => 1
                )
            );
        }

        $data['tax_rules'] = $this->get_tax_rules();

        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_paysto_status'])) {
            $data['payment_paysto_status'] = $this->request->post['payment_paysto_status'];
        } else {
            $data['payment_paysto_status'] = $this->config->get('payment_paysto_status');
        }

        if (isset($this->request->post['payment_paysto_sort_order'])) {
            $data['payment_paysto_sort_order'] = $this->request->post['payment_paysto_sort_order'];
        } else {
            $data['payment_paysto_sort_order'] = $this->config->get('payment_paysto_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // вывод вида
        $this->response->setOutput($this->load->view('extension/payment/paysto', $data));


    }
    
    
    /**
     * Get tax rate
     *
     * @return array
     */
    private function get_tax_rules()
    {
        return array(
            array(
                'id' => 1,
                'name' => 'With VAT'
            ),
            array(
                'id' => 0,
                'name' => 'Without VAT'
            )
        );
    }


    /**
     * Validation of settings
     *
     * @return bool
     */
    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/paysto')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_paysto_x_login']) {
            $this->error['x_login'] = $this->language->get('error_x_login');
        }

        if (!$this->request->post['payment_paysto_secret_key']) {
            $this->error['secret_key'] = $this->language->get('error_secret_key');
        }
    
        if (!$this->request->post['payment_paysto_description']) {
            $this->error['description'] = $this->language->get('error_description');
        }

        return !$this->error;
    }


}

