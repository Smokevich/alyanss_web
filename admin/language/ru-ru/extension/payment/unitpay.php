<?php
// Heading
$_['heading_title']      			= 'UnitPay';
$_['heading_license']               = 'Активация модуля оплаты "UnitPay"';

// Text
$_['text_extension']       			= 'Модули оплаты';
$_['text_success']       			= 'Настройки модуля оплаты "UnitPay" успешно обновлены!';
$_['text_success_license']      	= 'Модуль оплаты "UnitPay" - успешно активирован!';
$_['text_unitpay'] 		 			= '<a href="https://unitpay.ru/" target="_blank"><img src="view/image/payment/unitpay.png" alt="UnitPay" title="UnitPay" /></a>';
$_['text_edit']      	 			= 'Редактирование метода оплаты "UnitPay"';
$_['text_license_activate']         = 'Активация модуля оплаты "UnitPay"';
$_['text_secret_key']              	= 'Подробная инструкция получения данных для активации, дана в статье <a href="http://maxystore.com/license-product-info.html" target="_blanck" class="btn btn-default">Правила покупки лицензионных товаров</a>';
$_['text_license_info'] 			= 'После получения данных для активации, введите в соответствующие поля следующие данные:<br /><ol><li>№ заказа;</li><li>Код товара;</li><li>Ключ активации;</li></ol><br />В случае возникновения вопросов по получению данных для активации, пишите непосредственно на почту:<ol><li>support@maxystore.com</li><li>maxystore.com@gmail.com</li></ol>Помните, что модуль будет работать только на том домене, который вы указали при получении ключа активации!';
$_['text_clipboard']                = 'Скопировать секретный ключ';
$_['text_enabled']       			= 'Включено';
$_['text_disabled']       			= 'Отключено';
$_['text_yes']           			= 'Да';
$_['text_no']            			= 'Нет';
$_['text_russian']            		= 'Русский';
$_['text_english']            		= 'Английский';
$_['text_customer_group'] 			= '<i class="fa fa-users fa-fw"></i> Группа покупателей';
$_['text_not_group'] 				= 'Не зарегистрированные';
$_['text_comission_value'] 			= 'Комиссия для группы';

// Entry
$_['entry_title']					= 'Название модуля';
$_['entry_secret_key']     			= 'Секретный ключ';
$_['entry_activation_secret_key']   = 'Секретный ключ';
$_['entry_order_id']        		= '№ заказа';
$_['entry_product_license_code']    = 'Код товара';
$_['entry_license_key']        		= 'Ключ активации';
$_['entry_public_key']        		= 'Публичный ключ';
$_['entry_locale']    				= 'Язык платежной формы';
$_['entry_currency']    			= 'Валюта магазина';
$_['entry_total']					= 'Нижняя граница';
$_['entry_comission']				= 'Комиссия платежной системы';
$_['entry_callback_url']    		= 'Обработчик платежей';
$_['entry_fail_url']    			= 'Fail URL';
$_['entry_success_url']    			= 'Success URL';
$_['entry_order_status'] 			= 'Статус заказа после оплаты';
$_['entry_geo_zone']     			= 'Географическая зона';
$_['entry_status']       			= 'Статус';
$_['entry_sort_order']   			= 'Порядок сортировки';

// Tab
$_['tab_info']   					= 'Информация';
$_['tab_license']   				= 'Активация';

// Help
$_['help_currency']        			= 'Допустимые валюты платежной формы: Рубль (RUB), Гривна (UAN), Белорусский рубль (BYR), US Dollar (USD), Euro (EUR). Если в вашем магазине есть другие валюты, то выбирать их нельзя!';
$_['help_total']					= 'Минимальная сумма заказа. Ниже данной суммы, способ оплаты будет недоступен.';
$_['help_comission']				= 'Комиссия платежной системы в %<br />Если не желаете, чтобы покупатель оплачивал комиссию, тогда оставьте поле пустым.';

// Error
$_['error_permission']   			= 'У Вас нет прав для управления модулем оплаты "UnitPay"!';
$_['error_public_key']       		= 'Требуется указать публичный ключ!';
$_['error_secret_key']     			= 'Требуется указать секретный ключ!';
$_['error_license']    				= 'Ключ активации не принадлежит этому домену или неверный № заказа, код товара либо секретный ключ!';