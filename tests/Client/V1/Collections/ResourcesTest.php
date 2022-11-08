<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\Tests\Unit\V1\Collections;

use Hydrawiki\Reverb\Client\V1\Collections\Resources;
use PHPUnit\Framework\TestCase;

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
