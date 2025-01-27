<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Model\Signatory;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Exceptions\TeamNotFoundException;
use OCA\Circles\Model\Team;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Team>
 */
class TeamMapper extends QBMapper {
	public const TABLE = 'teams';

	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, self::TABLE, Team::class);
	}

	public function getBySingleId(string $singleId): Team {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('single_id', $qb->createNamedParameter($singleId)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new TeamNotFoundException('no team found');
		}
	}

}
