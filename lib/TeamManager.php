<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles;

use OCA\Circles\Db\TeamEntityMapper;
use OCA\Circles\Db\TeamMapper;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Team;
use OCA\Circles\Model\TeamEntity;

class TeamManager {
	public function __construct(
		private TeamMapper $teamMapper,
	) {
	}

	public function getTeam(string $singleId): Team {
		return $this->teamMapper->getBySingleId($singleId);
	}

	public function confirmNaming(Team $team): void {
		if ($team->isConfig(Circle::CFG_SYSTEM)
			|| $team->isConfig(Circle::CFG_SINGLE)) {
			return;
		}

//		$this->confirmDisplayName($circle);
//		$this->generateSanitizedName($circle);
	}

//	public function createTeam(
//		TeamEntityType $type,
//		string $origId,
//		string $displayName,
//	): TeamEntity {
//		$teamEntity = new TeamEntity();
//		$teamEntity->setTeamEntityType($type);
//		$teamEntity->setOrigId($origId);
//		$teamEntity->setDisplayName($displayName);
//		$this->teamEntityMapper->insert($teamEntity);
//
//		return $teamEntity;
//	}
}
