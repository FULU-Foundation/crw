<?php

use MediaWiki\Title\Title;

/**
 * Test: upload a file and generate a thumbnail.
 *
 * @group Database
 * @covers \LocalFile
 */
class ThumbnailTest extends MediaWikiIntegrationTestCase {

	private const ONE_PIXEL_PNG_BASE64 =
		'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

	public function testUploadAndThumbnailGeneration(): void {
		$tmpFile = $this->getNewTempFile();
		file_put_contents( $tmpFile, base64_decode( self::ONE_PIXEL_PNG_BASE64 ) );

		$title = Title::makeTitle( NS_FILE, 'SiteTests_Thumbnail_' . wfRandomString( 8 ) . '.png' );
		$file = $this->getServiceContainer()->getRepoGroup()->getLocalRepo()->newFile( $title );
		$uploader = $this->getTestUser()->getUser();

		$status = $file->upload( $tmpFile, 'Test upload for site-tests', 'Test page text', 0, false, false, $uploader );
		$this->assertTrue( $status->isGood(), 'Upload should succeed: ' . $status->getWikiText() );

		$file = $this->getServiceContainer()->getRepoGroup()->getLocalRepo()->newFile( $title );
		$this->assertTrue( $file->exists(), 'Uploaded file should exist in the repo' );

		$thumb = $file->transform( [ 'width' => 1 ] );

		$this->assertNotFalse( $thumb, 'Thumbnail transform should not fail' );
		$this->assertFalse( $thumb->isError(), 'Thumbnail should not be an error result' );
	}

}
