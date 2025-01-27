<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2;

interface ITeamMemberOperation {
	public function getTeamMembers(string $teamSingleId): array;
	public function getMemberships(string $teamSingleId): array;
}
