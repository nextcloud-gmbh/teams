<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles;

use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Model\TeamEntity;

interface ITeamEntityManager {
	// do we want an interface ? the only public part should be made available through TeamSession/Operation
//	public function getTeamEntity(string $singleId): TeamEntity;
//	public function searchTeamEntity(TeamEntityType $type, string $origId): TeamEntity;
//	public function createTeamEntity(TeamEntityType $type, string $origId, string $displayName): TeamEntity;
}
