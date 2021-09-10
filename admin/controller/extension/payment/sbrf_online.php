<?php 
class ControllerExtensionPaymentSbrfOnline extends Controller {
    private $error = array(); 
    private $data = array(); 

    public function index() {
        $this->load->language('extension/payment/sbrf_online');
        $this->load->model('localisation/language');
        $this->load->model('localisation/order_status');
        $this->load->model('localisation/geo_zone');
        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->_trimData(array(
                 'payment_sbrf_online_minimal_order',
                 'payment_sbrf_online_maximal_order'
            ));

            $this->_replaceData(',', '.', array(
                 'payment_sbrf_online_minimal_order',
                 'payment_sbrf_online_maximal_order'
            ));

            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('payment_sbrf_online', $this->request->post);
            $this->session->data['success'] = sprintf($this->language->get('text_success'), $this->language->get('heading_title'));
            $this->response->redirect($this->makeUrl('marketplace/extension', 'type=payment'));
        }

        $scripts = array(
            'view/javascript/codemirror/lib/codemirror.js',
            'view/javascript/codemirror/lib/xml.js',
            'view/javascript/codemirror/lib/formatting.js',
            'view/javascript/summernote/summernote.js',
            'view/javascript/summernote/summernote-image-attributes.js',
            'view/javascript/summernote/opencart.js'
        );
        
        if (version_compare(VERSION, '3.1.0', '>=')) {
            $scripts[] = 'view/javascript/shoputils/sbrf_online/extension_compatibility.js';
        }
        
        $this->_addScript($scripts);

        $this->_addStyle(array(
            'view/javascript/codemirror/lib/codemirror.css',
            'view/javascript/summernote/summernote.css',
            'view/stylesheet/sbrf_online.css'
        ));

        $host = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : HTTP_CATALOG;
        $this->_setData(array(
            'action'          => $this->makeUrl('extension/payment/sbrf_online'),
            'cancel'          => $this->makeUrl('marketplace/extension', 'type=payment'),
            'text_copyright'  => sprintf($this->language->get('text_copyright'), $this->language->get('heading_title'), date('Y', time())),
            'text_page_success_default'  => sprintf($this->language->get('text_page_success_default'), $host . 'index.php?route=account/account', $host . 'index.php?route=account/order', $host . 'index.php?route=account/download', $host . 'index.php?route=information/contact'),
            'error_warning'   => isset($this->error['warning']) ? $this->error['warning'] : '',
            'error_bank'      => isset($this->error['error_bank']) ? $this->error['error_bank'] : '',
            'geo_zones'       => $this->model_localisation_geo_zone->getGeoZones(),
            'order_statuses'  => $this->model_localisation_order_status->getOrderStatuses(),
            'languages'       => $this->model_localisation_language->getLanguages()
        ));
        
        $languages = $this->model_localisation_language->getLanguages();
        foreach ($languages as $language) {
            $this->data['error_bank_' . $language['language_id']] = isset($this->error['bank_' . $language['language_id']]) ? $this->error['bank_' . $language['language_id']] : '';
        }

        $this->data['breadcrumbs'][] = array(
            'href' => $this->makeUrl('common/dashboard'),
            'text' => $this->language->get('text_home')
        );

        $this->data['breadcrumbs'][] = array(
            'href' => $this->makeUrl('marketplace/extension', 'type=payment'),
            'text' => $this->language->get('text_payment')
        );

        $this->data['breadcrumbs'][] = array(
            'href' => $this->makeUrl('extension/payment/sbrf_online'),
            'text' => $this->language->get('heading_title')
        );

        $this->_updateData(array(
            'payment_sbrf_online_bank',
            'payment_sbrf_online_page_success',
            'payment_sbrf_online_icon',
            'payment_sbrf_online_minimal_order',
            'payment_sbrf_online_maximal_order',
            'payment_sbrf_online_order_status_id',
            'payment_sbrf_online_geo_zone_id',
            'payment_sbrf_online_status',
            'payment_sbrf_online_sort_order',
            'payment_sbrf_online_langdata'
        ));

        $this->_setData(array(
            'header'       => $this->load->controller('common/header'),
            'column_left'  => $this->load->controller('common/column_left'),
            'footer'       => $this->load->controller('common/footer')
        ));
            
        $this->response->setOutput($this->load->view('extension/payment/sbrf_online', $this->data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/sbrf_online')) {
          $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->model_localisation_language->getLanguages() as $language) {
            if (!isset($this->request->post['payment_sbrf_online_bank'][$language['language_id']]) || !trim($this->request->post['payment_sbrf_online_bank'][$language['language_id']])) {
                $this->error['warning'] = $this->error['error_bank'] = $this->language->get('error_bank');
            }
        }

        return !$this->error;
    }

    protected function _addScript($files) {
        foreach ($files as $file) {
            if (is_file(DIR_APPLICATION . $file)) {
                $this->document->addScript($file);
            }
        }
    }
    protected function _addStyle($files) {
        foreach ($files as $file) {
            if (is_file(DIR_APPLICATION . $file)) {
                $this->document->addStyle($file);
            }
        }
    }

    protected function _setData($values) {
        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $this->data[$value] = $this->language->get($value);
            } else {
                $this->data[$key] = $value;
            }
        }
    }

    protected function _updateData($keys, $info = array()) {
        foreach ($keys as $key) {
            if (isset($this->request->post[$key])) {
                $this->data[$key] = $this->request->post[$key];
            } elseif ($this->config->get($key)) {
                $this->data[$key] = $this->config->get($key);
            } elseif (isset($info[$key])) {
                $this->data[$key] = $info[$key];
            } else {
                $this->data[$key] = null;
            }
        }
    }

    protected function _trimData($values) {
        foreach ($values as $value) {
                if (isset($this->request->post[$value])) {
                    $this->request->post[$value] = trim($this->request->post[$value]);
                }
        }
    }

    protected function _replaceData($search, $replace, $values) {
        foreach ($values as $value) {
                if (isset($this->request->post[$value])) {
                    $this->request->post[$value] = str_replace($search, $replace, $this->request->post[$value]);
                }
        }
    }

    protected function makeUrl($route, $url = '') {
        return str_replace('&amp;', '&', $this->url->link($route, $url . '&user_token=' . $this->session->data['user_token'], 'SSL'));
    }
}
?>