<?php

use MediaWiki\Extension\StopForumSpam\Maintenance\UpdateDenyList;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;

require_once __DIR__ . '/../../../../extensions/StopForumSpam/maintenance/updateDenyList.php';

/**
 * Test: StopForumSpam deny-list script.
 *
 * @group Database
 * @covers \MediaWiki\Extension\StopForumSpam\Maintenance\UpdateDenyList
 */
class UpdateDenyListTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return UpdateDenyList::class;
	}

	public function testShowsEmptyCacheState(): void {
		$this->maintenance->setOption( 'show', true );
		$this->maintenance->execute();

		$this->expectOutputString( "List of SFS IPs...\n\nNo deny-listed IPs found in cache.\n" );
	}

}
