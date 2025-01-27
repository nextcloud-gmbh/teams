<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Circles\Command;

use OC\Core\Command\Base;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\TeamEntity;
use OCA\Circles\Service\TeamService;
use OCA\Circles\TeamSession;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CirclesCreate
 *
 * @package OCA\Circles\Command
 */
class Test extends Base {
	public function __construct(
		private TeamSession $teamManager,
		private TeamService $teamService,
	) {
		parent::__construct();
	}


	protected function configure() {
		parent::configure();
		$this->setName('teams:test')
			->setDescription('create a new circle');
//			->addArgument('owner', InputArgument::REQUIRED, 'owner of the circle')
//			->addArgument('name', InputArgument::REQUIRED, 'name of the circle')
//			->addOption('personal', '', InputOption::VALUE_NONE, 'create a personal circle')
//			->addOption('local', '', InputOption::VALUE_NONE, 'create a local circle')
//			->addOption('status-code', '', InputOption::VALUE_NONE, 'display status code on exception')
//			->addOption(
//				'type', '', InputOption::VALUE_REQUIRED, 'type of the owner',
//				Member::$TYPE[Member::TYPE_SINGLE]
//			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		$entity = new TeamEntity();
		$entity->setSingleId('1234');
//		$team = new Team();
//		$this->teamService->store($team);
		$this->teamManager->test();
		$session = $this->teamManager->asEntity($entity);
		$session->test();
		$session = $this->teamManager->asEntity($entity);
		$session->test();
		return 0;

		$ownerId = $input->getArgument('owner');
		$name = $input->getArgument('name');

		try {
			$this->federatedUserService->bypassCurrentUserCondition(true);

			$type = Member::parseTypeString($input->getOption('type'));

			$owner = $this->federatedUserService->getFederatedUser($ownerId, $type);
			$outcome = $this->circleService->create(
				$name,
				$owner,
				$input->getOption('personal'),
				$input->getOption('local')
			);
		} catch (FederatedItemException $e) {
			if ($input->getOption('status-code')) {
				throw new FederatedItemException(
					' [' . get_class($e) . ', ' . $e->getStatus() . ']' . "\n" . $e->getMessage()
				);
			}

			throw $e;
		}

		if (strtolower($input->getOption('output')) === 'json') {
			$output->writeln(json_encode($outcome, JSON_PRETTY_PRINT));
		} elseif (strtolower($input->getOption('output')) !== 'none') {
			/** @var Circle $circle */
			$circle = $this->deserialize($outcome, Circle::class);
			$output->writeln('Id: <info>' . $circle->getSingleId() . '</info>');
			$output->writeln('Name: <info>' . $circle->getDisplayName() . '</info>');
			$output->writeln('Owner: <info>' . $circle->getOwner()->getDisplayName() . '</info>');
		}

		return 0;
	}
}
