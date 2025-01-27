<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Model\Signatory;
use OCA\Circles\Model\TeamMember;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<TeamMember>
 */
class TeamMemberMapper extends QBMapper {
	public const TABLE = 'teams_members';

	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, self::TABLE, TeamMember::class);
	}
}
