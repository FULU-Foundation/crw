<?php

use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;

/**
 * Test: sitemap generation writes files.
 *
 * @group Database
 * @covers \GenerateSitemap
 */
class GenerateSitemapTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return GenerateSitemap::class;
	}

	public function testGeneratesSitemapFiles(): void {
		$outputDir = $this->getNewTempDirectory();

		$this->maintenance->setOption( 'fspath', $outputDir );
		$this->maintenance->setOption( 'urlpath', '/sitemap' );
		$this->maintenance->setOption( 'identifier', 'crwtest' );
		$this->maintenance->setOption( 'compress', 'no' );
		$this->maintenance->execute();

		$files = glob( $outputDir . '/sitemap-crwtest-*' );
		$this->assertNotEmpty( $files, 'GenerateSitemap should write at least one sitemap file' );
	}

}
