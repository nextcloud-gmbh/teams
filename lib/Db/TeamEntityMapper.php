<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Db;

use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Model\Signatory;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Model\TeamMember;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<TeamEntity>
 */
class TeamEntityMapper extends QBMapper {
	public const TABLE = 'teams_entities';

	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, self::TABLE, TeamEntity::class);
	}

	public function getBySingleId(string $singleId) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('single_id', $qb->createNamedParameter($singleId)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new TeamEntityNotFoundException('no team entity found');
		}
	}

	public function getByOrigId(TeamEntityType $type, string $origId) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('single_id', $qb->createNamedParameter($type->value)))
		   ->where($qb->expr()->eq('orig_id', $qb->createNamedParameter($origId)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new TeamEntityNotFoundException('no team entity found');
		}
	}
}
