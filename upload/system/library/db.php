<?php
/**
 * @package        OpenCart
 *
 * @author         Daniel Kerr
 * @copyright      Copyright (c) 2005 - 2022, OpenCart, Ltd. (https://www.opencart.com/)
 * @license        https://opensource.org/licenses/GPL-3.0
 *
 * @see           https://www.opencart.com
 */

/**
 * DB Class
 */
class DB {
	private object $adaptor;

	/**
	 * Constructor
	 *
	 * @param string $adaptor
	 * @param string $hostname
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param string $port
	 */
	public function __construct(string $adaptor, string $hostname, string $username, string $password, string $database, string $port = '') {
		$class = 'DB\\' . $adaptor;

		if (class_exists($class)) {
			$this->adaptor = new $class($hostname, $username, $password, $database, $port);
		} else {
			throw new \Exception('Error: Could not load database adaptor ' . $adaptor . '!');
		}
	}

	/**
	 * Query
	 *
	 * @param string $sql
	 *
	 * @return mixed
	 */
	public function query(string $sql) {
		return $this->adaptor->query($sql);
	}

	/**
	 * Escape
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function escape(string $value): string {
		return $this->adaptor->escape($value);
	}

	/**
	 * countAffected
	 *
	 * Gets the total number of affected rows from the last query
	 *
	 * @return int returns the total number of affected rows
	 */
	public function countAffected(): int {
		return $this->adaptor->countAffected();
	}

	/**
	 * getLastId
	 *
	 * Get the last ID gets the primary key that was returned after creating a row in a table.
	 *
	 * @return int Returns last ID
	 */
	public function getLastId(): int {
		return $this->adaptor->getLastId();
	}

	/**
	 * isConnected
	 * Checks if a DB connection is active.
	 *
	 * @return bool
	 */
	public function isConnected(): bool {
		return $this->adaptor->isConnected();
	}
}
