<?php

declare(strict_types=1);

namespace Reverb\tests\Client\V1\Api;

use PHPUnit\Framework\TestCase;
use Reverb\Client\V1\Api\Document;
use Reverb\Client\V1\Api\ResourceObject;
use WoohooLabs\Yang\JsonApi\Schema\Document as YangDocument;

class DocumentTest extends TestCase
{
    /**
     * Tests that `allResources()` provides both primary resources and included
     * resources.
     */
    public function testDocumentProvidesPrimaryAndIncludedResources(): void
    {
        $yangDocument = YangDocument::fromArray([
            'data' => [
                [
                    'type' => 'resources',
                    'id'   => '1',
                ],
                [
                    'type' => 'resources',
                    'id'   => '2',
                ],
            ],
            'included' => [
                [
                    'type' => 'resources',
                    'id'   => '3',
                ],
                [
                    'type' => 'resources',
                    'id'   => '4',
                ],
            ],
        ]);

        $document = new Document($yangDocument);

        $this->assertCount(4, $document->allResources());
    }

    /**
     * Tests that when a Document contains many primary resources that
     * `primaryResources()` returns all of them.
     */
    public function testDocumentProvidesPrimaryResources(): void
    {
        $yangDocument = YangDocument::fromArray([
            'data' => [
                [
                    'type' => 'resources',
                    'id'   => '1',
                ],
                [
                    'type' => 'resources',
                    'id'   => '2',
                ],
            ],
            'included' => [
                [
                    'type' => 'resources',
                    'id'   => '3',
                ],
                [
                    'type' => 'resources',
                    'id'   => '4',
                ],
            ],
        ]);

        $document = new Document($yangDocument);

        $this->assertCount(2, $document->primaryResources());
    }

    /**
     * Tests that when a Document contains a single primary resource that
     * the resource is provided.
     */
    public function testDocumentProvidesPrimaryResource(): void
    {
        $yangDocument = YangDocument::fromArray([
            'data' => [
                'type' => 'resources',
                'id'   => '1',
            ],
        ]);

        $document = new Document($yangDocument);

        $this->assertCount(1, $document->primaryResources());
    }

    /**
     * Tests that Yang ResourceObjects are wrapped in our own ResourceObject
     * class.
     */
    public function testResourcesAreWrappedAsResourceObjects(): void
    {
        $yangDocument = YangDocument::fromArray([
            'data' => [
                [
                    'type' => 'resources',
                    'id'   => '1',
                ],
            ],
            'included' => [
                [
                    'type' => 'resources',
                    'id'   => '3',
                ],
            ],
        ]);

        $document = new Document($yangDocument);

        $this->assertInstanceOf(ResourceObject::class, $document->primaryResources()->first());
        $this->assertInstanceOf(ResourceObject::class, $document->includedResources()->first());
    }

    /**
     * Tests that a Document is correctly labelled as having one Primary
     * resource.
     */
    public function testIsOnePrimaryResource(): void
    {
        $yangDocument = YangDocument::fromArray([
            'data' => [
                'type' => 'resources',
                'id'   => '1',
            ],
        ]);

        $document = new Document($yangDocument);

        $this->assertTrue($document->isOne());
        $this->assertFalse($document->isMany());
    }

    /**
     * Tests that a Document is correctly labelled as having many Primary
     * resources.
     */
    public function testIsManyPrimaryResource(): void
    {
        $yangDocument = YangDocument::fromArray([
            'data' => [
                [
                    'type' => 'resources',
                    'id'   => '1',
                ],
            ],
        ]);

        $document = new Document($yangDocument);

        $this->assertTrue($document->isMany());
        $this->assertFalse($document->isOne());
    }

    /**
     * Tests that when a Document has meta it is available on the Resources
     * Collection.
     */
    public function testResourcesFromDocumentOfManyHaveMeta(): void
    {
        $yangDocument = YangDocument::fromArray([
            'data' => [
                [
                    'type' => 'resources',
                    'id'   => '1',
                ],
            ],
            'meta' => [
                'x' => 'y',
                '1' => '2',
            ],
        ]);

        $document = new Document($yangDocument);

        $expected = ['x' => 'y', '1' => '2'];
        $meta = $document->primaryResources()->meta();

        $this->assertEquals($expected, $meta);
    }
}
