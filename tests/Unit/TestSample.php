<?php

namespace Rumur\WordPress\CosmoUsers\Tests\Unit;

class TestSample extends TestCase {

    public function testExample(): void {
        $this->assertTrue( __return_true() );
    }

	public function testSecondExample(): void {
		$this->assertFalse( __return_false() );
	}
}
