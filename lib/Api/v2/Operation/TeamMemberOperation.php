<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2\Operation;

use OCA\Circles\Api\v2\ITeamMemberOperation;
use OCA\Circles\Model\TeamEntity;

class TeamMemberOperation implements ITeamMemberOperation {
	private ?TeamEntity $entity = null;

	public function __construct() {
	}

	public function asEntity(TeamEntity $entity): void {
		if ($this->entity !== null) {
			throw new \Exception('cannot overwrite initiator'); // TODO specific Exception
		}
		$this->entity = $entity;
	}


	public function getTeamMembers(string $teamSingleId): array {
	}

	public function getMemberships(string $teamSingleId): array {
	}


}
