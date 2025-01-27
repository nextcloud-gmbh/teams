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
use OCA\Circles\Enum\TeamMemberLevel;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getTeamSingleId()
 * @method void setTeamSingleId(string $teamSingleId)
 * @method string getMemberSingleId()
 * @method void setMemberSingleId(string $memberSingleId)
 * @method int getLevel()
 * @method void setLevel(int $level)
 * @method ?array getMetadata()
 * @method void setMetadata(array $metadata)
 * @method int getCreation()
 * @method void setCreation(int $creation)
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TeamMember extends Entity implements JsonSerializable {
	protected string $teamSingleId = '';
	protected string $memberSingleId = '';
	protected int $level = 0;
	protected array $metadata = [];
	protected int $creation = 0;

	public function __construct(
	) {
		$this->addType('team_single_id', 'string');
		$this->addType('member_single_id', 'string');
		$this->addType('level', 'integer');
		$this->addType('metadata', 'json');
		$this->addType('creation', 'integer');
	}

//	public function setMetaValue(string $key, string|int|float|bool|array $value): void {
//		$this->metadata[$key] = $value;
//		$this->setter('metadata', [$this->metadata]);
//	}

	public function getTeamMemberLevel(): TeamMemberLevel {
		return TeamMemberLevel::from($this->getLevel());
	}

	public function setTeamMemberLevel(TeamMemberLevel $level): void {
		$this->setLevel($level->value);
	}

	public function getInvitedBy(): TeamEntity {
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


	public function import(array $data): void {
		$this->setTeamSingleId($data['teamSingleId'] ?? '');
		$this->setMemberSingleId($data['memberSingleId'] ?? '');
		$this->setLevel($data['level'] = 0);
		$this->setMetadata($data['metadata'] ?? []);
		$this->setCreation($data['creation'] ?? 0);
	}

	public function isValid(): bool {
		return ($this->getTeamSingleId() !== '' && $this->getMemberSingleId() !== '');
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'teamSingleId' => $this->getTeamSingleId(),
			'memberSingleId' => $this->getMemberSingleId(),
			'level' => $this->getLevel(),
			'metadata' => $this->getMetadata(),
			'creation' => $this->getCreation(),
			'invitedBy' => $this->metadata['_invitedBy'] ?? [],
		];
	}
}
