<?php
class ModelExtensionPaymentPayPal extends Model {
	public function getMethod(array $address, float $total): array {
		$method_data = [];

		$agree_status = $this->getAgreeStatus();

		if ($this->config->get('payment_paypal_status') && $this->config->get('payment_paypal_client_id') && $this->config->get('payment_paypal_secret') && $agree_status) {
			$this->load->language('extension/payment/paypal');

			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_paypal_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

			if (($this->config->get('payment_paypal_total') > 0) && ($this->config->get('payment_paypal_total') > $total)) {
				$status = false;
			} elseif (!$this->config->get('payment_paypal_geo_zone_id')) {
				$status = true;
			} elseif ($query->num_rows) {
				$status = true;
			} else {
				$status = false;
			}

			if ($status) {
				$method_data = [
					'code'       => 'paypal',
					'title'      => $this->language->get('text_paypal_title'),
					'terms'      => '',
					'sort_order' => $this->config->get('payment_paypal_sort_order')
				];
			}
		}

		return $method_data;
	}

	public function hasProductInCart(int $product_id, array $option = [], int $subscription_plan_id = 0): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "cart` WHERE `api_id` = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND `customer_id` = '" . (int)$this->customer->getId() . "' AND `session_id` = '" . $this->db->escape($this->session->getId()) . "' AND `product_id` = '" . (int)$product_id . "' AND `subscription_plan_id` = '" . (int)$subscription_plan_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");

		return (int)$query->row['total'];
	}

	public function getCountryByCode(string $code): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE `iso_code_2` = '" . $this->db->escape($code) . "' AND `status` = '1'");

		return $query->row;
	}

	public function getZoneByCode(int $country_id, string $code): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE `country_id` = '" . (int)$country_id . "' AND (`code` = '" . $this->db->escape($code) . "' OR `name` = '" . $this->db->escape($code) . "') AND `status` = '1'");

		return $query->row;
	}

	public function addPayPalOrder(array $data): void {
		$sql = "INSERT INTO `" . DB_PREFIX . "paypal_checkout_integration_order` SET";

		$implode = [];

		if (!empty($data['order_id'])) {
			$implode[] = "`order_id` = '" . (int)$data['order_id'] . "'";
		}

		if (!empty($data['transaction_id'])) {
			$implode[] = "`transaction_id` = '" . $this->db->escape($data['transaction_id']) . "'";
		}

		if (!empty($data['transaction_status'])) {
			$implode[] = "`transaction_status` = '" . $this->db->escape($data['transaction_status']) . "'";
		}

		if (!empty($data['payment_method'])) {
			$implode[] = "`payment_method` = '" . $this->db->escape($data['payment_method']) . "'";
		}

		if (!empty($data['vault_id'])) {
			$implode[] = "`vault_id` = '" . $this->db->escape($data['vault_id']) . "'";
		}

		if (!empty($data['vault_customer_id'])) {
			$implode[] = "`vault_customer_id` = '" . $this->db->escape($data['vault_customer_id']) . "'";
		}

		if (!empty($data['environment'])) {
			$implode[] = "`environment` = '" . $this->db->escape($data['environment']) . "'";
		}

		if ($implode) {
			$sql .= implode(", ", $implode);
		}

		$this->db->query($sql);
	}

	public function editPayPalOrder(array $data): void {
		$sql = "UPDATE `" . DB_PREFIX . "paypal_checkout_integration_order` SET";

		$implode = [];

		if (!empty($data['transaction_id'])) {
			$implode[] = "`transaction_id` = '" . $this->db->escape($data['transaction_id']) . "'";
		}

		if (!empty($data['transaction_status'])) {
			$implode[] = "`transaction_status` = '" . $this->db->escape($data['transaction_status']) . "'";
		}

		if (!empty($data['payment_method'])) {
			$implode[] = "`payment_method` = '" . $this->db->escape($data['payment_method']) . "'";
		}

		if (!empty($data['vault_id'])) {
			$implode[] = "`vault_id` = '" . $this->db->escape($data['vault_id']) . "'";
		}

		if (!empty($data['vault_customer_id'])) {
			$implode[] = "`vault_customer_id` = '" . $this->db->escape($data['vault_customer_id']) . "'";
		}

		if (!empty($data['environment'])) {
			$implode[] = "`environment` = '" . $this->db->escape($data['environment']) . "'";
		}

		if ($implode) {
			$sql .= implode(", ", $implode);
		}

		$sql .= " WHERE `order_id` = '" . (int)$data['order_id'] . "'";

		$this->db->query($sql);
	}

	public function deletePayPalOrder(int $order_id): void {
		$query = $this->db->query("DELETE FROM `" . DB_PREFIX . "paypal_checkout_integration_order` WHERE `order_id` = '" . (int)$order_id . "'");
	}

	public function getPayPalOrder(int $order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "paypal_checkout_integration_order` WHERE `order_id` = '" . (int)$order_id . "'");

		if ($query->num_rows) {
			return $query->row;
		} else {
			return [];
		}
	}

	public function addPayPalOrderRecurring(array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "paypal_checkout_integration_order_recurring` SET `order_recurring_id` = '" . (int)$data['order_recurring_id'] . "', `order_id` = '" . (int)$data['order_id'] . "', `date_added` = NOW(), `date_modified` = NOW(), `next_payment` = NOW(), `trial_end` = '" . $data['trial_end'] . "', `subscription_end` = '" . $data['subscription_end'] . "', `currency_code` = '" . $this->db->escape($data['currency_code']) . "', `total` = '" . $this->currency->format($data['amount'], $data['currency_code'], false, false) . "'");
	}

	public function editPayPalOrderRecurringNextPayment(int $order_recurring_id, string $next_payment): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "paypal_checkout_integration_order_recurring` SET `next_payment` = '" . $next_payment . "', `date_modified` = NOW() WHERE `order_recurring_id` = '" . (int)$order_recurring_id . "'");
	}

	public function deletePayPalOrderRecurring(int $order_id): void {
		$query = $this->db->query("DELETE FROM `" . DB_PREFIX . "paypal_checkout_integration_order_recurring` WHERE `order_id` = '" . (int)$order_id . "'");
	}

	public function getPayPalOrderRecurring(int $order_recurring_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "paypal_checkout_integration_order_recurring` WHERE `order_recurring_id` = '" . (int)$order_recurring_id . "'");

		return $query->row;
	}

	public function addOrderRecurring(int $order_id, string $description, array $data, string $reference): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring` SET `order_id` = '" . (int)$order_id . "', `date_added` = NOW(), `status` = 6, `product_id` = '" . (int)$data['product_id'] . "', `product_name` = '" . $this->db->escape($data['name']) . "', `product_quantity` = '" . $this->db->escape($data['quantity']) . "', `recurring_id` = '" . (int)$data['recurring']['recurring_id'] . "', `recurring_name` = '" . $this->db->escape($data['name']) . "', `recurring_description` = '" . $this->db->escape($description) . "', `recurring_frequency` = '" . $this->db->escape($data['recurring']['frequency']) . "', `recurring_cycle` = '" . (int)$data['recurring']['cycle'] . "', `recurring_duration` = '" . (int)$data['recurring']['duration'] . "', `recurring_price` = '" . (float)$data['recurring']['price'] . "', `trial` = '" . (int)$data['recurring']['trial'] . "', `trial_frequency` = '" . $this->db->escape($data['recurring']['trial_frequency']) . "', `trial_cycle` = '" . (int)$data['recurring']['trial_cycle'] . "', `trial_duration` = '" . (int)$data['recurring']['trial_duration'] . "', `trial_price` = '" . (float)$data['recurring']['trial_price'] . "', `reference` = '" . $this->db->escape($reference) . "'");

		return $this->db->getLastId();
	}

	public function editOrderRecurringStatus(int $subscription_id, int $status): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "order_subscription` SET `status` = '" . (int)$status . "' WHERE `subscription_id` = '" . (int)$subscription_id . "'");
	}

	public function deleteOrderRecurring(int $order_id): void {
		$query = $this->db->query("SELECT `subscription_id` FROM `" . DB_PREFIX . "order_subscription` WHERE `order_id` = '" . (int)$order_id . "'");

		foreach ($query->rows as $order_subscription) {
			$this->deleteOrderRecurringTransaction($order_subscription['subscription_id']);
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_recurring` WHERE `order_id` = '" . (int)$order_id . "'");
	}

	public function getOrderRecurrings(): array {
		$query = $this->db->query("SELECT `or`.`order_recurring_id` FROM `" . DB_PREFIX . "order_recurring` `or` JOIN `" . DB_PREFIX . "order` `o` USING(`order_id`) WHERE `o`.`payment_code` = 'paypal' AND `or`.`status` = '1'");

		$order_recurring_data = [];

		foreach ($query->rows as $order_recurring) {
			$order_recurring_data[] = $this->getOrderRecurring($order_recurring['order_recurring_id']);
		}

		return $order_recurring_data;
	}

	public function getOrderRecurring($subscription_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_subscription` WHERE `subscription_id` = '" . (int)$subscription_id . "'");

		return $query->row;
	}

	public function addOrderRecurringTransaction(array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring_transaction` SET `order_recurring_id` = '" . (int)$data['order_recurring_id'] . "', `reference` = '" . $this->db->escape($data['reference']) . "', `type` = '" . (int)$data['type'] . "', `amount` = '" . (float)$data['amount'] . "', `date_added` = NOW()");
	}

	public function deleteOrderRecurringTransaction(int $order_recurring_id): void {
		$query = $this->db->query("DELETE FROM `" . DB_PREFIX . "order_recurring_transaction` WHERE `order_recurring_id` = '" . (int)$order_recurring_id . "'");
	}

	public function recurringPayment(array $product_data, array $order_data, array $paypal_order_data): void {
		$_config = new Config();
		$_config->load('paypal');

		$config_setting = $_config->get('paypal_setting');

		$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_paypal_setting'));

		$transaction_method = $setting['general']['transaction_method'];

		$recurring_name = $product_data['recurring']['name'];

		if ($product_data['recurring']['trial'] == 1) {
			$price = $product_data['recurring']['trial_price'];
			$trial_amt = $this->currency->format($this->tax->calculate($product_data['recurring']['trial_price'], $product_data['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], false, false) * $product_data['quantity'] . ' ' . $this->session->data['currency'];
			$trial_text = sprintf($this->language->get('text_trial'), $trial_amt, $product_data['recurring']['trial_cycle'], $product_data['recurring']['trial_frequency'], $product_data['recurring']['trial_duration']);
		} else {
			$price = $product_data['recurring']['price'];
			$trial_text = '';
		}

		$recurring_amt = $this->currency->format($this->tax->calculate($product_data['recurring']['price'], $product_data['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], false, false) * $product_data['quantity'] . ' ' . $this->session->data['currency'];
		$recurring_description = $trial_text . sprintf($this->language->get('text_recurring'), $recurring_amt, $product_data['recurring']['cycle'], $product_data['recurring']['frequency']);

		if ($product_data['recurring']['duration'] > 0) {
			$recurring_description .= sprintf($this->language->get('text_length'), $product_data['recurring']['duration']);
		}

		$order_recurring_id = $this->addOrderRecurring($order_data['order_id'], $recurring_description, $product_data, $paypal_order_data['transaction_id']);

		$next_payment = new DateTime('now');
		$trial_end = new DateTime('now');
		$subscription_end = new DateTime('now');

		if (($product_data['recurring']['trial'] == 1) && ($product_data['recurring']['trial_duration'] != 0)) {
			$next_payment = $this->calculateSchedule($product_data['recurring']['trial_frequency'], $next_payment, $product_data['recurring']['trial_cycle']);
			$trial_end = $this->calculateSchedule($product_data['recurring']['trial_frequency'], $trial_end, $product_data['recurring']['trial_cycle'] * $product_data['recurring']['trial_duration']);
		} elseif ($product_data['recurring']['trial'] == 1) {
			$next_payment = $this->calculateSchedule($product_data['recurring']['trial_frequency'], $next_payment, $product_data['recurring']['trial_cycle']);
			$trial_end = new DateTime('0000-00-00');
		}

		if (date_format($trial_end, 'Y-m-d H:i:s') > date_format($subscription_end, 'Y-m-d H:i:s') && $product_data['recurring']['duration'] != 0) {
			$subscription_end = new DateTime(date_format($trial_end, 'Y-m-d H:i:s'));
			$subscription_end = $this->calculateSchedule($product_data['recurring']['frequency'], $subscription_end, $product_data['recurring']['cycle'] * $product_data['recurring']['duration']);
		} elseif (date_format($trial_end, 'Y-m-d H:i:s') == date_format($subscription_end, 'Y-m-d H:i:s') && $product_data['recurring']['duration'] != 0) {
			$next_payment = $this->calculateSchedule($product_data['recurring']['frequency'], $next_payment, $product_data['recurring']['cycle']);
			$subscription_end = $this->calculateSchedule($product_data['recurring']['frequency'], $subscription_end, $product_data['recurring']['cycle'] * $product_data['recurring']['duration']);
		} elseif (date_format($trial_end, 'Y-m-d H:i:s') > date_format($subscription_end, 'Y-m-d H:i:s') && $product_data['recurring']['duration'] == 0) {
			$subscription_end = new DateTime('0000-00-00');
		} elseif (date_format($trial_end, 'Y-m-d H:i:s') == date_format($subscription_end, 'Y-m-d H:i:s') && $product_data['recurring']['duration'] == 0) {
			$next_payment = $this->calculateSchedule($product_data['recurring']['frequency'], $next_payment, $product_data['recurring']['cycle']);
			$subscription_end = new DateTime('0000-00-00');
		}

		$result = $this->createPayment($order_data, $paypal_order_data, $price, $order_recurring_id, $recurring_name);

		$transaction_status = '';
		$transaction_id = '';
		$currency_code = '';
		$amount = '';

		if ($transaction_method == 'authorize') {
			if (isset($result['purchase_units'][0]['payments']['authorizations'][0]['status']) && isset($result['purchase_units'][0]['payments']['authorizations'][0]['seller_protection']['status'])) {
				$transaction_id = $result['purchase_units'][0]['payments']['authorizations'][0]['id'];
				$transaction_status = $result['purchase_units'][0]['payments']['authorizations'][0]['status'];
				$currency_code = $result['purchase_units'][0]['payments']['authorizations'][0]['amount']['currency_code'];
				$amount = $result['purchase_units'][0]['payments']['authorizations'][0]['amount']['value'];
			}
		} else {
			if (isset($result['purchase_units'][0]['payments']['captures'][0]['status']) && isset($result['purchase_units'][0]['payments']['captures'][0]['seller_protection']['status'])) {
				$transaction_id = $result['purchase_units'][0]['payments']['captures'][0]['id'];
				$transaction_status = $result['purchase_units'][0]['payments']['captures'][0]['status'];
				$currency_code = $result['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'];
				$amount = $result['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
			}
		}

		if ($transaction_id && $transaction_status && $currency_code && $amount) {
			$this->editOrderRecurringStatus($order_recurring_id, 1);

			$paypal_order_recurring_data = [
				'order_recurring_id' => $order_recurring_id,
				'order_id'           => $order_data['order_id'],
				'trial_end'          => date_format($trial_end, 'Y-m-d H       : i: s'),
				'subscription_end'   => date_format($subscription_end, 'Y-m-d H: i: s'),
				'currency_code'      => $currency_code,
				'amount'             => $amount
			];

			$this->addPayPalOrderRecurring($paypal_order_recurring_data);

			if (($transaction_status == 'CREATED') || ($transaction_status == 'COMPLETED') || ($transaction_status == 'PENDING')) {
				$order_recurring_transaction_data = [
					'order_recurring_id' => $order_recurring_id,
					'reference'          => $transaction_id,
					'type'               => '1',
					'amount'             => $amount
				];

				$this->addOrderRecurringTransaction($order_recurring_transaction_data);

				$this->editPayPalOrderRecurringNextPayment($order_recurring_id, date_format($next_payment, 'Y-m-d H:i:s'));
			} else {
				$order_recurring_transaction_data = [
					'order_recurring_id' => $order_recurring_id,
					'reference'          => $transaction_id,
					'type'               => '4',
					'amount'             => $amount
				];

				$this->addOrderRecurringTransaction($order_recurring_transaction_data);
			}
		}
	}

	public function cronPayment(): void {
		$this->load->model('checkout/order');

		$_config = new Config();
		$_config->load('paypal');

		$config_setting = $_config->get('paypal_setting');

		$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_paypal_setting'));

		$transaction_method = $setting['general']['transaction_method'];

		$order_recurrings = $this->getOrderRecurrings();

		foreach ($order_recurrings as $order_recurring) {
			if ($order_recurring['status'] == 1) {
				$paypal_order_recurring = $this->getPayPalOrderRecurring($order_recurring['order_recurring_id']);

				if ($paypal_order_recurring) {
					$today = new DateTime('now');
					$unlimited = new DateTime('0000-00-00');
					$next_payment = new DateTime($paypal_order_recurring['next_payment']);
					$trial_end = new DateTime($paypal_order_recurring['trial_end']);
					$subscription_end = new DateTime($paypal_order_recurring['subscription_end']);

					$order_info = $this->model_checkout_order->getOrder($order_recurring['order_id']);

					$paypal_order_info = $this->getPayPalOrder($order_recurring['order_id']);

					if ((date_format($today, 'Y-m-d H:i:s') > date_format($next_payment, 'Y-m-d H:i:s')) && (date_format($trial_end, 'Y-m-d H:i:s') > date_format($today, 'Y-m-d H:i:s') || date_format($trial_end, 'Y-m-d H:i:s') == date_format($unlimited, 'Y-m-d H:i:s'))) {
						$price = $this->currency->format($order_recurring['trial_price'], $order_info['currency_code'], false, false);
						$frequency = $order_recurring['trial_frequency'];
						$cycle = $order_recurring['trial_cycle'];
						$next_payment = $this->calculateSchedule($frequency, $next_payment, $cycle);
					} elseif ((date_format($today, 'Y-m-d H:i:s') > date_format($next_payment, 'Y-m-d H:i:s')) && (date_format($subscription_end, 'Y-m-d H:i:s') > date_format($today, 'Y-m-d H:i:s') || date_format($subscription_end, 'Y-m-d H:i:s') == date_format($unlimited, 'Y-m-d H:i:s'))) {
						$price = $this->currency->format($order_recurring['recurring_price'], $order_info['currency_code'], false, false);
						$frequency = $order_recurring['recurring_frequency'];
						$cycle = $order_recurring['recurring_cycle'];
						$next_payment = $this->calculateSchedule($frequency, $next_payment, $cycle);
					} else {
						continue;
					}

					$result = $this->createPayment($order_info, $paypal_order_info, $price, $order_recurring['order_recurring_id'], $order_recurring['recurring_name']);

					$transaction_status = '';
					$transaction_id = '';
					$currency_code = '';
					$amount = '';

					if ($transaction_method == 'authorize') {
						if (isset($result['purchase_units'][0]['payments']['authorizations'][0]['status']) && isset($result['purchase_units'][0]['payments']['authorizations'][0]['seller_protection']['status'])) {
							$transaction_id = $result['purchase_units'][0]['payments']['authorizations'][0]['id'];
							$transaction_status = $result['purchase_units'][0]['payments']['authorizations'][0]['status'];
							$currency_code = $result['purchase_units'][0]['payments']['authorizations'][0]['amount']['currency_code'];
							$amount = $result['purchase_units'][0]['payments']['authorizations'][0]['amount']['value'];
						}
					} else {
						if (isset($result['purchase_units'][0]['payments']['captures'][0]['status']) && isset($result['purchase_units'][0]['payments']['captures'][0]['seller_protection']['status'])) {
							$transaction_id = $result['purchase_units'][0]['payments']['captures'][0]['id'];
							$transaction_status = $result['purchase_units'][0]['payments']['captures'][0]['status'];
							$currency_code = $result['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'];
							$amount = $result['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
						}
					}

					if ($transaction_id && $transaction_status && $currency_code && $amount) {
						if (($transaction_status == 'CREATED') || ($transaction_status == 'COMPLETED') || ($transaction_status == 'PENDING')) {
							$order_recurring_transaction_data = [
								'order_recurring_id' => $order_recurring['order_recurring_id'],
								'reference'          => $transaction_id,
								'type'               => '1',
								'amount'             => $amount
							];

							$this->addOrderRecurringTransaction($order_recurring_transaction_data);

							$this->editPayPalOrderRecurringNextPayment($order_recurring['order_recurring_id'], date_format($next_payment, 'Y-m-d H:i:s'));
						} else {
							$order_recurring_transaction_data = [
								'order_recurring_id' => $order_recurring['order_recurring_id'],
								'reference'          => $transaction_id,
								'type'               => '4',
								'amount'             => $amount
							];

							$this->addOrderRecurringTransaction($order_recurring_transaction_data);
						}
					}
				}
			}
		}
	}

	public function createPayment(array $order_data, array $paypal_order_data, float $price, int $order_recurring_id, string $recurring_name): array {
		$this->load->language('extension/payment/paypal');

		$_config = new Config();
		$_config->load('paypal');

		$config_setting = $_config->get('paypal_setting');

		$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_paypal_setting'));

		$client_id = $this->config->get('payment_paypal_client_id');
		$secret = $this->config->get('payment_paypal_secret');
		$merchant_id = $this->config->get('payment_paypal_merchant_id');
		$environment = $this->config->get('payment_paypal_environment');
		$partner_id = $setting['partner'][$environment]['partner_id'];
		$partner_attribution_id = $setting['partner'][$environment]['partner_attribution_id'];
		$transaction_method = $setting['general']['transaction_method'];

		$currency_code = $order_data['currency_code'];
		$currency_value = $this->currency->getValue($currency_code);
		$decimal_place = $setting['currency'][$currency_code]['decimal_place'];

		require_once DIR_SYSTEM . 'library/paypal/paypal.php';

		$paypal_info = [
			'partner_id'             => $partner_id,
			'client_id'              => $client_id,
			'secret'                 => $secret,
			'environment'            => $environment,
			'partner_attribution_id' => $partner_attribution_id
		];

		$paypal = new PayPal($paypal_info);

		$token_info = [
			'grant_type' => 'client_credentials'
		];

		$paypal->setAccessToken($token_info);

		$item_info = [];

		$item_total = 0;

		$product_price = number_format($price * $currency_value, $decimal_place, '.', '');

		$item_info[] = [
			'name'        => $recurring_name,
			'quantity'    => 1,
			'unit_amount' => [
				'currency_code' => $currency_code,
				'value'         => $product_price
			]
		];

		$item_total += $product_price;

		$item_total = number_format($item_total, $decimal_place, '.', '');
		$order_total = number_format($item_total, $decimal_place, '.', '');

		$amount_info = [];

		$amount_info['currency_code'] = $currency_code;
		$amount_info['value'] = $order_total;

		$amount_info['breakdown']['item_total'] = [
			'currency_code' => $currency_code,
			'value'         => $item_total
		];

		$paypal_order_info = [];

		$paypal_order_info['intent'] = strtoupper($transaction_method);
		$paypal_order_info['purchase_units'][0]['reference_id'] = 'default';
		$paypal_order_info['purchase_units'][0]['items'] = $item_info;
		$paypal_order_info['purchase_units'][0]['amount'] = $amount_info;

		$paypal_order_info['purchase_units'][0]['description'] = 'Subscription to order ' . $order_data['order_id'];

		$shipping_preference = 'NO_SHIPPING';

		$paypal_order_info['application_context']['shipping_preference'] = $shipping_preference;

		$paypal_order_info['payment_source'][$paypal_order_data['payment_method']]['vault_id'] = $paypal_order_data['vault_id'];

		$result = $paypal->createOrder($paypal_order_info);

		$errors = [];

		if ($paypal->hasErrors()) {
			$errors = $paypal->getErrors();

			foreach ($errors as $error) {
				if (isset($error['name']) && ($error['name'] == 'CURLE_OPERATION_TIMEOUTED')) {
					$error['message'] = $this->language->get('error_timeout');
				}

				$this->log($error, $error['message']);
			}
		}

		if (isset($result['id']) && isset($result['status']) && !$errors) {
			$this->log($result, 'Create Recurring Payment');

			return $result;
		}

		return [];
	}

	public function calculateSchedule(string $frequency, string $next_payment, int $cycle): string {
		if ($frequency == 'semi_month') {
			$day = date_format($next_payment, 'd');
			$value = 15 - $day;
			$is_even = false;

			if ($cycle % 2 == 0) {
				$is_even = true;
			}

			$odd = ($cycle + 1) / 2;
			$plus_even = ($cycle / 2) + 1;
			$minus_even = $cycle / 2;

			if ($day == 1) {
				$odd--;
				$plus_even--;
				$day = 16;
			}

			if ($day <= 15 && $is_even) {
				$next_payment->modify('+' . $value . ' day');
				$next_payment->modify('+' . $minus_even . ' month');
			} elseif ($day <= 15) {
				$next_payment->modify('first day of this month');
				$next_payment->modify('+' . $odd . ' month');
			} elseif ($day > 15 && $is_even) {
				$next_payment->modify('first day of this month');
				$next_payment->modify('+' . $plus_even . ' month');
			} elseif ($day > 15) {
				$next_payment->modify('+' . $value . ' day');
				$next_payment->modify('+' . $odd . ' month');
			}
		} else {
			$next_payment->modify('+' . $cycle . ' ' . $frequency);
		}

		return $next_payment;
	}

	public function getAgreeStatus(): bool {
		$agree_status = true;

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE status = '1' AND (iso_code_2 = 'CU' OR iso_code_2 = 'IR' OR iso_code_2 = 'SY' OR iso_code_2 = 'KP')");

		if ($query->rows) {
			$agree_status = false;
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE country_id = '220' AND status = '1' AND (`code` = '43' OR `code` = '14' OR `code` = '09')");

		if ($query->rows) {
			$agree_status = false;
		}

		return $agree_status;
	}

	public function log(array $data, ?string $title = ''): void {
		// Setting
		$_config = new Config();
		$_config->load('paypal');

		$config_setting = $_config->get('paypal_setting');

		$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_paypal_setting'));

		if ($setting['general']['debug']) {
			$log = new Log('paypal.log');
			$log->write('PayPal debug (' . $title . '): ' . json_encode($data));
		}
	}

	public function update(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "paypal_checkout_integration_order`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "paypal_checkout_integration_order_recurring`");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paypal_checkout_integration_order` (`order_id` INT(11) NOT NULL, `transaction_id` VARCHAR(20) NOT NULL, `transaction_status` VARCHAR(20) NULL, `payment_method` VARCHAR(20) NULL, `vault_id` VARCHAR(50) NULL, `vault_customer_id` VARCHAR(50) NULL, `environment` VARCHAR(20) NULL, PRIMARY KEY (`order_id`, `transaction_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paypal_checkout_integration_order_recurring` (`paypal_order_recurring_id` INT(11) NOT NULL AUTO_INCREMENT, `order_id` INT(11) NOT NULL, `order_recurring_id` INT(11) NOT NULL, `date_added` DATETIME NOT NULL, `date_modified` DATETIME NOT NULL, `next_payment` DATETIME NOT NULL, `trial_end` DATETIME DEFAULT NULL, `subscription_end` DATETIME DEFAULT NULL, `currency_code` CHAR(3) NOT NULL, `total` DECIMAL(10, 2) NOT NULL, PRIMARY KEY (`paypal_order_recurring_id`), KEY (`order_id`), KEY (`order_recurring_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'paypal_order_info'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'paypal_header'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'paypal_extension_get_extensions'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'paypal_order_delete_order'");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'paypal_order_info', `trigger` = 'admin/view/sale/order_info/before', `action` = 'extension/payment/paypal/order_info_before', `sort_order` = '0', `status` = '1'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'paypal_header', `trigger` = 'catalog/controller/common/header/before', `action` = 'extension/payment/paypal/header_before', `sort_order` = '0', `status` = '1'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'paypal_extension_get_extensions', `trigger` = 'catalog/model/setting/extension/getExtensions/after', `action` = 'extension/payment/paypal/extension_get_extensions_after', `sort_order` = '0', `status` = '1'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'paypal_order_delete_order', `trigger` = 'catalog/model/checkout/order/deleteOrder/before', `action` = 'extension/payment/paypal/order_delete_order_before', `sort_order` = '0', `status` = '1'");

		// Setting
		$_config = new Config();
		$_config->load('paypal');

		$config_setting = $_config->get('paypal_setting');

		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' AND `code` = 'paypal_version'");

		$this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'paypal_version', `key` = 'paypal_version', `value` = '" . $this->db->escape($config_setting['version']) . "'");
	}

	public function recurringPayments(): bool {
		/*
		 * Used by the checkout to state the module
		 * supports recurring recurrings.
		 */
		return true;
	}
}
