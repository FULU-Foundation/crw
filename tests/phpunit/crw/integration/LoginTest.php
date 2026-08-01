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

		$this->assertTrue( $user->isRegistered(), 'Test user should be registered' );
		$this->assertFalse( $user->isAnon(), 'Test user should not be anonymous' );
		$this->assertGreaterThan( 0, $user->getId(), 'Test user should have a real user ID' );
	}

	public function testUserCanBeLoadedFromDatabaseById(): void {
		$user = $this->getTestUser()->getUser();

		$loaded = User::newFromId( $user->getId() );
		$loaded->load();

		$this->assertSame( $user->getName(), $loaded->getName(), 'User loaded by ID should match the original' );
	}

}
