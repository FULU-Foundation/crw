<?php

/**
 * Test: user creation and login.
 *
 * @group Database
 * @covers \User
 */
class LoginTest extends MediaWikiIntegrationTestCase {

	public function testTestUserIsRegistered(): void {
		$user = $this->getTestUser()->getUser();

		$this->assertTrue( $user->isRegistered() );
		$this->assertFalse( $user->isAnon() );
		$this->assertGreaterThan( 0, $user->getId() );
	}

	public function testUserCanBeLoadedFromDatabaseById(): void {
		$user = $this->getTestUser()->getUser();

		$loaded = User::newFromId( $user->getId() );
		$loaded->load();

		$this->assertSame( $user->getName(), $loaded->getName() );
	}

}
