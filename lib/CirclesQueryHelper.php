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


namespace OCA\Circles;

use OCA\Circles\Db\CoreQueryBuilder;
use OCA\Circles\Db\CoreRequestBuilder;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Service\FederatedUserService;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class CirclesQueryHelper
 *
 * @package OCA\Circles
 */
class CirclesQueryHelper {


	/** @var CoreRequestBuilder */
	private $coreRequestBuilder;

	/** @var CoreQueryBuilder */
	private $queryBuilder;

	/** @var FederatedUserService */
	private $federatedUserService;


	/**
	 * CirclesQueryHelper constructor.
	 *
	 * @param CoreRequestBuilder $coreRequestBuilder
	 * @param FederatedUserService $federatedUserService
	 */
	public function __construct(
		CoreRequestBuilder $coreRequestBuilder,
		FederatedUserService $federatedUserService
	) {
		$this->coreRequestBuilder = $coreRequestBuilder;
		$this->federatedUserService = $federatedUserService;
	}


	/**
	 * @return IQueryBuilder
	 */
	public function getQueryBuilder(): IQueryBuilder {
		$this->queryBuilder = $this->coreRequestBuilder->getQueryBuilder();

		return $this->queryBuilder;
	}


	/**
	 * @param array $fields
	 * @param bool $fullDetails
	 *
	 * @return ICompositeExpression
	 * @throws FederatedUserNotFoundException
	 * @throws RequestBuilderException
	 */
	public function limitToSession(
		array $fields,
		bool $fullDetails = false
	): ICompositeExpression {
		$session = $this->federatedUserService->getCurrentUser();
		if (is_null($session)) {
			throw new FederatedUserNotFoundException('session not initiated');
		}

//		$this->queryBuilder->setDefaultSelectAlias($alias);
		$this->queryBuilder->setOptions(
			[CoreQueryBuilder::HELPER],
			[
				'getData' => $fullDetails,
				'minimumLevel' => Member::LEVEL_MEMBER
			]
		);

		return $this->queryBuilder->limitToInitiator(
			CoreQueryBuilder::HELPER,
			$session,
			'',
			$fields
		);
	}


	/**
	 * @param string $alias
	 * @param string $field
	 * @param IFederatedUser $federatedUser
	 * @param bool $fullDetails
	 * @param array $extraFields
	 *
	 * @return ICompositeExpression
	 * @throws RequestBuilderException
	 */
	public function limitToInheritedMembers(
		string $field,
		string $alias,
		IFederatedUser $federatedUser,
		bool $fullDetails = false,
		array $extraFields = []
	): ICompositeExpression {
		$this->queryBuilder->setOptions(
			[CoreQueryBuilder::HELPER],
			[
				'getData' => $fullDetails,
				'minimumLevel' => Member::LEVEL_MEMBER
			]
		);

		return $this->queryBuilder->limitToInitiator(
			CoreQueryBuilder::HELPER,
			$federatedUser,
			$field,
			$alias,
			$extraFields
		);
	}


	/**
	 * @param string $field
	 * @param string $alias
	 *
	 * @throws RequestBuilderException
	 */
	public function addCircleDetails(
		string $alias,
		string $field
	): void {
		$this->queryBuilder->setDefaultSelectAlias($alias);
		$this->queryBuilder->setOptions(
			[CoreQueryBuilder::HELPER],
			[
				'getData' => true
			]
		);

		$this->queryBuilder->leftJoinCircle(CoreQueryBuilder::HELPER, null, $field, $alias);
	}


	/**
	 * @param array $data
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 */
	public function extractCircle(array $data): Circle {
		$circle = new Circle();
		$circle->importFromDatabase(
			$data,
			CoreQueryBuilder::HELPER . '_' . CoreQueryBuilder::CIRCLE . '_'
		);

		return $circle;
	}
}
