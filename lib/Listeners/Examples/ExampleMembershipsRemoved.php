<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Listeners\Examples;

use Exception;
use OCA\Circles\AppInfo\Application;
use OCA\Circles\CirclesManager;
use OCA\Circles\Events\MembershipsRemovedEvent;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Membership;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<MembershipsRemovedEvent|Event> */
class ExampleMembershipsRemoved implements IEventListener {
	use TStringTools;
	use TNCLogger;


	/** @var CirclesManager */
	private $circlesManager;

	/** @var ConfigService */
	private $configService;

	public function __construct(
		CirclesManager $circlesManager,
		ConfigService $configService
	) {
		$this->circlesManager = $circlesManager;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}

	public function handle(Event $event): void {
		if (!$event instanceof MembershipsRemovedEvent) {
			return;
		}

		if ($this->configService->getAppValue(ConfigService::EVENT_EXAMPLES) !== '1') {
			return;
		}

		$prefix = '[Example Event] (ExampleMembershipsRemoved) ';

		$memberships = array_map(
			function (Membership $membership) {
				$inheritance = ($membership->getInheritanceDepth() > 1) ?
					'an inherited member' : 'a direct member';
				try {
					$federatedUser = $this->circlesManager->getFederatedUser($membership->getSingleId());
				} catch (Exception $e) {
					$this->e($e);

					return $membership->getSingleId() . ' is not ' . $inheritance . ' of '
						   . $membership->getCircleId() . ' anymore';
				}

				return $federatedUser->getUserId() . ' (' . Member::$TYPE[$federatedUser->getUserType()]
					   . ') is not ' . $inheritance . ' of ' . $membership->getCircleId() . ' anymore';
			}, $event->getMemberships()
		);

		$this->log(3, $prefix . implode('. ', $memberships));
	}
}
