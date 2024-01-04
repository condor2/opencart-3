<?php
namespace Session;
/**
 * Class DB
 *
 * @package System\Library\Session
 */
class DB {
	private object $db;
	private object $config;
	public int $maxlifetime;

	/**
	 * Constructor
	 *
	 * @param object $registry
	 */
	public function __construct(object $registry) {
		$this->db = $registry->get('db');
		$this->config = $registry->get('config');

		$this->maxlifetime = ini_get('session.gc_maxlifetime') !== null ? (int)ini_get('session.gc_maxlifetime') : 1440;

		$this->gc();
	}

	/**
	 * Read
	 *
	 * @param string $session_id
	 *
	 * @return array
	 */
	public function read(string $session_id): array {
		$query = $this->db->query("SELECT `data` FROM `" . DB_PREFIX . "session` WHERE `session_id` = '" . $this->db->escape($session_id) . "' AND `expire` > '" . $this->db->escape(date('Y-m-d H:i:s', time())) . "'");

		if ($query->num_rows) {
			return (array)json_decode($query->row['data'], true);
		} else {
			return [];
		}
	}

	/**
	 * Write
	 *
	 * @param string $session_id
	 * @param array  $data
	 *
	 * @return bool
	 */
	public function write(string $session_id, array $data): bool {
		if ($session_id) {
			$this->db->query("REPLACE INTO `" . DB_PREFIX . "session` SET `session_id` = '" . $this->db->escape($session_id) . "', `data` = '" . $this->db->escape(json_encode($data)) . "', `expire` = '" . $this->db->escape(date('Y-m-d H:i:s', time() + (int)$this->maxlifetime)) . "'");
		}

		return true;
	}

	/**
	 * Destroy
	 *
	 * @param string $session_id
	 *
	 * @return bool
	 */
	public function destroy(string $session_id): bool {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE `session_id` = '" . $this->db->escape($session_id) . "'");

		return true;
	}

	/**
	 * GC
	 *
	 * @return bool
	 */
	public function gc(): bool {
		if (round(mt_rand(1, $this->config->get('session_divisor') / $this->config->get('session_probability'))) == 1) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE `expire` < '" . $this->db->escape(gmdate('Y-m-d H:i:s', time())) . "'");
		}

		return true;
	}
}
