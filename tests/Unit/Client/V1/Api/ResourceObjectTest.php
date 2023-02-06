<?php

declare(strict_types=1);

namespace Reverb\tests\Client\V1\Api;

use Reverb\Client\V1\Api\Resource;
use PHPUnit\Framework\TestCase;
use Reverb\Client\V1\Api\ResourceObject;
use Tightenco\Collect\Support\Collection;
use WoohooLabs\Yang\JsonApi\Schema\Link\RelationshipLinks as YangRelationshipLinks;
use WoohooLabs\Yang\JsonApi\Schema\Link\ResourceLinks as YangResourceLinks;
use WoohooLabs\Yang\JsonApi\Schema\Relationship as YangRelationship;
use WoohooLabs\Yang\JsonApi\Schema\Resource\ResourceObject as YangResourceObject;
use WoohooLabs\Yang\JsonApi\Schema\Resource\ResourceObjects as YangResourceObjects;

class ResourceObjectTest extends TestCase
{
    /**
     * Tests that a Resource Object returns the expected properties taken from
     * the WoohooLabs\Yang\JsonApi\Schema\Resource\ResourceObject.
     */
    public function testResourceObjectProperties(): void
    {
        $yangResourceObject = new YangResourceObject(
            'examples',
            '1',
            ['x' => 'y'],
            new YangResourceLinks([]),
            ['name' => 'Example'],
            []
        );

        $resourceObject = new ResourceObject($yangResourceObject);

        $this->assertEquals('examples', $resourceObject->type());
        $this->assertEquals('1', $resourceObject->id());
        $this->assertEquals('examples.1', $resourceObject->key());
        $this->assertEquals(['name' => 'Example'], $resourceObject->attributes());
        $this->assertEquals(['x' => 'y'], $resourceObject->meta());
    }

    /**
     * Tests that Resource relations are hydrated from the supplied resources.
     */
    public function testResourceObjectRelationsFormat(): void
    {
        $childrenRelationship = new YangRelationship(
            'children',
            [],
            new YangRelationshipLinks([]),
            [['type' => 'examples', 'id' => '1'], ['type' => 'examples', 'id' => '2']],
            new YangResourceObjects([], [], true),
            true
        );

        $parentRelationship = new YangRelationship(
            'parent',
            [],
            new YangRelationshipLinks([]),
            [['type' => 'examples', 'id' => '1']],
            new YangResourceObjects([], [], true),
            true
        );

        $yangResourceObject = new YangResourceObject(
            'examples',
            '1',
            [],
            new YangResourceLinks([]),
            [],
            [$childrenRelationship, $parentRelationship]
        );

        $object = new ResourceObject($yangResourceObject);

        $expected = new Collection([
            'parent' => [
                ['type' => 'examples', 'id' => '1'],
            ],
            'children' => [
                ['type' => 'examples', 'id' => '1'],
                ['type' => 'examples', 'id' => '2'],
            ],
        ]);

        $this->assertEquals($expected, $object->relations());
    }
}
