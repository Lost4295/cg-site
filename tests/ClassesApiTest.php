<?php

namespace App\Tests;

final class ClassesApiTest extends ApiTestCase
{
    public function testClassesAreAvailableThroughApiPlatform(): void
    {

        $classes = $this->request('GET', '/api/classes');

        $this->assertStatus(200);
        $this->assertSame('1A1', $classes[0]['value']);
    }
}
