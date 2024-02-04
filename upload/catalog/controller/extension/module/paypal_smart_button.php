<?php
/**
 * Class PayPal Smart Button
 *
 * @package Catalog\Controller\Extension\Module
 */
class ControllerExtensionModulePayPalSmartButton extends Controller {
	/**
	 * @var array<string, string>
	 */
	private array $error = [];

	/**
	 * Constructor
	 *
	 * @param object $registry
	 *
	 * @return string
	 */
	public function __construct(object $registry) {
		parent::__construct($registry);

		if (version_compare(PHP_VERSION, '8.3', '>=')) {
			ini_set('precision', 14);
			ini_set('serialize_precision', 14);
		}
	}

	/**
	 * @return string
	 */
	public function index(): string {
		if ($this->config->get('payment_paypal_status') && isset($this->request->get['route'])) {
			$status = false;

			// Setting
			$_config = new \Config();
			$_config->load('paypal');
			$paypal_setting = $_config->get('paypal_setting');

			$_config = new \Config();
			$_config->load('paypal_smart_button');
			$config_setting = $_config->get('paypal_smart_button_setting');

			$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('module_paypal_smart_button_setting'));
			$currency_code = $this->config->get('payment_paypal_currency_code');
			$currency_value = $this->config->get('payment_paypal_currency_value');
			$decimal_place = $paypal_setting['currency'][$currency_code]['decimal_place'];

			if ($setting['page']['product']['status'] && ($this->request->get['route'] == 'product/product') && isset($this->request->get['product_id'])) {
				$data['insert_tag'] = html_entity_decode($setting['page']['product']['insert_tag']);
				$data['insert_type'] = $setting['page']['product']['insert_type'];
				$data['button_align'] = $setting['page']['product']['button_align'];
				$data['button_size'] = $setting['page']['product']['button_size'];
				$data['button_color'] = $setting['page']['product']['button_color'];
				$data['button_shape'] = $setting['page']['product']['button_shape'];
				$data['button_label'] = $setting['page']['product']['button_label'];
				$data['button_tagline'] = $setting['page']['product']['button_tagline'];

				$data['message_status'] = $setting['page']['product']['message_status'];
				$data['message_align'] = $setting['page']['product']['message_align'];
				$data['message_size'] = $setting['page']['product']['message_size'];
				$data['message_layout'] = $setting['page']['product']['message_layout'];
				$data['message_text_color'] = $setting['page']['product']['message_text_color'];
				$data['message_text_size'] = $setting['page']['product']['message_text_size'];
				$data['message_flex_color'] = $setting['page']['product']['message_flex_color'];
				$data['message_flex_ratio'] = $setting['page']['product']['message_flex_ratio'];
				$data['message_placement'] = 'product';

				$product_id = (int)$this->request->get['product_id'];

				// Products
				$this->load->model('catalog/product');

				$product_info = $this->model_catalog_product->getProduct($product_id);

				if ($product_info) {
					if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
						if ((float)$product_info['special']) {
							$product_price = $this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));
						} else {
							$product_price = $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
						}

						$data['message_amount'] = number_format($product_price * $currency_value, $decimal_place, '.', '');
					}
				}

				$status = true;
			}

			if ($setting['page']['cart']['status'] && ($this->request->get['route'] == 'checkout/cart') && $this->cart->getTotal()) {
				$data['insert_tag'] = html_entity_decode($setting['page']['cart']['insert_tag']);
				$data['insert_type'] = $setting['page']['cart']['insert_type'];

				$data['button_align'] = $setting['page']['cart']['button_align'];
				$data['button_size'] = $setting['page']['cart']['button_size'];
				$data['button_color'] = $setting['page']['cart']['button_color'];
				$data['button_shape'] = $setting['page']['cart']['button_shape'];
				$data['button_label'] = $setting['page']['cart']['button_label'];
				$data['button_tagline'] = $setting['page']['cart']['button_tagline'];

				$data['message_status'] = $setting['page']['cart']['message_status'];
				$data['message_align'] = $setting['page']['cart']['message_align'];
				$data['message_size'] = $setting['page']['cart']['message_size'];
				$data['message_layout'] = $setting['page']['cart']['message_layout'];
				$data['message_text_color'] = $setting['page']['cart']['message_text_color'];
				$data['message_text_size'] = $setting['page']['cart']['message_text_size'];
				$data['message_flex_color'] = $setting['page']['cart']['message_flex_color'];
				$data['message_flex_ratio'] = $setting['page']['cart']['message_flex_ratio'];
				$data['message_placement'] = 'cart';

				$item_total = 0;

				foreach ($this->cart->getProducts() as $product) {
					$product_price = number_format($product['price'] * $currency_value, $decimal_place, '.', '');
					$item_total += $product_price * $product['quantity'];
				}

				$item_total = number_format($item_total, $decimal_place, '.', '');
				$sub_total = $this->cart->getSubTotal();
				$total = $this->cart->getTotal();
				$tax_total = number_format(($total - $sub_total) * $currency_value, $decimal_place, '.', '');
				$data['message_amount'] = number_format($item_total + $tax_total, $decimal_place, '.', '');

				$status = true;
			}

			if ($status) {
				// Countries
				$this->load->model('localisation/country');

				$country = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));

				$data['client_id'] = $this->config->get('payment_paypal_client_id');
				$data['merchant_id'] = $this->config->get('payment_paypal_merchant_id');
				$data['environment'] = $this->config->get('payment_paypal_environment');
				$data['partner_id'] = $paypal_setting['partner'][$data['environment']]['partner_id'];
				$data['transaction_method'] = $this->config->get('payment_paypal_transaction_method');
				$data['locale'] = preg_replace('/-(.+?)+/', '', $this->config->get('config_language')) . '_' . $country['iso_code_2'];
				$data['currency_code'] = $this->config->get('payment_paypal_currency_code');
				$data['button_width'] = $setting['button_width'][$data['button_size']];
				$data['message_width'] = $setting['message_width'][$data['message_size']];

				return $this->load->view('extension/module/paypal_smart_button', $data);
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

	/**
	 * Create Order
	 *
	 * @return void
	 */
	public function createOrder(): void {
		$this->load->language('extension/module/paypal_smart_button');

		$errors = [];

		// PayPal Smart Button
		$this->load->model('extension/module/paypal_smart_button');

		$data['order_id'] = '';

		if (isset($this->request->post['product_id'])) {
			$product_id = (int)$this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		// Products
		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			if (isset($this->request->post['quantity'])) {
				$quantity = (int)$this->request->post['quantity'];
			} else {
				$quantity = 1;
			}

			if (isset($this->request->post['option'])) {
				$option = array_filter($this->request->post['option']);
			} else {
				$option = [];
			}

			$product_options = $this->model_catalog_product->getOptions($this->request->post['product_id']);

			foreach ($product_options as $product_option) {
				if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
					$errors[] = sprintf($this->language->get('error_required'), $product_option['name']);
				}
			}

			if (isset($this->request->post['subscription_plan_id'])) {
				$subscription_plan_id = (int)$this->request->post['subscription_plan_id'];
			} else {
				$subscription_plan_id = 0;
			}

			$subscription_plans = $this->model_catalog_product->getSubscriptions($product_info['product_id']);

			if ($subscription_plans) {
				$subscription_plan_ids = [];

				foreach ($subscription_plans as $subscription_plan) {
					$subscription_plan_ids[] = $subscription_plan['subscription_plan_id'];
				}

				if (!in_array($subscription_plan_id, $subscription_plan_ids)) {
					$errors[] = $this->language->get('error_subscription_required');
				}
			}

			if (!$errors) {
				if (!$this->model_extension_module_paypal_smart_button->hasProductInCart($this->request->post['product_id'], $option, $subscription_plan_id)) {
					$this->cart->add($this->request->post['product_id'], $quantity, $option, $subscription_plan_id);
				}

				// Unset all shipping and payment methods
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
			}
		}

		if (!$errors) {
			// Setting
			$_config = new \Config();
			$_config->load('paypal');
			$config_setting = $_config->get('paypal_setting');
			$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_paypal_setting'));

			$client_id = $this->config->get('payment_paypal_client_id');
			$secret = $this->config->get('payment_paypal_secret');
			$environment = $this->config->get('payment_paypal_environment');
			$partner_id = $setting['partner'][$environment]['partner_id'];
			$transaction_method = $this->config->get('payment_paypal_transaction_method');
			$currency_code = $this->config->get('payment_paypal_currency_code');
			$currency_value = $this->config->get('payment_paypal_currency_value');
			$decimal_place = $setting['currency'][$currency_code]['decimal_place'];

			require_once DIR_SYSTEM . 'library/paypal/paypal.php';

			$paypal_info = [
				'partner_id'  => $partner_id,
				'client_id'   => $client_id,
				'secret'      => $secret,
				'environment' => $environment
			];

			$paypal = new \PayPal($paypal_info);

			$token_info = [
				'grant_type' => 'client_credentials'
			];

			$paypal->setAccessToken($token_info);

			$item_total = 0;
			$item_info = [];

			foreach ($this->cart->getProducts() as $product) {
				$product_price = number_format($product['price'] * $currency_value, $decimal_place, '.', '');

				$item_info[] = [
					'name'        => $product['name'],
					'sku'         => $product['model'],
					'url'         => $this->url->link('product/product', 'product_id=' . $product['product_id'], true),
					'quantity'    => $product['quantity'],
					'unit_amount' => [
						'currency_code' => $currency_code,
						'value'         => $product_price
					]
				];

				$item_total += $product_price * $product['quantity'];
			}

			$item_total = number_format($item_total, $decimal_place, '.', '');
			$sub_total = $this->cart->getSubTotal();
			$total = $this->cart->getTotal();
			$tax_total = number_format(($total - $sub_total) * $currency_value, $decimal_place, '.', '');
			$order_total = number_format($item_total + $tax_total, $decimal_place, '.', '');

			$amount_info = [
				'currency_code' => $currency_code,
				'value'         => $order_total,
				'breakdown'     => [
					'item_total' => [
						'currency_code' => $currency_code,
						'value'         => $item_total
					],
					'tax_total' => [
						'currency_code' => $currency_code,
						'value'         => $tax_total
					]
				]
			];

			if ($this->cart->hasShipping()) {
				$shipping_preference = 'GET_FROM_FILE';
			} else {
				$shipping_preference = 'NO_SHIPPING';
			}

			$order_info = [
				'intent'         => strtoupper($transaction_method),
				'purchase_units' => [
					[
						'reference_id' => 'default',
						'items'        => $item_info,
						'amount'       => $amount_info
					]
				],
				'application_context' => [
					'shipping_preference' => $shipping_preference
				]
			];

			$result = $paypal->createOrder($order_info);

			if (isset($result['id'])) {
				$data['order_id'] = $result['id'];
			}

			if ($paypal->hasErrors()) {
				$error_messages = [];

				$errors = $paypal->getErrors();

				foreach ($errors as $error) {
					if (isset($error['name']) && ($error['name'] == 'CURLE_OPERATION_TIMEOUTED')) {
						$error['message'] = $this->language->get('error_timeout');
					}

					if (isset($error['details'][0]['description'])) {
						$error_messages[] = $error['details'][0]['description'];
					} else {
						$error_messages[] = $error['message'];
					}

					$this->model_extension_module_paypal_smart_button->log($error, $error['message']);
				}

				$this->error['warning'] = implode(' ', $error_messages);
			}
		} else {
			$this->error['warning'] = implode(' ', $errors);
		}

		$data['error'] = $this->error;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}

	/**
	 * Approve Order
	 *
	 * @return void
	 */
	public function approveOrder(): void {
		$this->load->language('extension/module/paypal_smart_button');

		// PayPal Smart Button
		$this->load->model('extension/module/paypal_smart_button');

		// Countries
		$this->load->model('localisation/country');

		if (isset($this->request->post['order_id'])) {
			$this->session->data['paypal_order_id'] = (int)$this->request->post['order_id'];
		} else {
			$data['url'] = $this->url->link('checkout/cart', '', true);

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($data));
		}

		// check checkout can continue due to stock checks or vouchers
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$data['url'] = $this->url->link('checkout/cart', '', true);

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($data));
		}

		// if user not logged in check that the guest checkout is allowed
		if (!$this->customer->isLogged() && (!$this->config->get('config_checkout_guest') || $this->config->get('config_customer_price') || $this->cart->hasDownload() || $this->cart->hasSubscription())) {
			$data['url'] = $this->url->link('checkout/cart', '', true);

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($data));
		}

		// Setting
		$_config = new \Config();
		$_config->load('paypal');
		$config_setting = $_config->get('paypal_setting');
		$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_paypal_setting'));

		$client_id = $this->config->get('payment_paypal_client_id');
		$secret = $this->config->get('payment_paypal_secret');
		$environment = $this->config->get('payment_paypal_environment');
		$partner_id = $setting['partner'][$environment]['partner_id'];
		$transaction_method = $this->config->get('payment_paypal_transaction_method');

		$order_id = (int)$this->session->data['paypal_order_id'];

		require_once DIR_SYSTEM . 'library/paypal/paypal.php';

		$paypal_info = [
			'partner_id'  => $partner_id,
			'client_id'   => $client_id,
			'secret'      => $secret,
			'environment' => $environment
		];

		$paypal = new \PayPal($paypal_info);

		$token_info = [
			'grant_type' => 'client_credentials'
		];

		$paypal->setAccessToken($token_info);

		$order_info = $paypal->getOrder($order_id);

		if ($paypal->hasErrors()) {
			$error_messages = [];

			$errors = $paypal->getErrors();

			foreach ($errors as $error) {
				if (isset($error['name']) && ($error['name'] == 'CURLE_OPERATION_TIMEOUTED')) {
					$error['message'] = $this->language->get('error_timeout');
				}

				if (isset($error['details'][0]['description'])) {
					$error_messages[] = $error['details'][0]['description'];
				} else {
					$error_messages[] = $error['message'];
				}

				$this->model_extension_module_paypal_smart_button->log($error, $error['message']);
			}

			$this->error['warning'] = implode(' ', $error_messages);
		}

		if ($order_info && !$this->error) {
			// Addresses
			$this->load->model('account/address');

			// Customers
			$this->load->model('account/customer');

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);

			if ($this->customer->isLogged()) {
				$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

				$this->session->data['guest']['customer_id'] = $this->customer->getId();
				$this->session->data['guest']['customer_group_id'] = $customer_info['customer_group_id'];
				$this->session->data['guest']['firstname'] = $customer_info['firstname'];
				$this->session->data['guest']['lastname'] = $customer_info['lastname'];
				$this->session->data['guest']['email'] = $customer_info['email'];
				$this->session->data['guest']['telephone'] = $customer_info['telephone'];
				$this->session->data['guest']['custom_field'] = $customer_info['custom_field'];
			} else {
				$this->session->data['guest']['customer_id'] = 0;
				$this->session->data['guest']['customer_group_id'] = $this->config->get('config_customer_group_id');
				$this->session->data['guest']['firstname'] = $order_info['payer']['name']['given_name'] ?? '';
				$this->session->data['guest']['lastname'] = $order_info['payer']['name']['surname'] ?? '';
				$this->session->data['guest']['email'] = $order_info['payer']['email_address'] ?? '';
				$this->session->data['guest']['telephone'] = '';
				$this->session->data['guest']['custom_field'] = [];
			}

			if ($this->customer->isLogged() && $this->customer->getAddressId()) {
				$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			} else {
				$this->session->data['payment_address']['country_id'] = 0;
				$this->session->data['payment_address']['zone_id'] = 0;
				$this->session->data['payment_address']['firstname'] = $order_info['payer']['name']['given_name'] ?? '';
				$this->session->data['payment_address']['lastname'] = $order_info['payer']['name']['surname'] ?? '';
				$this->session->data['payment_address']['company'] = '';
				$this->session->data['payment_address']['address_1'] = '';
				$this->session->data['payment_address']['address_2'] = '';
				$this->session->data['payment_address']['city'] = '';
				$this->session->data['payment_address']['postcode'] = '';
				$this->session->data['payment_address']['country'] = '';
				$this->session->data['payment_address']['address_format'] = '';
				$this->session->data['payment_address']['zone'] = '';
				$this->session->data['payment_address']['custom_field'] = [];

				if (isset($order_info['payer']['address']['country_code'])) {
					$country_info = $this->model_localisation_country->getCountryByIsoCode2($order_info['payer']['address']['country_code']);

					if ($country_info) {
						$this->session->data['payment_address']['country'] = $country_info['name'];
						$this->session->data['payment_address']['country_id'] = $country_info['country_id'];
					}
				}
			}

			if ($this->cart->hasShipping()) {
				if ($this->customer->isLogged() && $this->customer->getAddressId()) {
					$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				} else {
					if (isset($order_info['purchase_units'][0]['shipping']['name']['full_name'])) {
						$shipping_name = explode(' ', $order_info['purchase_units'][0]['shipping']['name']['full_name']);
						$shipping_firstname = $shipping_name[0];
						unset($shipping_name[0]);
						$shipping_lastname = implode(' ', $shipping_name);
					}

					$this->session->data['shipping_address']['country_id'] = 0;
					$this->session->data['shipping_address']['zone_id'] = 0;
					$this->session->data['shipping_address']['firstname'] = $shipping_firstname ?? '';
					$this->session->data['shipping_address']['lastname'] = $shipping_lastname ?? '';
					$this->session->data['shipping_address']['address_1'] = $order_info['purchase_units'][0]['shipping']['address']['address_line_1'] ?? '';
					$this->session->data['shipping_address']['address_2'] = $order_info['purchase_units'][0]['shipping']['address']['address_line_2'] ?? '';
					$this->session->data['shipping_address']['city'] = $order_info['purchase_units'][0]['shipping']['address']['admin_area_2'] ?? '';
					$this->session->data['shipping_address']['postcode'] = $order_info['purchase_units'][0]['shipping']['address']['postal_code'] ?? '';
					$this->session->data['shipping_address']['company'] = '';
					$this->session->data['shipping_address']['country'] = '';
					$this->session->data['shipping_address']['address_format'] = '';
					$this->session->data['shipping_address']['zone'] = '';
					$this->session->data['shipping_address']['custom_field'] = [];

					if (isset($order_info['purchase_units'][0]['shipping']['address']['country_code'])) {
						$country_info = $this->model_localisation_country->getCountryByIsoCode2($order_info['purchase_units'][0]['shipping']['address']['country_code']);

						if ($country_info) {
							$this->session->data['shipping_address']['country_id'] = $country_info['country_id'];
							$this->session->data['shipping_address']['country'] = $country_info['name'];
							$this->session->data['shipping_address']['address_format'] = $country_info['address_format'];

							if (isset($order_info['purchase_units'][0]['shipping']['address']['admin_area_1'])) {
								$zone_info = $this->model_extension_module_paypal_smart_button->getZoneByCode($country_info['country_id'], $order_info['purchase_units'][0]['shipping']['address']['admin_area_1']);

								if ($zone_info) {
									$this->session->data['shipping_address']['zone_id'] = $zone_info['zone_id'];
									$this->session->data['shipping_address']['zone'] = $zone_info['name'];
								}
							}
						}
					}
				}
			}

			$data['url'] = $this->url->link('extension/module/paypal_smart_button/confirmOrder', '', true);
		}

		$data['error'] = $this->error;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}

	/**
	 * Confirm Order
	 *
	 * @return void
	 */
	public function confirmOrder(): void {
		$this->load->language('checkout/cart');
		$this->load->language('extension/module/paypal_smart_button');

		// Images
		$this->load->model('tool/image');

		if (!isset($this->session->data['paypal_order_id'])) {
			$this->response->redirect($this->url->link('checkout/cart', '', true));
		}

		// Coupon
		if (isset($this->request->post['coupon']) && $this->validateCoupon()) {
			$this->session->data['coupon'] = $this->request->post['coupon'];

			$this->session->data['success'] = $this->language->get('text_coupon');

			$this->response->redirect($this->url->link('extension/module/paypal_smart_button/confirmOrder', '', true));
		}

		// Voucher
		if (isset($this->request->post['voucher']) && $this->validateVoucher()) {
			$this->session->data['voucher'] = $this->request->post['voucher'];

			$this->session->data['success'] = $this->language->get('text_voucher');

			$this->response->redirect($this->url->link('extension/module/paypal_smart_button/confirmOrder', '', true));
		}

		// Reward
		if (isset($this->request->post['reward']) && $this->validateReward()) {
			$this->session->data['reward'] = abs($this->request->post['reward']);

			$this->session->data['success'] = $this->language->get('text_reward');

			$this->response->redirect($this->url->link('extension/module/paypal_smart_button/confirmOrder', '', true));
		}

		$this->document->setTitle($this->language->get('text_title'));

		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');

		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

		$data['heading_title'] = $this->language->get('text_title');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', '', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart', '', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_title'),
			'href' => $this->url->link('extension/module/paypal_smart_button/confirmOrder', '', true)
		];

		$points_total = 0;

		foreach ($this->cart->getProducts() as $product) {
			if ($product['points']) {
				$points_total += $product['points'];
			}
		}

		if (isset($this->request->post['next'])) {
			$data['next'] = $this->request->post['next'];
		} else {
			$data['next'] = '';
		}

		// Upload
		$this->load->model('tool/upload');

		$products = $this->cart->getProducts();

		if (!$products) {
			$this->response->redirect($this->url->link('checkout/cart', '', true));
		}

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
			}

			if ($product['image']) {
				$image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
			} else {
				$image = '';
			}

			$option_data = [];

			foreach ($product['option'] as $option) {
				if ($option['type'] != 'file') {
					$value = $option['value'];
				} else {
					$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

					if ($upload_info) {
						$value = $upload_info['name'];
					} else {
						$value = '';
					}
				}

				$option_data[] = [
					'name'  => $option['name'],
					'value' => (oc_strlen($value) > 20 ? oc_substr($value, 0, 20) . '..' : $value)
				];
			}

			// Display prices
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));

				$price = $this->currency->format($unit_price, $this->session->data['currency']);
				$total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
			} else {
				$price = false;
				$total = false;
			}

			// Subscriptions
			$description = '';

			if ($product['subscription']) {
				$trial_price = $this->currency->format($this->tax->calculate($product['subscription']['trial_price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				$trial_cycle = $product['subscription']['trial_cycle'];
				$trial_frequency = $this->language->get('text_' . $product['subscription']['trial_frequency']);
				$trial_duration = $product['subscription']['trial_duration'];

				if ($product['subscription']['trial_status']) {
					$description .= sprintf($this->language->get('text_subscription_trial'), $trial_price, $trial_cycle, $trial_frequency, $trial_duration);
				}

				$price = $this->currency->format($this->tax->calculate($product['subscription']['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				$cycle = $product['subscription']['cycle'];
				$frequency = $this->language->get('text_' . $product['subscription']['frequency']);
				$duration = $product['subscription']['duration'];

				if ($duration) {
					$description .= sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
				} else {
					$description .= sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
				}
			}

			$data['products'][] = [
				'cart_id'      => $product['cart_id'],
				'thumb'        => $image,
				'name'         => $product['name'],
				'model'        => $product['model'],
				'option'       => $option_data,
				'subscription' => $description,
				'quantity'     => $product['quantity'],
				'stock'        => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
				'reward'       => $product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : '',
				'price'        => $price,
				'total'        => $total,
				'href'         => $this->url->link('product/product', 'product_id=' . $product['product_id'], true)
			];
		}

		// Gift Voucher
		$data['vouchers'] = [];

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $key => $voucher) {
				$data['vouchers'][] = [
					'key'         => $key,
					'description' => $voucher['description'],
					'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency']),
					'remove'      => $this->url->link('checkout/cart', 'remove=' . $key, true)
				];
			}
		}

		// Extensions
		$this->load->model('setting/extension');

		if ($this->cart->hasShipping()) {
			$data['has_shipping'] = true;

			$data['shipping_address'] = $this->session->data['shipping_address'] ?? [];

			if ($data['shipping_address']) {
				// Shipping Methods
				$quote_data = [];

				$results = $this->model_setting_extension->getExtensionsByType('shipping');

				foreach ($results as $result) {
					if ($this->config->get('shipping_' . $result['code'] . '_status')) {
						$this->load->model('extension/shipping/' . $result['code']);

						$callable = [$this->{'model_extension_shipping_' . $result['code']}, 'getQuote'];

						if (is_callable($callable)) {
							$quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($data['shipping_address']);

							if ($quote) {
								$quote_data[$result['code']] = [
									'title'      => $quote['title'],
									'quote'      => $quote['quote'],
									'sort_order' => $quote['sort_order'],
									'error'      => $quote['error']
								];
							}
						}
					}
				}

				if ($quote_data) {
					$sort_order = [];

					foreach ($quote_data as $key => $value) {
						$sort_order[$key] = $value['sort_order'];
					}

					array_multisort($sort_order, SORT_ASC, $quote_data);

					$this->session->data['shipping_methods'] = $quote_data;
					$data['shipping_methods'] = $quote_data;

					if (!isset($this->session->data['shipping_method'])) {
						// Default the shipping to the very first option.
						$key1 = key($quote_data);
						$key2 = key($quote_data[$key1]['quote']);

						$this->session->data['shipping_method'] = $quote_data[$key1]['quote'][$key2];
					}

					$data['code'] = $this->session->data['shipping_method']['code'];
					$data['action_shipping'] = $this->url->link('extension/module/paypal_smart_button/confirmShipping', '', true);
				} else {
					unset($this->session->data['shipping_methods']);
					unset($this->session->data['shipping_method']);

					$data['error_no_shipping'] = $this->language->get('error_no_shipping');
				}
			} else {
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['shipping_method']);

				$data['error_no_shipping'] = $this->language->get('error_no_shipping');
			}
		} else {
			$data['has_shipping'] = false;
		}

		$data['guest'] = $this->session->data['guest'] ?? [];
		$data['payment_address'] = $this->session->data['payment_address'] ?? [];

		/**
		 * Payment methods
		 */
		$method_data = [];

		$results = $this->model_setting_extension->getExtensionsByType('payment');

		if (isset($total)) {
			foreach ($results as $result) {
				if ($this->config->get('payment_' . $result['code'] . '_status')) {
					$this->load->model('extension/payment/' . $result['code']);

					$callable = [$this->{'model_extension_payment_' . $result['code']}, 'getMethod'];

					if (is_callable($callable)) {
						$method = $this->{'model_extension_payment_' . $result['code']}->getMethod($data['payment_address'], $total);

						if ($method) {
							$method_data[$result['code']] = $method;
						}
					}
				}
			}
		}

		$sort_order = [];

		foreach ($method_data as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $method_data);

		$this->session->data['payment_methods'] = $method_data;

		$data['payment_methods'] = $method_data;

		if (!isset($method_data['paypal'])) {
			$this->session->data['error_warning'] = $this->language->get('error_unavailable');

			$this->response->redirect($this->url->link('checkout/checkout', '', true));
		}

		$this->session->data['payment_methods'] = $method_data;
		$this->session->data['payment_method'] = $method_data['paypal'];

		// Custom Fields
		$this->load->model('account/custom_field');

		$data['custom_fields'] = $this->model_account_custom_field->getCustomFields();

		// Totals
		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;

		// Because __call can not keep var references, so we put them into an array.
		$total_data = [
			'totals' => &$totals,
			'taxes'  => &$taxes,
			'total'  => &$total
		];

		// Display prices
		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$sort_order = [];

			$results = $this->model_setting_extension->getExtensionsByType('total');

			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
			}

			array_multisort($sort_order, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get('total_' . $result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);

					// We have to put the totals in an array so that they pass by reference.
					$callable = [$this->{'model_extension_total_' . $result['code']}, 'getTotal'];

					if (is_callable($callable)) {
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}
			}

			$sort_order = [];

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);
		}

		$data['totals'] = [];

		foreach ($totals as $total) {
			$data['totals'][] = [
				'title' => $total['title'],
				'text'  => $this->currency->format($total['value'], $this->session->data['currency']),
			];
		}

		$data['action_confirm'] = $this->url->link('extension/module/paypal_smart_button/completeOrder', '', true);

		if (isset($this->session->data['error_warning'])) {
			$data['error_warning'] = $this->session->data['error_warning'];

			unset($this->session->data['error_warning']);
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->session->data['attention'])) {
			$data['attention'] = $this->session->data['attention'];

			unset($this->session->data['attention']);
		} else {
			$data['attention'] = '';
		}

		$data['coupon'] = $this->load->controller('extension/total/coupon');
		$data['voucher'] = $this->load->controller('extension/total/voucher');
		$data['reward'] = $this->load->controller('extension/total/reward');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/module/paypal_smart_button/confirm', $data));
	}

	/**
	 * Complete Order
	 *
	 * @return void
	 */
	public function completeOrder(): void {
		$this->load->language('extension/module/paypal_smart_button');

		// PayPal Smart Button
		$this->load->model('extension/module/paypal_smart_button');

		// Validate if payment address has been set.
		if (empty($this->session->data['payment_address'])) {
			$this->response->redirect($this->url->link('checkout/checkout', '', true));
		}

		// Validate if payment method has been set.
		if (!isset($this->session->data['payment_method'])) {
			$this->response->redirect($this->url->link('checkout/checkout', '', true));
		}

		if ($this->cart->hasShipping()) {
			// Validate if shipping address has been set.
			if (empty($this->session->data['shipping_address'])) {
				$this->response->redirect($this->url->link('checkout/checkout', '', true));
			}

			// Validate if shipping method has been set.
			if (!isset($this->session->data['shipping_method'])) {
				$this->response->redirect($this->url->link('checkout/checkout', '', true));
			}
		} else {
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
		}

		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$this->response->redirect($this->url->link('checkout/cart', '', true));
		}

		if (isset($this->session->data['paypal_order_id'])) {
			$totals = [];
			$taxes = $this->cart->getTaxes();
			$total = 0;

			// Because __call can not keep var references, so we put them into an array.
			$total_data = [
				'totals' => &$totals,
				'taxes'  => &$taxes,
				'total'  => &$total
			];

			// Extensions
			$this->load->model('setting/extension');

			$sort_order = [];

			$results = $this->model_setting_extension->getExtensionsByType('total');

			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
			}

			array_multisort($sort_order, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get('total_' . $result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);

					// We have to put the totals in an array so that they pass by reference.
					$callable = [$this->{'model_extension_total_' . $result['code']}, 'getTotal'];

					if (is_callable($callable)) {
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}
			}

			$sort_order = [];

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);

			$order_data = [];
			$order_data['totals'] = $totals;
			$order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
			$order_data['store_id'] = $this->config->get('config_store_id');
			$order_data['store_name'] = $this->config->get('config_name');

			if ($order_data['store_id']) {
				$order_data['store_url'] = $this->config->get('config_url');
			} else {
				if ($this->request->server['HTTPS']) {
					$order_data['store_url'] = HTTPS_SERVER;
				} else {
					$order_data['store_url'] = HTTP_SERVER;
				}
			}

			// Guest Details
			$order_data['customer_id'] = $this->session->data['guest']['customer_id'];
			$order_data['customer_group_id'] = (int)$this->session->data['guest']['customer_group_id'];
			$order_data['firstname'] = $this->session->data['guest']['firstname'];
			$order_data['lastname'] = $this->session->data['guest']['lastname'];
			$order_data['email'] = $this->session->data['guest']['email'];
			$order_data['telephone'] = $this->session->data['guest']['telephone'];
			$order_data['custom_field'] = $this->session->data['guest']['custom_field'];

			// Payment Details
			$order_data['payment_firstname'] = $this->session->data['payment_address']['firstname'];
			$order_data['payment_lastname'] = $this->session->data['payment_address']['lastname'];
			$order_data['payment_company'] = $this->session->data['payment_address']['company'];
			$order_data['payment_address_1'] = $this->session->data['payment_address']['address_1'];
			$order_data['payment_address_2'] = $this->session->data['payment_address']['address_2'];
			$order_data['payment_city'] = $this->session->data['payment_address']['city'];
			$order_data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
			$order_data['payment_zone'] = $this->session->data['payment_address']['zone'];
			$order_data['payment_zone_id'] = (int)$this->session->data['payment_address']['zone_id'];
			$order_data['payment_country'] = $this->session->data['payment_address']['country'];
			$order_data['payment_country_id'] = (int)$this->session->data['payment_address']['country_id'];
			$order_data['payment_address_format'] = $this->session->data['payment_address']['address_format'];
			$order_data['payment_custom_field'] = $this->session->data['payment_address']['custom_field'] ?? [];

			if (isset($this->session->data['payment_method']['name'])) {
				$order_data['payment_method'] = $this->session->data['payment_method']['name'];
			} else {
				$order_data['payment_method'] = '';
			}

			if (isset($this->session->data['payment_method']['code'])) {
				$order_data['payment_code'] = $this->session->data['payment_method']['code'];
			} else {
				$order_data['payment_code'] = '';
			}

			if ($this->cart->hasShipping()) {
				$order_data['shipping_firstname'] = $this->session->data['shipping_address']['firstname'];
				$order_data['shipping_lastname'] = $this->session->data['shipping_address']['lastname'];
				$order_data['shipping_company'] = $this->session->data['shipping_address']['company'];
				$order_data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
				$order_data['shipping_address_2'] = $this->session->data['shipping_address']['address_2'];
				$order_data['shipping_city'] = $this->session->data['shipping_address']['city'];
				$order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
				$order_data['shipping_zone'] = $this->session->data['shipping_address']['zone'];
				$order_data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
				$order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
				$order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
				$order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
				$order_data['shipping_custom_field'] = $this->session->data['shipping_address']['custom_field'] ?? [];

				if (isset($this->session->data['shipping_method']['title'])) {
					$order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
				} else {
					$order_data['shipping_method'] = '';
				}

				if (isset($this->session->data['shipping_method']['code'])) {
					$order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
				} else {
					$order_data['shipping_code'] = '';
				}
			} else {
				$order_data['shipping_zone_id'] = 0;
				$order_data['shipping_country_id'] = 0;
				$order_data['shipping_firstname'] = '';
				$order_data['shipping_lastname'] = '';
				$order_data['shipping_company'] = '';
				$order_data['shipping_address_1'] = '';
				$order_data['shipping_address_2'] = '';
				$order_data['shipping_city'] = '';
				$order_data['shipping_postcode'] = '';
				$order_data['shipping_zone'] = '';
				$order_data['shipping_country'] = '';
				$order_data['shipping_address_format'] = '';
				$order_data['shipping_method'] = '';
				$order_data['shipping_code'] = '';
				$order_data['shipping_custom_field'] = [];
			}

			$order_data['products'] = [];

			foreach ($this->cart->getProducts() as $product) {
				$option_data = [];

				foreach ($product['option'] as $option) {
					$option_data[] = [
						'product_option_id'       => $option['product_option_id'],
						'product_option_value_id' => $option['product_option_value_id'],
						'option_id'               => $option['option_id'],
						'option_value_id'         => $option['option_value_id'],
						'name'                    => $option['name'],
						'value'                   => $option['value'],
						'type'                    => $option['type']
					];
				}

				$order_data['products'][] = [
					'product_id' => $product['product_id'],
					'name'       => $product['name'],
					'model'      => $product['model'],
					'option'     => $option_data,
					'download'   => $product['download'],
					'quantity'   => $product['quantity'],
					'subtract'   => $product['subtract'],
					'price'      => $product['price'],
					'total'      => $product['total'],
					'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
					'reward'     => $product['reward']
				];
			}

			// Gift Voucher
			$order_data['vouchers'] = [];

			if (!empty($this->session->data['vouchers'])) {
				foreach ($this->session->data['vouchers'] as $voucher) {
					$order_data['vouchers'][] = [
						'description'      => $voucher['description'],
						'code'             => oc_token(10),
						'to_name'          => $voucher['to_name'],
						'to_email'         => $voucher['to_email'],
						'from_name'        => $voucher['from_name'],
						'from_email'       => $voucher['from_email'],
						'voucher_theme_id' => $voucher['voucher_theme_id'],
						'message'          => $voucher['message'],
						'amount'           => $voucher['amount']
					];
				}
			}

			$order_data['comment'] = $this->session->data['comment'] ?? '';
			$order_data['total'] = $total_data['total'];

			if (isset($this->request->cookie['tracking'])) {
				$order_data['tracking'] = $this->request->cookie['tracking'];

				$sub_total = $this->cart->getSubTotal();

				// Customer Affiliate
				$this->load->model('account/customer');

				$affiliate_info = $this->model_account_customer->getAffiliateByTracking($this->request->cookie['tracking']);

				if ($affiliate_info) {
					$order_data['affiliate_id'] = $affiliate_info['customer_id'];
					$order_data['commission'] = ($sub_total / 100) * $affiliate_info['commission'];
				} else {
					$order_data['affiliate_id'] = 0;
					$order_data['commission'] = 0;
				}

				// Marketing
				$this->load->model('checkout/marketing');

				$marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);

				if ($marketing_info) {
					$order_data['marketing_id'] = $marketing_info['marketing_id'];
				} else {
					$order_data['marketing_id'] = 0;
				}
			} else {
				$order_data['affiliate_id'] = 0;
				$order_data['commission'] = 0;
				$order_data['marketing_id'] = 0;
				$order_data['tracking'] = '';
			}

			$order_data['language_id'] = $this->config->get('config_language_id');
			$order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
			$order_data['currency_code'] = $this->session->data['currency'];
			$order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
			$order_data['ip'] = $this->request->server['REMOTE_ADDR'];

			if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
				$order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
			} elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
				$order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
			} else {
				$order_data['forwarded_ip'] = '';
			}

			if (isset($this->request->server['HTTP_USER_AGENT'])) {
				$order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
			} else {
				$order_data['user_agent'] = '';
			}

			if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
				$order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
			} else {
				$order_data['accept_language'] = '';
			}

			// Orders
			$this->load->model('checkout/order');

			$this->session->data['order_id'] = $this->model_checkout_order->addOrder($order_data);

			// Setting
			$_config = new \Config();
			$_config->load('paypal');
			$config_setting = $_config->get('paypal_setting');
			$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_paypal_setting'));

			$client_id = $this->config->get('payment_paypal_client_id');
			$secret = $this->config->get('payment_paypal_secret');
			$environment = $this->config->get('payment_paypal_environment');
			$partner_id = $setting['partner'][$environment]['partner_id'];

			$transaction_method = $this->config->get('payment_paypal_transaction_method');
			$currency_code = $this->config->get('payment_paypal_currency_code');
			$currency_value = $this->config->get('payment_paypal_currency_value');
			$decimal_place = $setting['currency'][$currency_code]['decimal_place'];

			require_once DIR_SYSTEM . 'library/paypal/paypal.php';

			$paypal_info = [
				'partner_id'  => $partner_id,
				'client_id'   => $client_id,
				'secret'      => $secret,
				'environment' => $environment
			];

			$paypal = new \PayPal($paypal_info);

			$token_info = [
				'grant_type' => 'client_credentials'
			];

			$paypal->setAccessToken($token_info);

			$order_id = $this->session->data['paypal_order_id'];

			$order_info = [];

			$order_info[] = [
				'op'    => 'add',
				'path'  => '/purchase_units/@reference_id==\'default\'/description',
				'value' => 'Your order ' . $this->session->data['order_id']
			];

			$order_info[] = [
				'op'    => 'add',
				'path'  => '/purchase_units/@reference_id==\'default\'/invoice_id',
				'value' => $this->session->data['order_id']
			];

			$shipping_info = [];

			if ($this->cart->hasShipping()) {
				$shipping_info['name']['full_name'] = $this->session->data['shipping_address']['firstname'] ?? '';
				$shipping_info['name']['full_name'] .= isset($this->session->data['shipping_address']['lastname']) ? (' ' . $this->session->data['shipping_address']['lastname']) : '';
				$shipping_info['address']['address_line_1'] = $this->session->data['shipping_address']['address_1'] ?? '';
				$shipping_info['address']['address_line_2'] = $this->session->data['shipping_address']['address_2'] ?? '';
				$shipping_info['address']['admin_area_1'] = $this->session->data['shipping_address']['zone'] ?? '';
				$shipping_info['address']['admin_area_2'] = $this->session->data['shipping_address']['city'] ?? '';
				$shipping_info['address']['postal_code'] = $this->session->data['shipping_address']['postcode'] ?? '';

				if (isset($this->session->data['shipping_address']['country_id'])) {
					$country_id = (int)$this->session->data['shipping_address']['country_id'];
				} else {
					$country_id = 0;
				}

				// Countries
				$this->load->model('localisation/country');

				$country_info = $this->model_localisation_country->getCountry($country_id);

				if ($country_info) {
					$shipping_info['address']['country_code'] = $country_info['iso_code_2'];
				}

				$order_info[] = [
					'op'    => 'replace',
					'path'  => '/purchase_units/@reference_id==\'default\'/shipping/name',
					'value' => $shipping_info['name']
				];

				$order_info[] = [
					'op'    => 'replace',
					'path'  => '/purchase_units/@reference_id==\'default\'/shipping/address',
					'value' => $shipping_info['address']
				];
			}

			$item_total = 0;

			foreach ($this->cart->getProducts() as $product) {
				$product_price = number_format($product['price'] * $currency_value, $decimal_place, '.', '');

				$item_total += $product_price * $product['quantity'];
			}

			$item_total = number_format($item_total, 2, '.', '');
			$sub_total = $this->cart->getSubTotal();
			$total = $this->cart->getTotal();
			$tax_total = number_format(($total - $sub_total) * $currency_value, $decimal_place, '.', '');

			$discount_total = 0;
			$handling_total = 0;
			$shipping_total = 0;

			if (isset($this->session->data['shipping_method'])) {
				$shipping_total = $this->tax->calculate($this->session->data['shipping_method']['cost'], $this->session->data['shipping_method']['tax_class_id'], $this->config->get('config_tax'));
				$shipping_total = number_format($shipping_total * $currency_value, $decimal_place, '.', '');
			}

			$order_total = number_format($order_data['total'] * $currency_value, $decimal_place, '.', '');
			$rebate = number_format($item_total + $tax_total + $shipping_total - $order_total, $decimal_place, '.', '');

			if ($rebate > 0) {
				$discount_total = $rebate;
			} elseif ($rebate < 0) {
				$handling_total = -$rebate;
			}

			$amount_info = [
				'currency_code' => $currency_code,
				'value'         => $order_total,
				'breakdown'     => [
					'item_total' => [
						'currency_code' => $currency_code,
						'value'         => $item_total
					],
					'tax_total' => [
						'currency_code' => $currency_code,
						'value'         => $tax_total
					],
					'shipping' => [
						'currency_code' => $currency_code,
						'value'         => $shipping_total
					],
					'handling' => [
						'currency_code' => $currency_code,
						'value'         => $handling_total
					],
					'discount' => [
						'currency_code' => $currency_code,
						'value'         => $discount_total
					]
				]
			];

			$order_info[] = [
				'op'    => 'replace',
				'path'  => '/purchase_units/@reference_id==\'default\'/amount',
				'value' => $amount_info
			];

			$result = $paypal->updateOrder($order_id, $order_info);

			if ($transaction_method == 'authorize') {
				$result = $paypal->setOrderAuthorize($order_id);

				if (isset($result['purchase_units'][0]['payments']['authorizations'][0]['seller_protection'])) {
					$seller_protection_status = $result['purchase_units'][0]['payments']['authorizations'][0]['seller_protection']['status'];
				}
			} else {
				$result = $paypal->setOrderCapture($order_id);

				if (isset($result['purchase_units'][0]['payments']['captures'][0]['seller_protection'])) {
					$seller_protection_status = $result['purchase_units'][0]['payments']['captures'][0]['seller_protection']['status'];
				}
			}

			if (!$this->cart->hasShipping()) {
				$seller_protection_status = 'NOT_ELIGIBLE';
			}

			if ($paypal->hasErrors()) {
				$error_messages = [];

				$errors = $paypal->getErrors();

				foreach ($errors as $error) {
					if (isset($error['name']) && ($error['name'] == 'CURLE_OPERATION_TIMEOUTED')) {
						$error['message'] = $this->language->get('error_timeout');
					}

					if (isset($error['details'][0]['description'])) {
						$error_messages[] = $error['details'][0]['description'];
					} else {
						$error_messages[] = $error['message'];
					}

					$this->model_extension_module_paypal_smart_button->log($error, $error['message']);
				}

				$this->error['warning'] = implode(' ', $error_messages);
			}

			unset($this->session->data['paypal_order_id']);

			if (!$this->error && isset($seller_protection_status)) {
				$message = sprintf($this->language->get('text_order_message'), $seller_protection_status);

				$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('config_order_status_id'), $message);

				$this->response->redirect($this->url->link('checkout/success', '', true));
			} else {
				$this->session->data['error'] = $this->error['warning'];

				$this->response->redirect($this->url->link('checkout/checkout', '', true));
			}
		}

		$this->response->redirect($this->url->link('checkout/cart', '', true));
	}

	/**
	 * Payment Address
	 *
	 * @return void
	 */
	public function paymentAddress(): void {
		$this->load->language('extension/module/paypal_smart_button');

		// Countries
		$this->load->model('localisation/country');

		$data['guest'] = $this->session->data['guest'] ?? [];

		$data['payment_address'] = $this->session->data['payment_address'] ?? [];

		$data['countries'] = $this->model_localisation_country->getCountries();

		// Custom Fields
		$this->load->model('account/custom_field');

		$data['custom_fields'] = $this->model_account_custom_field->getCustomFields();

		$this->response->setOutput($this->load->view('extension/module/paypal_smart_button/payment_address', $data));
	}

	/**
	 * Shipping Address
	 *
	 * @return void
	 */
	public function shippingAddress(): void {
		$this->load->language('extension/module/paypal_smart_button');

		// Countries
		$this->load->model('localisation/country');

		$data['shipping_address'] = $this->session->data['shipping_address'] ?? [];
		$data['countries'] = $this->model_localisation_country->getCountries();

		// Custom Fields
		$this->load->model('account/custom_field');

		$data['custom_fields'] = $this->model_account_custom_field->getCustomFields();

		$this->response->setOutput($this->load->view('extension/module/paypal_smart_button/shipping_address', $data));
	}

	/**
	 * Confirm Shipping
	 *
	 * @return void
	 */
	public function confirmShipping(): void {
		$this->validateShipping($this->request->post['shipping_method']);

		$this->response->redirect($this->url->link('extension/module/paypal_smart_button/confirmOrder', '', true));
	}

	/**
	 * Confirm Payment Address
	 *
	 * @return void
	 */
	public function confirmPaymentAddress(): void {
		$this->load->language('extension/module/paypal_smart_button');

		$data['url'] = '';

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validatePaymentAddress()) {
			$this->session->data['guest']['firstname'] = $this->request->post['firstname'];
			$this->session->data['guest']['lastname'] = $this->request->post['lastname'];
			$this->session->data['guest']['email'] = $this->request->post['email'];
			$this->session->data['guest']['telephone'] = $this->request->post['telephone'];

			if (isset($this->request->post['custom_field'])) {
				$this->session->data['guest']['custom_field'] = $this->request->post['custom_field'];
			} else {
				$this->session->data['guest']['custom_field'] = [];
			}

			$this->session->data['payment_address']['firstname'] = $this->request->post['firstname'];
			$this->session->data['payment_address']['lastname'] = $this->request->post['lastname'];
			$this->session->data['payment_address']['company'] = $this->request->post['company'];
			$this->session->data['payment_address']['address_1'] = $this->request->post['address_1'];
			$this->session->data['payment_address']['address_2'] = $this->request->post['address_2'];
			$this->session->data['payment_address']['postcode'] = $this->request->post['postcode'];
			$this->session->data['payment_address']['city'] = $this->request->post['city'];
			$this->session->data['payment_address']['country_id'] = (int)$this->request->post['country_id'];
			$this->session->data['payment_address']['zone_id'] = (int)$this->request->post['zone_id'];

			// Countries
			$this->load->model('localisation/country');

			$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

			if ($country_info) {
				$this->session->data['payment_address']['country'] = $country_info['name'];
				$this->session->data['payment_address']['iso_code_2'] = $country_info['iso_code_2'];
				$this->session->data['payment_address']['iso_code_3'] = $country_info['iso_code_3'];
				$this->session->data['payment_address']['address_format'] = $country_info['address_format'];
			} else {
				$this->session->data['payment_address']['country'] = '';
				$this->session->data['payment_address']['iso_code_2'] = '';
				$this->session->data['payment_address']['iso_code_3'] = '';
				$this->session->data['payment_address']['address_format'] = '';
			}

			if (isset($this->request->post['custom_field']['address'])) {
				$this->session->data['payment_address']['custom_field'] = $this->request->post['custom_field']['address'];
			} else {
				$this->session->data['payment_address']['custom_field'] = [];
			}

			// Zones
			$this->load->model('localisation/zone');

			$zone_info = $this->model_localisation_zone->getZone($this->request->post['zone_id']);

			if ($zone_info) {
				$this->session->data['payment_address']['zone'] = $zone_info['name'];
				$this->session->data['payment_address']['zone_code'] = $zone_info['code'];
			} else {
				$this->session->data['payment_address']['zone'] = '';
				$this->session->data['payment_address']['zone_code'] = '';
			}

			$data['url'] = $this->url->link('extension/module/paypal_smart_button/confirmOrder', '', true);
		}

		$data['error'] = $this->error;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}

	/**
	 * Confirm Shipping Address
	 *
	 * @return void
	 */
	public function confirmShippingAddress(): void {
		$this->load->language('extension/module/paypal_smart_button');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateShippingAddress()) {
			$this->session->data['shipping_address']['firstname'] = $this->request->post['firstname'];
			$this->session->data['shipping_address']['lastname'] = $this->request->post['lastname'];
			$this->session->data['shipping_address']['company'] = $this->request->post['company'];
			$this->session->data['shipping_address']['address_1'] = $this->request->post['address_1'];
			$this->session->data['shipping_address']['address_2'] = $this->request->post['address_2'];
			$this->session->data['shipping_address']['postcode'] = $this->request->post['postcode'];
			$this->session->data['shipping_address']['city'] = $this->request->post['city'];
			$this->session->data['shipping_address']['country_id'] = (int)$this->request->post['country_id'];
			$this->session->data['shipping_address']['zone_id'] = (int)$this->request->post['zone_id'];

			// Countries
			$this->load->model('localisation/country');

			$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

			if ($country_info) {
				$this->session->data['shipping_address']['country'] = $country_info['name'];
				$this->session->data['shipping_address']['iso_code_2'] = $country_info['iso_code_2'];
				$this->session->data['shipping_address']['iso_code_3'] = $country_info['iso_code_3'];
				$this->session->data['shipping_address']['address_format'] = $country_info['address_format'];
			} else {
				$this->session->data['shipping_address']['country'] = '';
				$this->session->data['shipping_address']['iso_code_2'] = '';
				$this->session->data['shipping_address']['iso_code_3'] = '';
				$this->session->data['shipping_address']['address_format'] = '';
			}

			// Zones
			$this->load->model('localisation/zone');

			$zone_info = $this->model_localisation_zone->getZone($this->request->post['zone_id']);

			if ($zone_info) {
				$this->session->data['shipping_address']['zone'] = $zone_info['name'];
				$this->session->data['shipping_address']['zone_code'] = $zone_info['code'];
			} else {
				$this->session->data['shipping_address']['zone'] = '';
				$this->session->data['shipping_address']['zone_code'] = '';
			}

			if (isset($this->request->post['custom_field'])) {
				$this->session->data['shipping_address']['custom_field'] = $this->request->post['custom_field']['address'];
			} else {
				$this->session->data['shipping_address']['custom_field'] = [];
			}

			$data['url'] = $this->url->link('extension/module/paypal_smart_button/confirmOrder', '', true);
		}

		$data['error'] = $this->error;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}

	/**
	 * Validate Shipping
	 *
	 * @param string $code
	 *
	 * @return bool
	 */
	private function validateShipping(string $code): bool {
		$this->load->language('checkout/cart');
		$this->load->language('extension/module/paypal_smart_button');

		if (!$code) {
			$this->session->data['error_warning'] = $this->language->get('error_shipping');

			return false;
		} else {
			$shipping = explode('.', $code);

			if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
				$this->session->data['error_warning'] = $this->language->get('error_shipping');

				return false;
			} else {
				$this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
				$this->session->data['success'] = $this->language->get('text_shipping_updated');

				return true;
			}
		}
	}

	/**
	 * Validate Payment Address
	 *
	 * @return bool
	 */
	private function validatePaymentAddress(): bool {
		if ((oc_strlen($this->request->post['firstname']) < 1) || (oc_strlen($this->request->post['firstname']) > 32)) {
			$this->error['firstname'] = $this->language->get('error_firstname');
		}

		if ((oc_strlen($this->request->post['lastname']) < 1) || (oc_strlen($this->request->post['lastname']) > 32)) {
			$this->error['lastname'] = $this->language->get('error_lastname');
		}

		if ((oc_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if ((oc_strlen($this->request->post['telephone']) < 3) || (oc_strlen($this->request->post['telephone']) > 32)) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		if ((oc_strlen($this->request->post['address_1']) < 3) || (oc_strlen($this->request->post['address_1']) > 128)) {
			$this->error['address_1'] = $this->language->get('error_address_1');
		}

		if ((oc_strlen($this->request->post['city']) < 2) || (oc_strlen($this->request->post['city']) > 128)) {
			$this->error['city'] = $this->language->get('error_city');
		}

		// Countries
		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

		if ($country_info && $country_info['postcode_required'] && (oc_strlen($this->request->post['postcode']) < 2 || oc_strlen($this->request->post['postcode']) > 10)) {
			$this->error['postcode'] = $this->language->get('error_postcode');
		}

		if ($this->request->post['country_id'] == '') {
			$this->error['country'] = $this->language->get('error_country');
		}

		if (!isset($this->request->post['zone_id']) || $this->request->post['zone_id'] == '' || !is_numeric($this->request->post['zone_id'])) {
			$this->error['zone'] = $this->language->get('error_zone');
		}

		// Customer Groups
		if (isset($this->request->post['customer_group_id']) && in_array($this->request->post['customer_group_id'], (array)$this->config->get('config_customer_group_display'))) {
			$customer_group_id = (int)$this->request->post['customer_group_id'];
		} else {
			$customer_group_id = (int)$this->config->get('config_customer_group_id');
		}

		// Custom field validation
		$this->load->model('account/custom_field');

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
				$this->error['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
			} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => $custom_field['validation']]])) {
				$this->error['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
			}
		}

		return !$this->error;
	}

	/**
	 * Validate Shipping Address
	 *
	 * @return bool
	 */
	private function validateShippingAddress(): bool {
		if ((oc_strlen($this->request->post['firstname']) < 1) || (oc_strlen($this->request->post['firstname']) > 32)) {
			$this->error['firstname'] = $this->language->get('error_firstname');
		}

		if ((oc_strlen($this->request->post['lastname']) < 1) || (oc_strlen($this->request->post['lastname']) > 32)) {
			$this->error['lastname'] = $this->language->get('error_lastname');
		}

		if ((oc_strlen($this->request->post['address_1']) < 3) || (oc_strlen($this->request->post['address_1']) > 128)) {
			$this->error['address_1'] = $this->language->get('error_address_1');
		}

		if ((oc_strlen($this->request->post['city']) < 2) || (oc_strlen($this->request->post['city']) > 128)) {
			$this->error['city'] = $this->language->get('error_city');
		}

		// Countries
		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

		if ($country_info && $country_info['postcode_required'] && (oc_strlen($this->request->post['postcode']) < 2 || oc_strlen($this->request->post['postcode']) > 10)) {
			$this->error['postcode'] = $this->language->get('error_postcode');
		}

		if ($this->request->post['country_id'] == '') {
			$this->error['country'] = $this->language->get('error_country');
		}

		if (!isset($this->request->post['zone_id']) || $this->request->post['zone_id'] == '' || !is_numeric($this->request->post['zone_id'])) {
			$this->error['zone'] = $this->language->get('error_zone');
		}

		// Customer Groups
		if (isset($this->request->post['customer_group_id']) && in_array($this->request->post['customer_group_id'], (array)$this->config->get('config_customer_group_display'))) {
			$customer_group_id = (int)$this->request->post['customer_group_id'];
		} else {
			$customer_group_id = (int)$this->config->get('config_customer_group_id');
		}

		// Custom field validation
		$this->load->model('account/custom_field');

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'address') {
				if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
					$this->error['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
				} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !preg_match(html_entity_decode($custom_field['validation'], ENT_QUOTES, 'UTF-8'), $this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
					$this->error['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_regex'), $custom_field['name']);
				}
			}
		}

		return !$this->error;
	}

	/**
	 * Validate Coupon
	 *
	 * @return bool
	 */
	private function validateCoupon(): bool {
		// Coupons
		$this->load->model('extension/total/coupon');

		$coupon_info = $this->model_extension_total_coupon->getCoupon($this->request->post['coupon']);

		if ($coupon_info) {
			return true;
		} else {
			$this->session->data['error_warning'] = $this->language->get('error_coupon');

			return false;
		}
	}

	/**
	 * Validate Voucher
	 *
	 * @return bool
	 */
	private function validateVoucher(): bool {
		// Gift Voucher
		$this->load->model('extension/total/voucher');

		$voucher_info = $this->model_extension_total_voucher->getVoucher($this->request->post['voucher']);

		if ($voucher_info) {
			return true;
		} else {
			$this->session->data['error_warning'] = $this->language->get('error_voucher');

			return false;
		}
	}

	/**
	 * Validate Reward
	 *
	 * @return bool
	 */
	private function validateReward(): bool {
		$points = $this->customer->getRewardPoints();

		$points_total = 0;

		foreach ($this->cart->getProducts() as $product) {
			if ($product['points']) {
				$points_total += $product['points'];
			}
		}

		$error = '';

		if (empty($this->request->post['reward'])) {
			$error = $this->language->get('error_reward');
		}

		if ($this->request->post['reward'] > $points) {
			$error = sprintf($this->language->get('error_points'), $this->request->post['reward']);
		}

		if ($this->request->post['reward'] > $points_total) {
			$error = sprintf($this->language->get('error_maximum'), $points_total);
		}

		if (!$error) {
			return true;
		} else {
			$this->session->data['error_warning'] = $error;

			return false;
		}
	}
}
