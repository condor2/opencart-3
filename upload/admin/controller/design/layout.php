<?php
/**
 * Class Layout
 *
 * @package Admin\Controller\Design
 */
class ControllerDesignLayout extends Controller {
	/**
	 * @var array<string, string>
	 */
	private array $error = [];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('design/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		// Layouts
		$this->load->model('design/layout');

		$this->getList();
	}

	/**
	 * Add
	 *
	 * @return void
	 */
	public function add(): void {
		$this->load->language('design/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		// Layouts
		$this->load->model('design/layout');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_design_layout->addLayout($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	/**
	 * Edit
	 *
	 * @return void
	 */
	public function edit(): void {
		$this->load->language('design/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		// Layouts
		$this->load->model('design/layout');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_design_layout->editLayout($this->request->get['layout_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	/**
	 * Delete
	 *
	 * @return void
	 */
	public function delete(): void {
		$this->load->language('design/layout');

		$this->document->setTitle($this->language->get('heading_title'));

		// Layouts
		$this->load->model('design/layout');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ((array)$this->request->post['selected'] as $layout_id) {
				$this->model_design_layout->deleteLayout($layout_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	/**
	 * Get List
	 *
	 * @return void
	 */
	protected function getList(): void {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'] . $url, true)
		];

		$data['add'] = $this->url->link('design/layout/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('design/layout/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['layouts'] = [];

		$filter_data = [
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		];

		$layout_total = $this->model_design_layout->getTotalLayouts();

		$results = $this->model_design_layout->getLayouts($filter_data);

		foreach ($results as $result) {
			$data['layouts'][] = [
				'layout_id' => $result['layout_id'],
				'name'      => $result['name'],
				'edit'      => $this->url->link('design/layout/edit', 'user_token=' . $this->session->data['user_token'] . '&layout_id=' . $result['layout_id'] . $url, true)
			];
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = [];
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new \Pagination();
		$pagination->total = $layout_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($layout_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($layout_total - $this->config->get('config_limit_admin'))) ? $layout_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $layout_total, ceil($layout_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('design/layout_list', $data));
	}

	/**
	 * Get Form
	 *
	 * @return void
	 */
	protected function getForm(): void {
		$data['text_form'] = !isset($this->request->get['layout_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'] . $url, true)
		];

		if (!isset($this->request->get['layout_id'])) {
			$data['action'] = $this->url->link('design/layout/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('design/layout/edit', 'user_token=' . $this->session->data['user_token'] . '&layout_id=' . $this->request->get['layout_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->get['layout_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$layout_info = $this->model_design_layout->getLayout($this->request->get['layout_id']);
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($layout_info)) {
			$data['name'] = $layout_info['name'];
		} else {
			$data['name'] = '';
		}

		// Stores
		$this->load->model('setting/store');

		$data['stores'] = $this->model_setting_store->getStores();

		if (isset($this->request->post['layout_route'])) {
			$data['layout_routes'] = $this->request->post['layout_route'];
		} elseif (isset($this->request->get['layout_id'])) {
			$data['layout_routes'] = $this->model_design_layout->getRoutes($this->request->get['layout_id']);
		} else {
			$data['layout_routes'] = [];
		}

		// Modules
		$this->load->model('setting/module');

		// Extensions
		$this->load->model('setting/extension');

		$data['extensions'] = [];

		// Get a list of installed modules
		$extensions = $this->model_setting_extension->getExtensionsByType('module');

		// Add all the modules which have multiple settings for each module
		foreach ($extensions as $code) {
			$this->load->language('extension/module/' . $code, 'extension');

			$module_data = [];

			$modules = $this->model_setting_module->getModulesByCode($code);

			foreach ($modules as $module) {
				$module_data[] = [
					'name' => strip_tags($module['name']),
					'code' => $code . '.' . $module['module_id']
				];
			}

			if ($this->config->has('module_' . $code . '_status') || $module_data) {
				$data['extensions'][] = [
					'name'   => strip_tags($this->language->get('extension')->get('heading_title')),
					'code'   => $code,
					'module' => $module_data
				];
			}
		}

		// Modules layout
		if (isset($this->request->post['layout_module'])) {
			$layout_modules = $this->request->post['layout_module'];
		} elseif (isset($this->request->get['layout_id'])) {
			$layout_modules = $this->model_design_layout->getModules($this->request->get['layout_id']);
		} else {
			$layout_modules = [];
		}

		$data['layout_modules'] = [];

		// Add all the modules which have multiple settings for each module
		foreach ($layout_modules as $layout_module) {
			$part = explode('.', $layout_module['code']);

			if (!isset($part[1])) {
				$data['layout_modules'][] = [
					'code'       => $layout_module['code'],
					'edit'       => $this->url->link('extension/module/' . $part[0], 'user_token=' . $this->session->data['user_token'], true),
					'position'   => $layout_module['position'],
					'sort_order' => $layout_module['sort_order']
				];
			} else {
				$module_info = $this->model_setting_module->getModule($part[1]);

				if ($module_info) {
					$data['layout_modules'][] = [
						'code'       => $layout_module['code'],
						'edit'       => $this->url->link('extension/module/' . $part[0], 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $part[1], true),
						'position'   => $layout_module['position'],
						'sort_order' => $layout_module['sort_order']
					];
				}
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('design/layout_form', $data));
	}

	/**
	 * Validate Form
	 *
	 * @return bool
	 */
	protected function validateForm(): bool {
		if (!$this->user->hasPermission('modify', 'design/layout')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((oc_strlen($this->request->post['name']) < 3) || (oc_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		return !$this->error;
	}

	/**
	 * Validate Delete
	 *
	 * @return bool
	 */
	protected function validateDelete(): bool {
		if (!$this->user->hasPermission('modify', 'design/layout')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// Stores
		$this->load->model('setting/store');

		// Products
		$this->load->model('catalog/product');

		// Categories
		$this->load->model('catalog/category');

		// Information
		$this->load->model('catalog/information');

		foreach ((array)$this->request->post['selected'] as $layout_id) {
			if ($this->config->get('config_layout_id') == $layout_id) {
				$this->error['warning'] = $this->language->get('error_default');
			}

			$store_total = $this->model_setting_store->getTotalStoresByLayoutId($layout_id);

			if ($store_total) {
				$this->error['warning'] = sprintf($this->language->get('error_store'), $store_total);
			}

			$product_total = $this->model_catalog_product->getTotalProductsByLayoutId($layout_id);

			if ($product_total) {
				$this->error['warning'] = sprintf($this->language->get('error_product'), $product_total);
			}

			$category_total = $this->model_catalog_category->getTotalCategoriesByLayoutId($layout_id);

			if ($category_total) {
				$this->error['warning'] = sprintf($this->language->get('error_category'), $category_total);
			}

			$information_total = $this->model_catalog_information->getTotalInformationsByLayoutId($layout_id);

			if ($information_total) {
				$this->error['warning'] = sprintf($this->language->get('error_information'), $information_total);
			}
		}

		return !$this->error;
	}
}
