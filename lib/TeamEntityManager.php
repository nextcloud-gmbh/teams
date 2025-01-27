<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles;

use OCA\Circles\Db\TeamEntityMapper;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Model\TeamEntity;

class TeamEntityManager implements ITeamEntityManager {

	public function __construct(
		private TeamEntityMapper $teamEntityMapper,
	) {
	}


	public function getTeamEntity(string $singleId): TeamEntity {
		return $this->teamEntityMapper->getBySingleId($singleId);
	}

	public function searchTeamEntity(TeamEntityType $type, string $origId): TeamEntity {
		return $this->teamEntityMapper->getByOrigId($type, $origId);
	}

	public function createTeamEntity(
		TeamEntityType $type,
		string $origId,
		string $displayName,
	): TeamEntity {
		$teamEntity = new TeamEntity();
		$teamEntity->setTeamEntityType($type);
		$teamEntity->setOrigId($origId);
		$teamEntity->setDisplayName($displayName);
		$this->teamEntityMapper->insert($teamEntity);

		return $teamEntity;
	}
}
