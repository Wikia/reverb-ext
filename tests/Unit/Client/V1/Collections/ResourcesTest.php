<?php

declare(strict_types=1);

namespace Reverb\tests\Client\V1\Collections;

use PHPUnit\Framework\TestCase;
use Reverb\Client\V1\Collections\Resources;

class ResourcesTest extends TestCase
{
    /**
     * Tests that meta data is retained after mapping.
     */
    public function testMetaIsRetainedAfterMap(): void
    {
        $resources = new Resources;
        $resources->setMeta(['x' => 'y']);

        $mapped = $resources->map(function ($resource) {
            return $resource;
        });

        $this->assertSame(['x' => 'y'], $mapped->meta());
    }
}
