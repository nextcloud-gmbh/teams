<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

use OC\Teams\TeamManager;
use OCA\Circles\Api\v2\ITeamOperation;
use OCA\Circles\Db\TeamMapper;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Service\TeamService;

class TeamOperation implements ITeamOperation {
	private ?TeamEntity $entity = null;

	public function __construct(
		private TeamService $teamService,
	) {
	}

	public function asEntity(TeamEntity $entity): void {
		if ($this->entity !== null) {
			throw new \Exception('cannot overwrite initiator'); // TODO specific Exception
		}
		$this->entity = $entity;
	}


	public function getTeams(): array {
	//	$this->teamMapper->get
	}

	public function getAvailableTeams(): array {
	}


}
