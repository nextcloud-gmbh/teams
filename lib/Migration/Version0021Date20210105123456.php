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

namespace OCA\Circles\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;


/**
 * Class Version0021Date20210105123456
 *
 * @package OCA\Circles\Migration
 */
class Version0021Date20210105123456 extends SimpleMigrationStep {


	/** @var IDBConnection */
	private $connection;


	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		try {
			$circles = $schema->getTable('circle_circles');
			if (!$circles->hasColumn('config')) {
				$circles->addColumn(
					'config', 'integer', [
								'notnull'  => false,
								'length'   => 11,
								'unsigned' => true,
							]
				);
			}
			if (!$circles->hasColumn('instance')) {
				$circles->addColumn(
					'instance', 'string', [
								  'notnull' => true,
								  'default' => '',
								  'length'  => 255
							  ]
				);
			}
			$circles->addIndex(['config']);
		} catch (SchemaException $e) {
		}

		if (!$schema->hasTable('circle_remotes')) {
			$table = $schema->createTable('circle_remotes');
			$table->addColumn(
				'id', 'integer', [
						'autoincrement' => true,
						'notnull'       => true,
						'length'        => 4,
						'unsigned'      => true,
					]
			);
			$table->addColumn(
				'uid', 'string', [
						 'notnull' => false,
						 'length'  => 20,
					 ]
			);
			$table->addColumn(
				'instance', 'string', [
							  'notnull' => false,
							  'length'  => 127,
						  ]
			);
			$table->addColumn(
				'href', 'string', [
						  'notnull' => false,
						  'length'  => 254,
					  ]
			);
			$table->addColumn(
				'item', 'text', [
						  'notnull' => false,
					  ]
			);
			$table->addColumn(
				'creation', 'datetime', [
							  'notnull' => false,
						  ]
			);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['instance']);
			$table->addIndex(['uid']);
			$table->addIndex(['href']);
		}

		if (!$schema->hasTable('circle_memberships')) {
//			$table = $schema->createTable('circle_memberships');
//			$table->addColumn(
//				'id', 'string', [
//						 'notnull' => false,
//						 'length'  => 15,
//					 ]
//			);

//			$table->setIndex(['id']);
		}

		return $schema;
	}


}
