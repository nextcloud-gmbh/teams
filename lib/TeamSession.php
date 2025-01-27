<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles;

use NCU\Config\IUserConfig;
use OCA\Circles\Api\v2\ITeamMemberOperation;
use OCA\Circles\Api\v2\ITeamOperation;
use OCA\Circles\Api\v2\Operation\TeamMemberOperation;
use OCA\Circles\Api\v2\Operation\TeamOperation;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\TeamEntityMapper;
use OCA\Circles\Enum\TeamEntityType;
use OCA\Circles\Exceptions\TeamEntityNotFoundException;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Service\TeamService;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

class TeamSession implements ITeamSession {
	private ?TeamEntity $entity = null;
	private ?ITeamOperation $initiatedTeamOperation = null;
	private ?ITeamMemberOperation $initiatedTeamMemberOperation = null;

	public function __construct(
		private IUserSession $userSession,
		private IUserManager $userManager,
		private IUserConfig $userConfig,
		private IAppConfig $appConfig,
		private TeamEntityManager $teamEntityManager,
		private TeamEntityMapper $teamEntityMapper,
		private TeamService $teamService,
		private TeamOperation $teamOperation,
		private TeamMemberOperation $teamMemberOperation,
	) {
		// by default, and if available, ITeamSession uses current user session
		$user = $this->userSession?->getUser();
		if ($user !== null) {
			$this->entity = $this->generateEntityFromUser($user);
		}
	}

	public function asCurrentUser(): self {
		$user = $this->userSession?->getUser();
		if ($user === null) {
			return $this;
		}

		return $this->asEntity($this->generateEntityFromUser($user));
	}

	public function asUser(IUser $user): self {
		return $this->asEntity($this->generateEntityFromUser($user));
	}

	public function asLocalUser(string $userId): self {
		$singleId = $this->userConfig->getValueString($userId, Application::APP_ID, 'singleId');
		if ($singleId !== '') {
			$teamEntity = $this->generateTeamEntity($singleId, TeamEntityType::LOCAL_USER, $userId);
		} else {
			$user = $this->userManager->get($userId);
			if ($user === null) {
				throw new TeamEntityNotFoundException('local user not found');
			}
			$teamEntity = $this->generateEntityFromUser($user);
		}

		return $this->asEntity($teamEntity);
	}

	public function asApp(string $appId): self {
		$singleId = $this->appConfig->getValueString($appId, 'teamSingleId');
		if ($singleId !== '') {
			$teamEntity = $this->generateTeamEntity($singleId, TeamEntityType::APP, $appId);
		} else {
			try {
				$teamEntity = $this->teamEntityManager->searchTeamEntity(TeamEntityType::APP, $appId);
			} catch (TeamEntityNotFoundException) {
				$teamEntity = $this->teamEntityManager->createTeamEntity(TeamEntityType::APP, $appId, $appId);
			}
			$this->appConfig->setValueString($appId, 'teamSingleId', $teamEntity->getSingleId());
		}

		return $this->asEntity($teamEntity);
	}

	public function asOcc(): self {
		$singleId = $this->appConfig->getValueString(Application::APP_ID, 'occSingleId');
		if ($singleId !== '') {
			$teamEntity = $this->generateTeamEntity($singleId, TeamEntityType::OCC, 'occ');
		} else {
			try {
				$teamEntity = $this->teamEntityManager->searchTeamEntity(TeamEntityType::OCC, 'occ');
			} catch (TeamEntityNotFoundException) {
				$teamEntity = $this->teamEntityManager->createTeamEntity(TeamEntityType::OCC, 'occ', 'Occ Command');
			}
			$this->appConfig->setValueString(Application::APP_ID, 'occSingleId', $teamEntity->getSingleId());
		}

		return $this->asEntity($teamEntity);
	}

	public function asSuperAdmin(): self {
		$singleId = $this->appConfig->getValueString(Application::APP_ID, 'superAdminSingleId');
		if ($singleId !== '') {
			$teamEntity = $this->generateTeamEntity($singleId, TeamEntityType::SUPER_ADMIN, 'superAdmin');
		} else {
			try {
				$teamEntity = $this->teamEntityManager->searchTeamEntity(TeamEntityType::SUPER_ADMIN, 'superAdmin');
			} catch (TeamEntityNotFoundException) {
				$teamEntity = $this->teamEntityManager->createTeamEntity(TeamEntityType::SUPER_ADMIN, 'superAdmin', 'Super Admin script');
			}
			$this->appConfig->setValueString(Application::APP_ID, 'superAdminSingleId', $teamEntity->getSingleId());
		}

		return $this->asEntity($teamEntity);
	}

	public function asEntity(TeamEntity $entity): self {
		$session = clone $this;
		$session->entity = $entity;
		$this->initiatedTeamOperation = null;
		$this->initiatedTeamMemberOperation = null;
		return $session;
	}

	public function getEntity(): ?TeamEntity {
		return $this->entity;
	}

	private function generateEntityFromUser(IUser $user): TeamEntity {
		$singleId = $this->userConfig->getValueString($user->getUID(), Application::APP_ID, 'singleId');
		if ($singleId === '') {
			try {
				$teamEntity = $this->teamEntityManager->searchTeamEntity(TeamEntityType::LOCAL_USER, $user->getUID());
			} catch (TeamEntityNotFoundException) {
				$teamEntity = $this->teamEntityManager->createTeamEntity(TeamEntityType::LOCAL_USER, $user->getUID(), $user->getDisplayName());
			}
			$this->userConfig->setValueString($user->getUID(), Application::APP_ID, 'singleId', $teamEntity->getSingleId());
			return $teamEntity;
		}

		return $this->generateTeamEntity($singleId, TeamEntityType::LOCAL_USER, $user->getUID(), $user->getDisplayName());
	}

	private function generateTeamEntity(string $singleId, TeamEntityType $type, string $origId, string $displayName = ''): TeamEntity {
		$entity = new TeamEntity($this->teamEntityMapper);
		$entity->setSingleId($singleId);
		$entity->setTeamEntityType($type);
		$entity->setOrigId($origId);
		$entity->setDisplayName($displayName);
		return $entity;
	}

	public function performTeamOperation(): ITeamOperation {
		if ($this->initiatedTeamOperation === null) {
			if ($this->entity === null) {
				throw new \Exception('session must be initiated'); // TODO: specific exception
			}
			$operation = clone $this->teamOperation;
			$operation->asEntity($this->entity);
			$this->initiatedTeamOperation = $operation;
		}

		return $this->initiatedTeamOperation;
	}

	public function performTeamMemberOperation(): ITeamMemberOperation {
		if ($this->initiatedTeamMemberOperation === null) {
			if ($this->entity === null) {
				throw new \Exception('session must be initiated'); // TODO: specific exception
			}
			$operation = clone $this->teamMemberOperation;
			$operation->asEntity($this->entity);
			$this->initiatedTeamMemberOperation = $operation;
		}

		return $this->initiatedTeamMemberOperation;
	}

}
