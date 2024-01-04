<?php
/**
 * Class Error
 *
 * @package Admin\Controller\Startup
 */
class ControllerStartupError extends Controller {
	/**
	 * @return void
	 */
	public function index(): void {
		// Log
		$this->registry->set('log', new \Log($this->config->get('config_error_filename') ?: $this->config->get('error_filename')));

		set_error_handler([
			$this,
			'error'
		]);
		set_exception_handler([
			$this,
			'exception'
		]);
	}

	/**
	 * Error
	 *
	 * @param string $code
	 * @param string $message
	 * @param string $file
	 * @param string $line
	 *
	 * @return bool
	 */
	public function error(string $code, string $message, string $file, string $line): bool {
		// error suppressed with @
		if (error_reporting() === 0) {
			return false;
		}

		switch ($code) {
			case E_NOTICE:
			case E_USER_NOTICE:
				$error = 'Notice';
				break;
			case E_WARNING:
			case E_USER_WARNING:
				$error = 'Warning';
				break;
			case E_ERROR:
			case E_USER_ERROR:
				$error = 'Fatal Error';
				break;
			default:
				$error = 'Unknown';
				break;
		}

		if ($this->config->get('config_error_log')) {
			$sting = 'PHP ' . $error . ': ' . $message . "\n";
			$sting .= 'File: ' . $file . "\n";
			$sting .= 'Line: ' . $line . "\n";

			$this->log->write($sting);
		}

		if ($this->config->get('config_error_display')) {
			echo '<b>' . $error . '</b>: ' . $message . ' in <b>' . $file . '</b> on line <b>' . $line . '</b>';
		} else {
			header('Location: ' . $this->config->get('error_page'));
			exit();
		}

		return true;
	}

	/**
	 * Exception
	 *
	 * @param \Throwable $e
	 *
	 * @return void
	 */
	public function exception(\Throwable $e): void {
		if ($this->config->get('config_error_log')) {
			$sting = $e::class . ':  ' . $e->getMessage() . "\n";
			$sting .= 'File: ' . $e->getFile() . "\n";
			$sting .= 'Line: ' . $e->getLine() . "\n";

			$this->log->write($sting);
		}

		if ($this->config->get('config_error_display')) {
			echo '<b>' . $e::class . '</b>: ' . $e->getMessage() . ' in <b>' . $e->getFile() . '</b> on line <b>' . $e->getLine() . '</b>';
		} else {
			header('Location: ' . $this->config->get('error_page'));
			exit();
		}
	}
}
