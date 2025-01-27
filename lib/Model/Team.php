<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Circles\Model;

use JsonSerializable;
use OCA\Circles\Exceptions\TeamOwnerNotFoundException;
use OCP\AppFramework\Db\Entity;

/**
 * @method void setSingleId(string $singleId)
 * @method string getSingleId()
 * @method void setDisplayName(string $displayName)
 * @method string getDisplayName()
 * @method void setSanitizedName(string $sanitizedName)
 * @method string getSanitizedName()
 * @method void setConfig(int $config)
 * @method int getConfig()
 * @method ?array getSettings()
 * @method void setSettings(array $settings)
 * @method ?array getMetadata()
 * @method void setMetadata(array $metadata)
 * @method int getCreation()
 * @method void setCreation(int $creation)
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Team extends Entity implements JsonSerializable {
	protected string $singleId = '';
	protected string $displayName = '';
	protected string $sanitizedName = '';
	protected int $config = 0;
	protected array $settings = [];
	protected array $metadata = [];
	protected int $creation = 0;

	private ?TeamMember $owner = null;

	public function __construct(
	) {
		$this->addType('single_id', 'string');
		$this->addType('display_name', 'string');
		$this->addType('sanitized_name', 'string');
		$this->addType('config', 'integer');
		$this->addType('settings', 'json');
		$this->addType('metadata', 'json');
		$this->addType('creation', 'integer');
	}

	public function getOwner(): TeamMember {
		if ($this->owner === null) {
			$owner = new TeamMember();
			$owner->import($this->metadata['_owner'] ?? []);
			if ($owner->isValid()) {
				$this->owner = $owner;
			}
		}

		if ($this->owner === null) {
			throw new TeamOwnerNotFoundException();
		}

		return $this->owner;
	}

	public function setOwner(TeamMember $owner): void {
		$this->metadata['_owner'] = $owner->jsonSerialize();
	}


//	public function setMetaValue(string $key, string|int|float|bool|array $value): void {
//		$this->metadata[$key] = $value;
//		$this->setter('metadata', [$this->metadata]);
//	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'singleId' => $this->getSingleId(),
			'displayName' => $this->getDisplayName(),
			'sanitizedName' => $this->getSanitizedName(),
			'config' => $this->getConfig(),
			'settings' => $this->getSettings(),
			'metadata' => $this->getMetadata(),
			'creation' => $this->getCreation(),
			'owner' => $this->metadata['_owner'] ?? []
		];
	}
}
