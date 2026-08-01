<?php

use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;

/**
 * Test: create and edit a page.
 *
 * @group Database
 * @covers \MediaWiki\Page\WikiPage
 */
class PageEditTest extends MediaWikiIntegrationTestCase {

	public function testCreateAndEditPage(): void {
		$title = Title::newFromText( 'CrwTest_PageEdit_' . wfRandomString( 8 ) );

		$status = $this->editPage( $title, 'Initial content.' );
		$this->assertStatusGood( $status, 'Initial page creation should succeed' );

		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$content = $page->getRevisionRecord()
			->getContent( SlotRecord::MAIN )
			->getText();
		$this->assertSame( 'Initial content.', $content, 'Page content should match what was just created' );

		$status = $this->editPage( $title, 'Updated content.' );
		$this->assertStatusGood( $status, 'Follow-up edit should succeed' );

		$page->clear();
		$content = $page->getRevisionRecord()
			->getContent( SlotRecord::MAIN )
			->getText();
		$this->assertSame( 'Updated content.', $content, 'Page content should reflect the update' );
	}

}
