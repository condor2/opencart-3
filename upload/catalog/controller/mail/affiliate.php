<?php
class ControllerMailAffiliate extends Controller {
	// catalog/model/account/customer/addAffiliate/after
	public function index(&$route, &$args, &$output) {
		$this->load->language('mail/affiliate');

		$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

		$subject = sprintf($this->language->get('text_subject'), $store_name);

		$data['text_welcome'] = sprintf($this->language->get('text_welcome'), $store_name);

		$this->load->model('account/customer_group');

		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} else {
			$customer_group_id = $args[1]['customer_group_id'];
		}

		$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

		if ($customer_group_info) {
			$data['approval'] = ($this->config->get('config_affiliate_approval') || $customer_group_info['approval']);
		} else {
			$data['approval'] = '';
		}

		$data['login'] = $this->url->link('affiliate/login', '', true);

		$data['store'] = $store_name;
		$data['store_url'] = $this->config->get('config_url');

		$mail = new \Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		if ($this->customer->isLogged()) {
			$mail->setTo($this->customer->getEmail());
		} else {
			$mail->setTo($args[1]['email']);
		}

		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender($store_name);
		$mail->setSubject($subject);
		$mail->setHtml($this->load->view('mail/affiliate', $data));
		$mail->send();
 	}
	
	// catalog/model/account/customer/addAffiliate/after
	public function alert(&$route, &$args, &$output) {
		// Send to main admin email if new affiliate email is enabled
		if (in_array('affiliate', (array)$this->config->get('config_mail_alert'))) {
			$this->load->language('mail/affiliate');

			$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

			$subject = $this->language->get('text_new_affiliate');

			if ($this->customer->isLogged()) {
				$customer_group_id = $this->customer->getGroupId();

				$data['firstname'] = $this->customer->getFirstName();
				$data['lastname'] = $this->customer->getLastName();
				$data['email'] = $this->customer->getEmail();
				$data['telephone'] = $this->customer->getTelephone();
			} else {
				$customer_group_id = $args[1]['customer_group_id'];

				$data['firstname'] = $args[1]['firstname'];
				$data['lastname'] = $args[1]['lastname'];
				$data['email'] = $args[1]['email'];
				$data['telephone'] = $args[1]['telephone'];
			}

			$data['website'] = html_entity_decode($args[1]['website'], ENT_QUOTES, 'UTF-8');
			$data['company'] = $args[1]['company'];

			$this->load->model('account/customer_group');

			$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

			if ($customer_group_info) {
				$data['customer_group'] = $customer_group_info['name'];
			} else {
				$data['customer_group'] = '';
			}

			$data['store'] = $store_name;
			$data['store_url'] = $this->config->get('config_url');

			$mail = new \Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender($store_name);
			$mail->setSubject($subject);
			$mail->setHtml($this->load->view('mail/affiliate_alert', $data));
			$mail->send();

			// Send to additional alert emails if new affiliate email is enabled
			$emails = explode(',', $this->config->get('config_mail_alert_email'));

			foreach ($emails as $email) {
				if (utf8_strlen($email) > 0 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$mail->setTo(trim($email));
					$mail->send();
				}
			}
		}
	}
}		