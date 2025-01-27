<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Service;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Db\TeamMapper;
use OCA\Circles\Db\TeamMemberMapper;
use OCA\Circles\Enum\TeamMemberLevel;
use OCA\Circles\Exceptions\CircleNameTooShortException;
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
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\CircleConfig;
use OCA\Circles\FederatedItems\CircleCreate;
use OCA\Circles\FederatedItems\CircleDestroy;
use OCA\Circles\FederatedItems\CircleEdit;
use OCA\Circles\FederatedItems\CircleJoin;
use OCA\Circles\FederatedItems\CircleLeave;
use OCA\Circles\FederatedItems\CircleSetting;
use OCA\Circles\IEntity;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\Probes\DataProbe;
use OCA\Circles\Model\Probes\MemberProbe;
use OCA\Circles\Model\Team;
use OCA\Circles\Model\TeamMember;
use OCA\Circles\StatusCode;
use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TDeserialize;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IL10N;
use OCP\Security\IHasher;

class TeamService {
	public function __construct(
		private TeamMapper $teamMapper,
		private TeamMemberMapper $teamMemberMapper,
	) {
	}

	public function create(string $name, string $ownerSingleId): void {
		$teamSingleId = $this->generateSingleId();

		// todo: check team_single_id does not exist in oc_teams not oc_teams_members

		$owner = new TeamMember();
		$owner->setTeamSingleId($teamSingleId);
		$owner->setMemberSingleId($ownerSingleId);
		$owner->setTeamMemberLevel(TeamMemberLevel::OWNER);
		$owner->setCreation(time());
		$this->teamMemberMapper->insert($owner);

		$team = new Team();
		$team->setDisplayName($name);
		$team->setSanitizedName($this->generateSingleId(6)); // TODO
		$team->setOwner($owner);
		$team->setSingleId($teamSingleId);

		$this->teamMapper->insert($team);
	}

	public function store(Team $team): void {
		$this->teamMapper->insert($team);
	}
private int $count = 1;

	public function count(): void {
		echo '(' . $this->count++ . ')' . "\n";
	}
private function generateMetadata(Team $team): void {

}


	public function generateSingleId(int $length = 15): string {
		$availableChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$availableCharsLength = strlen($availableChars);
		$result = '';

		for ($i = 0; $i < $length; $i++) {
			$result .= $availableChars[random_int(0, $availableCharsLength - 1)];
		}

		return $result;
	}
}
