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


namespace OCA\Circles\Service;


use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\TArrayTools;
use daita\MySmallPhpTools\Traits\TStringTools;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\MembersLimitException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\CircleConfig;
use OCA\Circles\FederatedItems\CircleCreate;
use OCA\Circles\FederatedItems\CircleJoin;
use OCA\Circles\FederatedItems\CircleLeave;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\StatusCode;


/**
 * Class CircleService
 *
 * @package OCA\Circles\Service
 */
class CircleService {


	use TArrayTools;
	use TStringTools;


	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var RemoteStreamService */
	private $remoteStreamService;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var MemberService */
	private $memberService;

	/** @var ConfigService */
	private $configService;


	/**
	 * CircleService constructor.
	 *
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param RemoteStreamService $remoteStreamService
	 * @param FederatedUserService $federatedUserService
	 * @param FederatedEventService $federatedEventService
	 * @param MemberService $memberService
	 * @param ConfigService $configService
	 */
	public function __construct(
		CircleRequest $circleRequest, MemberRequest $memberRequest, RemoteStreamService $remoteStreamService,
		FederatedUserService $federatedUserService, FederatedEventService $federatedEventService,
		MemberService $memberService, ConfigService $configService
	) {
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->remoteStreamService = $remoteStreamService;
		$this->federatedUserService = $federatedUserService;
		$this->federatedEventService = $federatedEventService;
		$this->memberService = $memberService;
		$this->configService = $configService;
	}


	/**
	 * @param string $name
	 * @param FederatedUser|null $owner
	 * @param bool $personal
	 *
	 * @return array
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RemoteInstanceException
	 */
	public function create(
		string $name,
		?FederatedUser $owner = null,
		bool $personal = false
	): array {

		$this->federatedUserService->mustHaveCurrentUser();
		if (is_null($owner)) {
			$owner = $this->federatedUserService->getCurrentUser();
		}

		$circle = new Circle();
		$circle->setName($name);
		$circle->setId($this->token(ManagedModel::ID_LENGTH));
		if ($personal) {
			$circle->setConfig(Circle::CFG_SINGLE);
		}

		$member = new Member();
		$member->importFromIFederatedUser($owner);
		$member->setId($this->token(ManagedModel::ID_LENGTH))
			   ->setCircleId($circle->getId())
			   ->setLevel(Member::LEVEL_OWNER)
			   ->setStatus(Member::STATUS_MEMBER);
		$circle->setOwner($member)
			   ->setInitiator($member);

		$event = new FederatedEvent(CircleCreate::class);
		$event->setCircle($circle);
		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $circleId
	 * @param int $config
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function updateConfig(string $circleId, int $config): array {
		$circle = $this->getCircle($circleId);

		$event = new FederatedEvent(CircleConfig::class);
		$event->setCircle($circle);
		$event->setData(new SimpleDataStore(['config' => $config]));

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $circleId
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function circleJoin(string $circleId): array {
		$this->federatedUserService->mustHaveCurrentUser();

		$circle = $this->circleRequest->getCircle($circleId, $this->federatedUserService->getCurrentUser());

		$event = new FederatedEvent(CircleJoin::class);
		$event->setCircle($circle);

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $circleId
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 */
	public function circleLeave(string $circleId): array {
		$this->federatedUserService->mustHaveCurrentUser();

		$circle = $this->circleRequest->getCircle($circleId, $this->federatedUserService->getCurrentUser());

		$event = new FederatedEvent(CircleLeave::class);
		$event->setCircle($circle);

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param Member|null $filter
	 * @param bool $filterSystemCircles
	 *
	 * @return Circle[]
	 * @throws InitiatorNotFoundException
	 */
	public function getCircles(?Member $filter = null, bool $filterSystemCircles = true): array {
		$this->federatedUserService->mustHaveCurrentUser();

		return $this->circleRequest->getCircles(
			$filter,
			$this->federatedUserService->getCurrentUser(),
			$this->federatedUserService->getRemoteInstance(),
			$filterSystemCircles
		);
	}


	/**
	 * @param string $circleId
	 * @param int $filter
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 */
	public function getCircle(
		string $circleId,
		int $filter = Circle::CFG_BACKEND | Circle::CFG_SINGLE | Circle::CFG_HIDDEN
	): Circle {
		$this->federatedUserService->mustHaveCurrentUser();

		return $this->circleRequest->getCircle(
			$circleId,
			$this->federatedUserService->getCurrentUser(),
			$this->federatedUserService->getRemoteInstance(),
			$filter
		);
	}


	/**
	 * @param Circle $circle
	 *
	 * @throws MembersLimitException
	 */
	public function confirmCircleNotFull(Circle $circle): void {
		if ($this->isCircleFull($circle)) {
			throw new MembersLimitException(StatusCode::$MEMBER_ADD[121], 121);
		}
	}


	/**
	 * @param Circle $circle
	 *
	 * @return bool
	 */
	public function isCircleFull(Circle $circle): bool {
		$filter = new Member();
		$filter->setLevel(Member::LEVEL_MEMBER);
		$members = $this->memberRequest->getMembers($circle->getId(), null, null, $filter);

		$limit = $this->getInt('members_limit', $circle->getSettings());
		if ($limit === -1) {
			return false;
		}
		if ($limit === 0) {
			$limit = $this->configService->getAppValue(ConfigService::MEMBERS_LIMIT);
		}

		return (sizeof($members) >= $limit);
	}

}

