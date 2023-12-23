<?php

declare(strict_types=1);

/*
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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

use OCP\Circles\Model\IEntity;
use OCP\Circles\IEntityManager;
use OCP\Circles\IEntityManagerSession;

class EntityManager extends EntityManagerSession implements IEntityManager {
	public function isAvailable(): bool {
		return true;
	}

	public function asEntity(IEntity $entity): IEntityManagerSession {
	}

	public function asLocal(string $userId): IEntityManagerSession {
	}

	public function asSuper(): IEntityManagerSession {
	}
}
