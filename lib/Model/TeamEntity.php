<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Circles\Model;

use JsonSerializable;
use NCU\Security\Signature\Enum\SignatoryStatus;
use NCU\Security\Signature\Enum\SignatoryType;
use OCA\Circles\Db\TeamEntityMapper;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Enum\TeamMemberLevel;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getSingleId()
 * @method void setSingleId(string $teamSingleId)
 * @method int getType()
 * @method void setType(int $type)
 * @method string getOrigId()
 * @method void setOrigId(string $oridId)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TeamEntity extends Entity implements JsonSerializable {
	protected string $singleId = '';
	protected int $type = 0;
	protected string $origId = '';
	protected string $displayName = '';

	public function __construct(
		private ?TeamEntityMapper $teamEntityMapper = null,
	) {
		$this->addType('single_id', 'string');
		$this->addType('type', 'integer');
		$this->addType('orig_id', 'string');
		$this->addType('display_name', 'string');
	}

	public function getTeamEntityType(): TeamEntityType {
		return TeamEntityType::from($this->getType());
	}

	public function setTeamEntityType(TeamEntityType $level): void {
		$this->setType($level->value);
	}

	public function getDisplayName(): string {
		/*
		 * Lazy loading of the display name can be done if TeamEntityMapper is provided at the creation of the object
		 */
		if ($this->displayName === '' && $this->teamEntityMapper !== null && $this->singleId !== '') {
			try {
				$lazyTeamEntity = $this->teamEntityMapper->getBySingleId($this->singleId);
				$this->displayName = $lazyTeamEntity->getDisplayName();
			} catch (TeamEntityNotFoundException) {
			}
		}

		return $this->displayName;
	}

	public function import(array $data): void {
		$this->setSingleId($data['singleId']);
		$this->setType($data['type']);
		$this->setOrigId($data['origId']);
		$this->setDisplayName($data['displayName']);
	}

	public function isValid(): bool {
		return true; // TODO
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'singleId' => $this->getSingleId(),
			'type' => $this->getType(),
			'origId' => $this->getOrigId(),
			'displayName' => $this->getDisplayName(),
		];
	}
}
