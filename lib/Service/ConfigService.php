<?php
/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Circles\Service;

use OCA\Circles\Model\Circle;
use OCP\IConfig;
use OCP\Util;

class ConfigService {

	const CIRCLES_ALLOW_CIRCLES = 'allow_circles';
	const CIRCLES_SWAP_TO_TEAMS = 'swap_to_teams';
	const CIRCLES_ALLOW_FEDERATED = 'allow_federated';

	private $defaults = [
		self::CIRCLES_ALLOW_CIRCLES   => Circle::CIRCLES_ALL,
		self::CIRCLES_SWAP_TO_TEAMS   => '0',
		self::CIRCLES_ALLOW_FEDERATED => '0'
	];

	/** @var string */
	private $appName;

	/** @var IConfig */
	private $config;

	/** @var string */
	private $userId;

	/** @var MiscService */
	private $miscService;

	/** @var int */
	private $allowedCircle = -1;

	/** @var int */
	private $allowedFederated = -1;

	public function __construct($appName, IConfig $config, $userId, MiscService $miscService) {
		$this->appName = $appName;
		$this->config = $config;
		$this->userId = $userId;
		$this->miscService = $miscService;
	}


	/**
	 * returns if this type of circle is allowed by the current configuration.
	 *
	 * @param $type
	 *
	 * @return int
	 */
	public function isCircleAllowed($type) {
		if ($this->allowedCircle === -1) {
			$this->allowedCircle = (int)$this->getAppValue(self::CIRCLES_ALLOW_CIRCLES);
		}

		return ((int)$type & (int)$this->allowedCircle);
	}


	public function isFederatedAllowed() {
		if ($this->allowedFederated === -1) {
			$this->allowedFederated = (int)$this->getAppValue(self::CIRCLES_ALLOW_FEDERATED);
		}

		return ($this->allowedFederated === 1);
	}

	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValue($key) {
		$defaultValue = null;

		if (array_key_exists($key, $this->defaults)) {
			$defaultValue = $this->defaults[$key];
		}

		return $this->config->getAppValue($this->appName, $key, $defaultValue);
	}

	/**
	 * Set a value by key
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 */
	public function setAppValue($key, $value) {
		$this->config->setAppValue($this->appName, $key, $value);
	}

	/**
	 * remove a key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function deleteAppValue($key) {
		return $this->config->deleteAppValue($this->appName, $key);
	}

	/**
	 * Get a user value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getUserValue($key) {
		return $this->config->getUserValue($this->userId, $this->appName, $key);
	}

	/**
	 * Set a user value by key
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function setUserValue($key, $value) {
		return $this->config->setUserValue($this->userId, $this->appName, $key, $value);
	}

	/**
	 * Get a user value by key and user
	 *
	 * @param string $userId
	 * @param string $key
	 *
	 * @return string
	 */
	public function getValueForUser($userId, $key) {
		return $this->config->getUserValue($userId, $this->appName, $key);
	}

	/**
	 * Set a user value by key
	 *
	 * @param string $userId
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function setValueForUser($userId, $key, $value) {
		return $this->config->setUserValue($userId, $this->appName, $key, $value);
	}

	/**
	 * return the cloud version.
	 * if $complete is true, return a string x.y.z
	 *
	 * @param boolean $complete
	 *
	 * @return string|integer
	 */
	public function getCloudVersion($complete = false) {
		$ver = Util::getVersion();

		if ($complete) {
			return implode('.', $ver);
		}

		return $ver[0];
	}
}
