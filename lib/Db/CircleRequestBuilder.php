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
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\AppService;
use OCA\Circles\Model\Circle;


/**
 * Class CircleRequestBuilder
 *
 * @package OCA\Circles\Db
 */
class CircleRequestBuilder extends CoreRequestBuilder {


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getCircleInsertSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->insert(self::TABLE_CIRCLE)
		   ->setValue('creation', $qb->createNamedParameter($this->timezoneService->getUTCDate()));

		return $qb;
	}


	/**
	 * @param string $id
	 *
	 * @return CoreQueryBuilder
	 */
	protected function getCircleUpdateSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->update(self::TABLE_CIRCLE);

		return $qb;
	}


	/**
	 * @return CoreQueryBuilder
	 */
	protected function getCircleSelectSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->selectDistinct('c.unique_id')
		   ->addSelect(
			   'c.name', 'c.alt_name', 'c.description', 'c.settings', 'c.type', 'contact_addressbook',
			   'contact_groupname', 'c.creation'
		   )
		   ->from(self::TABLE_CIRCLE, 'c')
		   ->setDefaultSelectAlias('c');

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return CoreQueryBuilder
	 */
	protected function getCircleDeleteSql(): CoreQueryBuilder {
		$qb = $this->getQueryBuilder();
		$qb->delete(self::TABLE_CIRCLE);

		return $qb;
	}


	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function getItemFromRequest(CoreQueryBuilder $qb): Circle {
		/** @var Circle $circle */
		try {
			$circle = $qb->asItem(Circle::class);
		} catch (RowNotFoundException $e) {
			throw new CircleNotFoundException();
		}
		
		return $circle;
	}

	/**
	 * @param CoreQueryBuilder $qb
	 *
	 * @return Circle[]
	 */
	public function getItemsFromRequest(CoreQueryBuilder $qb): array {
		/** @var AppService[] $result */
		return $qb->asItems(Circle::class);
	}

}
