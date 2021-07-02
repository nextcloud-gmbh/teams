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


use ArtificialOwl\MySmallPhpTools\Model\SimpleDataStore;
use ArtificialOwl\MySmallPhpTools\Traits\Nextcloud\nc22\TNC22Logger;
use ArtificialOwl\MySmallPhpTools\Traits\TStringTools;
use Exception;
use OC;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\ContactAddressBookNotFoundException;
use OCA\Circles\Exceptions\ContactFormatException;
use OCA\Circles\Exceptions\ContactNotFoundException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\MemberNotFoundException;
use OCA\Circles\Exceptions\MigrationException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Exceptions\UserTypeNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\Member;
use OCA\DAV\CardDAV\ContactsManager;
use OCP\Contacts\IManager;
use OCP\IDBConnection;
use OCP\IURLGenerator;


/**
 * Class MigrationService
 *
 * @package OCA\Circles\Service
 */
class MigrationService {


	use TStringTools;
	use TNC22Logger;


	/** @var IDBConnection */
	private $dbConnection;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var MembershipService */
	private $membershipService;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var CircleService */
	private $circleService;

	/** @var ContactService */
	private $contactService;

	/** @var TimezoneService */
	private $timezoneService;

	/** @var OutputService */
	private $outputService;

	/** @var ConfigService */
	private $configService;


	/** @var FederatedUser */
	private $appCircle = null;


	/**
	 * MigrationService constructor.
	 *
	 * @param IDBConnection $dbConnection
	 * @param IURLGenerator $urlGenerator
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param MembershipService $membershipService
	 * @param FederatedUserService $federatedUserService
	 * @param CircleService $circleService
	 * @param ContactService $contactService
	 * @param TimezoneService $timezoneService
	 * @param OutputService $outputService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IDBConnection $dbConnection,
		IURLGenerator $urlGenerator,
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		MembershipService $membershipService,
		FederatedUserService $federatedUserService,
		CircleService $circleService,
		ContactService $contactService,
		TimezoneService $timezoneService,
		OutputService $outputService,
		ConfigService $configService
	) {
		$this->dbConnection = $dbConnection;
		$this->urlGenerator = $urlGenerator;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->membershipService = $membershipService;
		$this->federatedUserService = $federatedUserService;
		$this->circleService = $circleService;
		$this->contactService = $contactService;
		$this->timezoneService = $timezoneService;
		$this->outputService = $outputService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param bool $force
	 *
	 * @throws ContactAddressBookNotFoundException
	 * @throws ContactFormatException
	 * @throws ContactNotFoundException
	 * @throws FederatedUserException
	 * @throws InvalidIdException
	 * @throws MigrationException
	 * @throws RequestBuilderException
	 * @throws SingleCircleNotFoundException
	 */
	public function migration(bool $force = false): void {
		if ($this->configService->getAppValueBool(ConfigService::MIGRATION_RUN)) {
			throw new MigrationException('A migration process is already running');
		}
		$this->configService->setAppValue(ConfigService::MIGRATION_RUN, '1');

		if ($force) {
			$this->configService->setAppValue(ConfigService::MIGRATION_22, '0');
//			$this->configService->setAppValue(ConfigService::MIGRATION_23, '0');
		}

		$this->appCircle = $this->federatedUserService->getAppInitiator(
			Application::APP_ID,
			Member::APP_CIRCLES
		);

		$this->migrationTo22();
		//	$this->migrationTo23();

		$this->configService->setAppValue(ConfigService::MIGRATION_RUN, '0');
	}


	/**
	 *
	 */
	private function migrationTo22(): void {
		if ($this->configService->getAppValueBool(ConfigService::MIGRATION_22)) {
			return;
		}

		$this->outputService->output('Migrating to 22');

		$this->migrationTo22_Circles();
		$this->migrationTo22_Members();
		$this->membershipService->resetMemberships('', true);
		$this->membershipService->manageAll();

		$this->configService->setAppValue(ConfigService::MIGRATION_22, '1');
	}


	private function migrationTo22_Circles(): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')->from('circle_circles');

		try {
			$cursor = $qb->executeQuery();
			$this->outputService->startMigrationProgress($cursor->rowCount());

			while ($row = $cursor->fetch()) {
				try {
					$data = new SimpleDataStore($row);
					$this->outputService->output(
						'Migrating Circle \'' . $data->g('name') . '\'',
						true
					);

					$circle = $this->generateCircleFrom21($data);
					$this->saveGeneratedCircle($circle);
				} catch (Exception $e) {
				}
			}

			$cursor->closeCursor();
		} catch (\OCP\DB\Exception $e) {
		}

		$this->outputService->finishMigrationProgress();
	}


	/**
	 * @param SimpleDataStore $data
	 *
	 * @return Circle
	 * @throws RequestBuilderException
	 */
	private function generateCircleFrom21(SimpleDataStore $data): Circle {
		$circle = new Circle();
		$circle->setSingleId($data->g('unique_id'))
			   ->setName($data->g('name'))
			   ->setDisplayName($data->g('display_name'))
			   ->setSettings($data->gArray('settings'))
			   ->setDescription($data->g('description'))
			   ->setContactAddressBook($data->gInt('contact_addressbook'))
			   ->setContactGroupName($data->g('contact_groupname'))
			   ->setSource(Member::TYPE_CIRCLE);

		$dTime = $this->timezoneService->getDateTime($data->g('creation'));
		$circle->setCreation($dTime->getTimestamp());

		if ($circle->getDisplayName() === '') {
			$circle->setDisplayName($circle->getName());
		}

		$this->circleService->generateSanitizedName($circle);
		$this->convertCircleTypeFrom21($circle, $data->gInt('type'));

		return $circle;
	}


	/**
	 * @param Circle $circle
	 * @param int $type
	 */
	private function convertCircleTypeFrom21(Circle $circle, int $type): void {
		switch ($type) {
			case 1: // personal
				$circle->setConfig(Circle::CFG_PERSONAL);
				break;

			case 2: // secret
				$circle->setConfig(Circle::CFG_OPEN + Circle::CFG_CIRCLE);
				break;

			case 4: // closed
				$circle->setConfig(Circle::CFG_OPEN + Circle::CFG_REQUEST + Circle::CFG_VISIBLE);
				break;

			case 8: // public
				$circle->setConfig(Circle::CFG_OPEN + Circle::CFG_VISIBLE);
				break;
		}
	}


	/**
	 * @param Circle $circle
	 */
	private function saveGeneratedCircle(Circle $circle): void {
		try {
			$this->circleRequest->getCircle($circle->getSingleId());
		} catch (CircleNotFoundException $e) {
			try {
				$this->circleRequest->save($circle);
			} catch (InvalidIdException $e) {
			}
		} catch (RequestBuilderException $e) {
		}
	}


	/**
	 */
	private function migrationTo22_Members(): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')->from('circle_members');

		try {
			$cursor = $qb->executeQuery();
			$this->outputService->startMigrationProgress($cursor->rowCount());

			while ($row = $cursor->fetch()) {
				try {
					$data = new SimpleDataStore($row);
					$this->outputService->output(
						'Migrating Member \'' . $data->g('user_id') . '\'',
						true
					);

					$member = $this->generateMemberFrom21($data);
					$this->saveGeneratedMember($member);
				} catch (Exception $e) {
				}
			}

			$cursor->closeCursor();
		} catch (\OCP\DB\Exception $e) {
		}

		$this->outputService->finishMigrationProgress();
	}


	/**
	 * @throws CircleNotFoundException
	 * @throws RemoteInstanceException
	 * @throws UserTypeNotFoundException
	 * @throws FederatedUserNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RequestBuilderException
	 * @throws RemoteNotFoundException
	 * @throws UnknownRemoteException
	 * @throws FederatedUserException
	 * @throws ContactAddressBookNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws MemberNotFoundException
	 * @throws FederatedItemException
	 * @throws SingleCircleNotFoundException
	 * @throws InvalidIdException
	 */
	private function generateMemberFrom21(SimpleDataStore $data): Member {
		$member = new Member();

		$member->setCircleId($data->g('circle_id'))
			   ->setId($data->g('member_id'))
			   ->setUserId($data->g('user_id'))
			   ->setInstance($data->g('instance'))
			   ->setDisplayName($data->g('cached_name'))
			   ->setLevel($data->gInt('level'))
			   ->setStatus($data->g('status'))
			   ->setContactMeta($data->g('contact_meta'))
			   ->setContactId($data->g('contact_id'))
			   ->setInvitedBy($this->appCircle);

		$this->convertMemberUserTypeFrom21($member, $data->gInt('user_type'));

		$singleMember = $this->federatedUserService->getFederatedUser(
			$member->getUserId(),
			$member->getUserType()
		);

		$member->setSingleId($singleMember->getSingleId());

//					"cached_update":"2021-05-02 12:13:22",
//					"joined":"2021-05-02 12:13:22",
//					"contact_checked":null,"
//					single_id":"wt6WQYYCry3EOud",
//					"circle_source":null}

		return $member;
	}


	/**
	 * @param Member $member
	 * @param int $userType
	 *
	 * @throws ContactAddressBookNotFoundException
	 */
	private function convertMemberUserTypeFrom21(Member $member, int $userType): void {
		switch ($userType) {
			case 1:
				$member->setUserType(1);

				return;
			case 2:
				$member->setUserType(2);

				return;
			case 3:
				$member->setUserType(4);

				return;
			case 4:
				$member->setUserType(8);
				$this->fixContactId($member);

				return;
		}
	}


	/**
	 * @param Member $member
	 *
	 * @throws ContactAddressBookNotFoundException
	 */
	private function fixContactId(Member $member) {
		list($userId, $contactId) = explode(':', $member->getUserId());

		$contactsManager = OC::$server->get(ContactsManager::class);

		/** @var IManager $cm */
		$cm = OC::$server->get(IManager::class);
		$contactsManager->setupContactsProvider($cm, $userId, $this->urlGenerator);

		$contact = $cm->search($contactId, ['UID']);
		if (sizeof($contact) === 1) {
			$entry = array_shift($contact);
			$addressBook =
				$this->contactService->getAddressBoxById($cm, $this->get('addressbook-key', $entry));

			$member->setUserId($userId . '/' . $addressBook->getUri() . '/' . $contactId);
		}
	}


	/**
	 * @param Member $member
	 */
	private function saveGeneratedMember(Member $member): void {
		try {
			$this->memberRequest->getMemberById($member->getId());
		} catch (MemberNotFoundException $e) {
			try {
				$this->memberRequest->save($member);
			} catch (InvalidIdException $e) {
			}
		} catch (RequestBuilderException $e) {
		}
	}

}
