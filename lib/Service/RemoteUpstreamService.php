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


use daita\MySmallPhpTools\Exceptions\RequestNetworkException;
use daita\MySmallPhpTools\Exceptions\SignatoryException;
use daita\MySmallPhpTools\Model\Nextcloud\nc21\NC21RequestResult;
use daita\MySmallPhpTools\Model\Request;
use daita\MySmallPhpTools\Model\SimpleDataStore;
use daita\MySmallPhpTools\Traits\Nextcloud\nc21\TNC21Request;
use Exception;
use OCA\Circles\Db\RemoteWrapperRequest;
use OCA\Circles\Exceptions\FederatedEventDSyncException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Federated\RemoteInstance;
use OCA\Circles\Model\Federated\RemoteWrapper;
use OCA\Circles\Model\GlobalScale\GSEvent;
use OCA\Circles\Model\GlobalScale\GSWrapper;
use OCP\AppFramework\Http;
use OCP\IL10N;
use ReflectionClass;
use ReflectionException;


/**
 * Class RemoteUpstreamService
 *
 * @package OCA\Circles\Service
 */
class RemoteUpstreamService {


	use TNC21Request;


	/** @var IL10N */
	private $l10n;

	/** @var RemoteWrapperRequest */
	private $remoteWrapperRequest;

	/** @var RemoteStreamService */
	private $remoteStreamService;

	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var ConfigService */
	private $configService;


	/**
	 * RemoteUpstreamService constructor.
	 *
	 * @param IL10N $l10n
	 * @param RemoteWrapperRequest $remoteWrapperRequest
	 * @param RemoteStreamService $remoteStreamService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IL10N $l10n, RemoteWrapperRequest $remoteWrapperRequest, RemoteStreamService $remoteStreamService,
		ConfigService $configService
	) {
		$this->l10n = $l10n;
		$this->remoteWrapperRequest = $remoteWrapperRequest;
		$this->remoteStreamService = $remoteStreamService;
		$this->configService = $configService;
	}


	/**
	 * @param string $token
	 *
	 * @return RemoteWrapper[]
	 */
	public function getEventsByToken(string $token): array {
		return $this->remoteWrapperRequest->getByToken($token);
	}


	/**
	 * @param RemoteWrapper $wrapper
	 */
	public function broadcastWrapper(RemoteWrapper $wrapper): void {
		$status = RemoteWrapper::STATUS_FAILED;

		try {
			$this->broadcastEvent($wrapper->getEvent(), $wrapper->getInstance());
			$status = GSWrapper::STATUS_DONE;
		} catch (Exception $e) {
		}

		if ($wrapper->getSeverity() === GSEvent::SEVERITY_HIGH) {
			$wrapper->setStatus($status);
		} else {
			$wrapper->setStatus(GSWrapper::STATUS_OVER);
		}

		$this->remoteWrapperRequest->update($wrapper);
	}


	/**
	 * @param FederatedEvent $event
	 * @param string $instance
	 *
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 */
	public function broadcastEvent(FederatedEvent $event, string $instance): void {
		$data = $this->remoteStreamService->resultRequestRemoteInstance(
			$instance,
			RemoteInstance::INCOMING,
			Request::TYPE_POST,
			$event
		);

		$event->setResult(new SimpleDataStore($this->getArray('result', $data, [])));
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws FederatedEventDSyncException
	 * @throws FederatedEventException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestNetworkException
	 * @throws SignatoryException
	 * @throws UnknownRemoteException
	 */
	public function confirmEvent(FederatedEvent $event): void {
		$data = $this->remoteStreamService->requestRemoteInstance(
			$event->getCircle()->getInstance(),
			RemoteInstance::EVENT,
			Request::TYPE_POST,
			$event
		);

		// TODO: check what is happening if website is down...
		if (!$data->getOutgoingRequest()->hasResult()) {
			throw new RemoteInstanceException();
		}

		$result = $data->getOutgoingRequest()->getResult();
		$this->manageRequestOutcome($event, $result->getAsArray());

		if ($result->getStatusCode() === Http::STATUS_OK) {
			return;
		}

		throw $this->getFederatedItemExceptionFromResult($result, $event->getReadingOutcome());
	}


	//
	//
	//

//	/**
//	 * @param array $sync
//	 *
//	 * @throws GSStatusException
//	 */
//	public function synchronize(array $sync) {
//		$this->configService->getGSStatus();
//
//		$this->synchronizeCircles($sync);
//		$this->removeDeprecatedCircles();
//		$this->removeDeprecatedEvents();
//	}


//	/**
//	 * @param array $circles
//	 */
//	public function synchronizeCircles(array $circles): void {
//		$event = new GSEvent(GSEvent::GLOBAL_SYNC, true);
//		$event->setSource($this->configService->getLocalInstance());
//		$event->setData(new SimpleDataStore($circles));
//
//		foreach ($this->federatedEventService->getInstances() as $instance) {
//			try {
//				$this->broadcastEvent($event, $instance);
//			} catch (RequestContentException | RequestNetworkException | RequestResultSizeException | RequestServerException | RequestResultNotJsonException $e) {
//			}
//		}
//	}
//
//
//	/**
//	 *
//	 */
//	private function removeDeprecatedCircles() {
//		$knownCircles = $this->circlesRequest->forceGetCircles();
//		foreach ($knownCircles as $knownItem) {
//			if ($knownItem->getOwner()
//						  ->getInstance() === '') {
//				continue;
//			}
//
//			try {
//				$this->checkCircle($knownItem);
//			} catch (GSStatusException $e) {
//			}
//		}
//	}
//
//
//	/**
//	 * @param DeprecatedCircle $circle
//	 *
//	 * @throws GSStatusException
//	 */
//	private function checkCircle(DeprecatedCircle $circle): void {
//		$status = $this->confirmCircleStatus($circle);
//
//		if (!$status) {
//			$this->circlesRequest->destroyCircle($circle->getUniqueId());
//			$this->membersRequest->removeAllFromCircle($circle->getUniqueId());
//		}
//	}
//
//
//	/**
//	 * @param DeprecatedCircle $circle
//	 *
//	 * @return bool
//	 * @throws GSStatusException
//	 */
//	public function confirmCircleStatus(DeprecatedCircle $circle): bool {
//		$event = new GSEvent(GSEvent::CIRCLE_STATUS, true);
//		$event->setSource($this->configService->getLocalInstance());
//		$event->setDeprecatedCircle($circle);
//
//		$this->signEvent($event);
//
//		$path = $this->urlGenerator->linkToRoute('circles.RemoteWrapper.status');
//		$request = new NC21Request($path, Request::TYPE_POST);
//		$this->configService->configureRequest($request);
//		$request->setDataSerialize($event);
//
//		$requestIssue = false;
//		$notFound = false;
//		$foundWithNoOwner = false;
//		foreach ($this->federatedEventService->getInstances() as $instance) {
//			$request->setHost($instance);
//
//			try {
//				$result = $this->retrieveJson($request);
//				if ($this->getInt('status', $result, 0) !== 1) {
//					throw new RequestContentException('result status is not good');
//				}
//
//				$status = $this->getInt('success.data.status', $result);
//
//				// if error, we assume the circle might still exist.
//				if ($status === CircleStatus::STATUS_ERROR) {
//					return true;
//				}
//
//				if ($status === CircleStatus::STATUS_OK) {
//					return true;
//				}
//
//				// TODO: check the data.supposedOwner entry.
//				if ($status === CircleStatus::STATUS_NOT_OWNER) {
//					$foundWithNoOwner = true;
//				}
//
//				if ($status === CircleStatus::STATUS_NOT_FOUND) {
//					$notFound = true;
//				}
//
//			} catch (RequestContentException
//			| RequestNetworkException
//			| RequestResultNotJsonException
//			| RequestResultSizeException
//			| RequestServerException $e) {
//				$requestIssue = true;
//				// TODO: log instances that have network issue, after too many tries (7d), remove this circle.
//				continue;
//			}
//		}
//
//		// if no request issue, we can imagine that the instance that owns the circle is down.
//		// We'll wait for more information (cf request exceptions management);
//		if ($requestIssue) {
//			return true;
//		}
//
//		// circle were not found in any other instances, we can easily says that the circle does not exists anymore
//		if ($notFound && !$foundWithNoOwner) {
//			return false;
//		}
//
//		// circle were found everywhere but with no owner on every instance. we need to assign a new owner.
//		// This should be done by checking admin rights. if no admin rights, let's assume that circle should be removed.
//		if (!$notFound && $foundWithNoOwner) {
//			// TODO: assign a new owner and check that when changing owner, we do check that the destination instance is updated FOR SURE!
//			return true;
//		}
//
//		// some instances returned notFound, some returned circle with no owner. let's assume the circle is deprecated.
//		return false;
//	}
//
//	/**
//	 * @throws GSStatusException
//	 */
//	public function syncEvents() {
//
//	}
//
//	/**
//	 *
//	 */
//	private function removeDeprecatedEvents() {
////		$this->deprecatedEvents();
//
//	}


	/**
	 * @param FederatedEvent $event
	 * @param array $result
	 */
	private function manageRequestOutcome(FederatedEvent $event, array $result): void {
		$outcome = new SimpleDataStore($result);

		$event->setDataOutcome($outcome->gArray('data'));
		$event->setReadingOutcome(
			$outcome->g('reading.message'),
			$outcome->gArray('reading.params')
		);

		$reading = $event->getReadingOutcome();
		$reading->s('translated', $this->l10n->t($reading->g('message'), $reading->gArray('params')));
	}


	private function getFederatedItemExceptionFromResult(
		NC21RequestResult $result,
		SimpleDataStore $reading
	): FederatedItemException {

		$exception = $reading->gArray('params._exception');
		if (empty($exception)) {
			$e = $this->getFederatedItemExceptionFromStatus($result->getStatusCode());

			return new $e($reading->g('message'));
		}

		$class = $this->get('class', $exception);
		$message = $this->get('message', $exception);
		$params = $this->getArray('params', $exception);
		try {
			$test = new ReflectionClass($class);
			$this->confirmFederatedItemExceptionFromClass($test);
			$e = $class;
			echo $e;
		} catch (ReflectionException | FederatedItemException $_e) {
			$e = $this->getFederatedItemExceptionFromStatus($result->getStatusCode());
		}

		return new $e($message, $params);
	}


	/**
	 * @param int $statusCode
	 *
	 * @return string
	 */
	private function getFederatedItemExceptionFromStatus(int $statusCode): string {
		foreach (FederatedItemException::$CHILDREN as $e) {
			if ($e::STATUS === $statusCode) {
				return $e;
			}
		}

		return FederatedItemException::class;
	}


	/**
	 * @param ReflectionClass $class
	 *
	 * @return void
	 * @throws FederatedItemException
	 */
	private function confirmFederatedItemExceptionFromClass(ReflectionClass $class): void {
		while (true) {
			foreach (FederatedItemException::$CHILDREN as $e) {
				if ($class->getName() === $e) {
					return;
				}
			}
			$class = $class->getParentClass();
			if (!$class) {
				throw new FederatedItemException();
			}
		}
	}

}

