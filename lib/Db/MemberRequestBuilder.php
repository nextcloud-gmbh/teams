<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Circles\Db;


use daita\MySmallPhpTools\Exceptions\RowNotFoundException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Model\Member;


/**
 * Class MemberRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class MemberRequestBuilder extends CoreRequestBuilder {


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getMemberInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_MEMBER)
		   ->setValue('joined', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getMemberUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_MEMBER);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getMemberSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->select(
			'm.user_id', 'm.instance', 'm.user_type', 'm.circle_id', 'm.level', 'm.status', 'm.note',
			'm.contact_id', 'm.member_id', 'm.cached_name', 'm.cached_update', 'm.contact_meta', 'm.joined'
		)
		   ->from(self::TABLE_MEMBER, 'm')
		   ->orderBy('m.joined')
		   ->setDefaultSelectAlias('m');

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder
	 */
	protected function getMemberDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_MEMBER);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Member
	 * @throws MemberNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): Member {
		/** @var Member $member */
		try {
			$member = $qb->asItem(
				Member::class,
				[
					'local' => $this->configService->getLocalInstance()
				]
			);
		} catch (RowNotFoundException $e) {
			throw new MemberNotFoundException();
		}

		return $member;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Member[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var Member[] $result */
		return $qb->asItems(
			Member::class,
			[
				'local' => $this->configService->getLocalInstance()
			]
		);
	}

}
