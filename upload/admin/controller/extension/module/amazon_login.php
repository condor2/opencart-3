<?php
/**
 * Class Amazon Login
 *
 * @package Admin\Controller\Extension\Module
 */
class ControllerExtensionModuleAmazonLogin extends Controller {
	/**
	 * @var string
	 * @var array<string, string>
	 */
	private string $version = '3.2.1';
	private array $error = [];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/module/amazon_login');

		// Layouts
		$this->load->model('design/layout');

		// Settings
		$this->load->model('setting/setting');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_amazon_login', $this->request->post);

			// Events
			$this->load->model('setting/event');

			$this->model_setting_event->deleteEventByCode('amazon_login');
			$this->model_setting_event->addEvent('amazon_login', 'catalog/controller/account/logout/after', 'extension/module/amazon_login/logout');

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['heading_title'] = $this->language->get('heading_title') . ' ' . $this->version;

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/amazon_login', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['action'] = $this->url->link('extension/module/amazon_login', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->post['module_amazon_login_button_type'])) {
			$data['module_amazon_login_button_type'] = $this->request->post['module_amazon_login_button_type'];
		} elseif ($this->config->get('module_amazon_login_button_type')) {
			$data['module_amazon_login_button_type'] = $this->config->get('module_amazon_login_button_type');
		} else {
			$data['module_amazon_login_button_type'] = 'LwA';
		}

		if (isset($this->request->post['module_amazon_login_button_colour'])) {
			$data['module_amazon_login_button_colour'] = $this->request->post['module_amazon_login_button_colour'];
		} elseif ($this->config->get('module_amazon_login_button_colour')) {
			$data['module_amazon_login_button_colour'] = $this->config->get('module_amazon_login_button_colour');
		} else {
			$data['module_amazon_login_button_colour'] = 'gold';
		}

		if (isset($this->request->post['module_amazon_login_button_size'])) {
			$data['module_amazon_login_button_size'] = $this->request->post['module_amazon_login_button_size'];
		} elseif ($this->config->get('module_amazon_login_button_size')) {
			$data['module_amazon_login_button_size'] = $this->config->get('module_amazon_login_button_size');
		} else {
			$data['module_amazon_login_button_size'] = 'medium';
		}

		if (isset($this->request->post['module_amazon_login_status'])) {
			$data['module_amazon_login_status'] = $this->request->post['module_amazon_login_status'];
		} else {
			$data['module_amazon_login_status'] = $this->config->get('module_amazon_login_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/amazon_login', $data));
	}

	/**
	 * Validate
	 *
	 * @return bool
	 */
	protected function validate(): bool {
		if (!$this->user->hasPermission('modify', 'extension/module/amazon_login')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		// Events
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('amazon_login');
		$this->model_setting_event->addEvent('amazon_login', 'catalog/controller/account/logout/after', 'extension/module/amazon_login/logout');
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		// Events
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('amazon_login');
	}
}
