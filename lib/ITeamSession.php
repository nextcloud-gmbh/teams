<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles;

use OCA\Circles\Api\v2\ITeamMemberOperation;
use OCA\Circles\Api\v2\ITeamOperation;
use OCA\Circles\Model\TeamEntity;
use OCP\IUser;

interface ITeamSession {
	public function asCurrentUser(): self;
	public function asUser(IUser $user): self;
	public function asLocalUser(string $userId): self;
	public function asApp(string $appId): self;
	public function asSuperAdmin(): self;
	public function asEntity(TeamEntity $entity): self;
	public function getEntity(): ?TeamEntity;
	public function performTeamOperation(): ITeamOperation;
	public function performTeamMemberOperation(): ITeamMemberOperation;
}
