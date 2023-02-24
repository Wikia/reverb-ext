<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\Tests\Unit\V1\Hydrators;

use Hydrawiki\Reverb\Client\V1\Api\Document;
use Hydrawiki\Reverb\Client\V1\Api\ResourceObject;
use Hydrawiki\Reverb\Client\V1\Hydrators\ResourceHydrator;
use Hydrawiki\Reverb\Client\V1\Hydrators\ResourceFactory;
use Hydrawiki\Reverb\Client\V1\Resources\Resource;
use Hydrawiki\Reverb\Client\V1\Collections\Resources;
use PHPUnit\Framework\TestCase;
use Tightenco\Collect\Support\Collection;

class ResourceHydratorTest extends TestCase
{
    /**
     * Tests that when a Document is a single primary resource document that a
     * single hydrated Resource is returned.
     */
    public function testHydratorReturnsSinglePrimaryResource(): void
    {
        $primaryResource = $this->createMock(Resource::class);

        $resourceObject = $this->createMock(ResourceObject::class);
        $resourceObject->method('relations')->willReturn(new Resources());
        $resourceObject->method('key')->willReturn('resources.1');

        $document = $this->createMock(Document::class);
        $document->method('allResources')->willReturn(new Resources([
            $resourceObject,
        ]));

        $document->method('primaryResources')->willReturn(new Resources([
            $resourceObject,
        ]));

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->method('make')->willReturn($primaryResource);

        $hydrator = new ResourceHydrator($resourceFactory);

        $document->method('isOne')->willReturn(true);

        $this->assertEquals($primaryResource, $hydrator->hydrate($document));
    }

    /**
     * Tests that when a document has many primary resources (even if the
     * document only has one of many) that a Collection of resources is
     * returned.
     */
    public function testHydratorReturnsCollectionForManyDocument(): void
    {
        $primaryResource = $this->createMock(Resource::class);

        $resourceObject = $this->createMock(ResourceObject::class);
        $resourceObject->method('relations')->willReturn(new Resources());
        $resourceObject->method('key')->willReturn('resources.1');

        $document = $this->createMock(Document::class);
        $document->method('allResources')->willReturn(new Resources([
            $resourceObject,
        ]));

        $document->method('primaryResources')->willReturn(new Resources([
            $resourceObject,
        ]));

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->method('make')->willReturn($primaryResource);

        $hydrator = new ResourceHydrator($resourceFactory);

        $document->method('isOne')->willReturn(false);

        $this->assertEquals(
            (new Resources())->wrap($primaryResource),
            $hydrator->hydrate($document)
        );
    }

    /**
     * Tests that the Resource's relations are hydrated with the relation's
     * Resource.
     */
    public function testRelationsAreHydrated(): void
    {
        $resource1 = $this->createMock(Resource::class);
        $resource2 = $this->createMock(Resource::class);

        $resourceObject1 = $this->createMock(ResourceObject::class);
        $resourceObject1->method('key')->willReturn('resources.1');
        $resourceObject1->method('relations')->willReturn(new Resources([
            'children' => [
                ['type' => 'resources', 'id' => '1'],
                ['type' => 'resources', 'id' => '2'],
            ],
            'parent' => [
                ['type' => 'resources', 'id' => '1'],
            ],
        ]));

        $resourceObject2 = $this->createMock(ResourceObject::class);
        $resourceObject2->method('key')->willReturn('resources.2');
        $resourceObject2->method('relations')->willReturn(new Resources());

        $document = $this->createMock(Document::class);
        $document->method('allResources')->willReturn(new Resources([
            $resourceObject1,
            $resourceObject2,
        ]));

        $resourceFactory = $this->createMock(ResourceFactory::class);
		$resourceFactory->expects($this->any())->method('make')
			->willReturnOnConsecutiveCalls($resource1, $resource2);

        $resource1->expects($this->once())
            ->method('setId')
            ->willReturn($resource1);

        $resource1->expects($this->once())
            ->method('setAttributes')
            ->willReturn($resource1);

        $resource1->expects($this->once())
            ->method('setMeta')
            ->willReturn($resource1);

        $resource1->expects($this->once())
            ->method('setRelations')
            ->with([
                'children' => [
                    $resource1,
                    $resource2,
                ],
                'parent' => [
                    $resource1,
                ],
            ])
            ->willReturn($resource1);

        (new ResourceHydrator($resourceFactory))->hydrate($document);
    }
}
