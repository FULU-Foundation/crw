<?php

use MediaWiki\JobQueue\Jobs\NullJob;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;

/**
 * Test: job queue.
 *
 * @group Database
 * @covers \RunJobs
 */
class RunJobsTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return RunJobs::class;
	}

	public function testDrainsTheQueue(): void {
		$queueGroup = $this->getServiceContainer()->getJobQueueGroup();
		$queueGroup->push( new NullJob( [] ) );

		$this->assertGreaterThan( 0, $queueGroup->getQueueSizes()['null'] ?? 0 );

		$this->maintenance->setOption( 'maxjobs', 10 );
		$this->maintenance->execute();

		$this->assertSame( 0, $queueGroup->getQueueSizes()['null'] ?? 0 );
	}

}
