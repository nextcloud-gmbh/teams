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


use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\IMember;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;


/**
 * Class CircleRequest
 *
 * @package OCA\Circles\Db
 */
class CircleRequest extends CircleRequestBuilder {


	/**
	 * @param Circle $circle
	 */
	public function save(Circle $circle): void {
		$qb = $this->getCircleInsertSql();
		$qb->setValue('unique_id', $qb->createNamedParameter($circle->getId()))
		   ->setValue('long_id', $qb->createNamedParameter($circle->getId()))
		   ->setValue('name', $qb->createNamedParameter($circle->getName()))
		   ->setValue('alt_name', $qb->createNamedParameter($circle->getAltName()))
		   ->setValue('description', $qb->createNamedParameter($circle->getDescription()))
		   ->setValue('contact_addressbook', $qb->createNamedParameter($circle->getContactAddressBook()))
		   ->setValue('contact_groupname', $qb->createNamedParameter($circle->getContactGroupName()))
		   ->setValue('settings', $qb->createNamedParameter(json_encode($circle->getSettings())))
		   ->setValue('type', $qb->createNamedParameter($circle->getType()))
		   ->setValue('config', $qb->createNamedParameter($circle->getConfig()));

		$qb->execute();
	}


	/**
	 * @param Circle $circle
	 */
	public function update(Circle $circle) {
		$qb = $this->getCircleUpdateSql();
//		$qb->set('uid', $qb->createNamedParameter($circle->getUid(true)))
//		   ->set('href', $qb->createNamedParameter($circle->getId()))
//		   ->set('item', $qb->createNamedParameter(json_encode($circle->getOrigData())));

		$qb->limitToUniqueId($circle->getId());

		$qb->execute();
	}


	/**
	 * @param Member|null $filter
	 * @param IMember|null $viewer
	 *
	 * @return Circle[]
	 */
	public function getCircles(?Member $filter = null, ?IMember $viewer = null): array {
		$qb = $this->getCircleSelectSql();
		$qb->leftJoinOwner();

		if (!is_null($viewer)) {
			$qb->limitToViewer($viewer);
		}

		if (!is_null($filter)) {
			$qb->limitToMembership($filter, $filter->getLevel());
		}

		return $this->getItemsFromRequest($qb);
	}


	/**
	 * @param string $id
	 * @param Member|null $viewer
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function getCircle(string $id, ?Member $viewer = null): Circle {
		$qb = $this->getCircleSelectSql();
		$qb->limitToUniqueId($id);
		$qb->leftJoinOwner();

		if (!is_null($viewer)) {
			$qb->limitToViewer($viewer);
		}

		return $this->getItemFromRequest($qb);
	}


	/**
	 * method that return the single-user Circle based on a Viewer.
	 *
	 * @param IMember $viewer
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function getViewerCircle(IMember $viewer): Circle {
		$qb = $this->getCircleSelectSql();
		$qb->leftJoinOwner();
		$qb->limitToMembership($viewer, Member::LEVEL_OWNER);
		$qb->limitToConfig(Circle::CFG_SINGLE);

		return $this->getItemFromRequest($qb);
	}


	/**
	 * @return Circle[]
	 */
	public function getFederated(): array {
		$qb = $this->getCircleSelectSql();
		$qb->filterConfig(Circle::CFG_FEDERATED);
		$qb->leftJoinOwner();

		return $this->getItemsFromRequest($qb);
	}


}

