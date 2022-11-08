<?php

declare(strict_types=1);

namespace Hydrawiki\Reverb\Client\Tests\Unit\V1\Resources;

use Hydrawiki\Reverb\Client\V1\Exceptions\ResourceAlreadyPopulated;
use Hydrawiki\Reverb\Client\V1\Exceptions\ResourceAttributeUndefined;
use Hydrawiki\Reverb\Client\V1\Exceptions\ResourceRelationshipUndefined;
use Hydrawiki\Reverb\Client\V1\Resources\Resource;
use Hydrawiki\Reverb\Client\Tests\Unit\V1\Mocks\MockResource;
use PHPUnit\Framework\TestCase;
use Tightenco\Collect\Support\Collection;

class ResourceTest extends TestCase
{
    /**
     * Tests that defined attributes have their values populated from the
     * values passed in to a Resource.
     */
    public function testResourceAttributeValuesArePopulated(): void
    {
        $values = [
            'defined' => 'Example',
        ];

        $resource = new class($values) extends Resource {
            protected $attributes = [
                'defined' => null,
            ];
        };

        $this->assertSame('Example', $resource->defined);
    }

    /**
     * Tests that metadata is added to a Resource.
     */
    public function testResourceMetaDataIsProvided(): void
    {
        $resource = new class() extends Resource {
        };
        $resource->setMeta([
            'x' => 'y',
        ]);

        $this->assertSame(['x' => 'y'], $resource->meta());
        $this->assertSame('y', $resource->meta('x'));
    }

    /**
     * Tests that attributes are populated using the `setAttributes` method
     * after the Resource has been created.
     */
    public function testAttributesArePopulatedAfterCreation(): void
    {
        $resource = new class() extends Resource {
            protected $attributes = [
                'name' => null,
            ];
        };

        $resource->setAttributes(['name' => 'Example']);
        $this->assertEquals('Example', $resource->name);
    }

    /**
     * Tests that attributes cannot be set if the attributes were given values
     * through the Resource constructor -- a developer should use `update` to
     * change attributes.
     */
    public function testAttributesCannotBePopulatedIfPopulatedDuringCreation(): void
    {
        $attributes = [
            'name' => 'Example',
        ];

        $resource = new class($attributes) extends Resource {
            protected $attributes = [
                'name' => null,
            ];
        };

        $this->expectException(ResourceAlreadyPopulated::class);

        $resource->setAttributes($attributes);
    }

    /**
     * Tests that attributes cannot be populated if they have already been
     * populated.
     */
    public function testAttributesCannotBePopulatedMoreThanOnce(): void
    {
        $resource = new class() extends Resource {
            protected $attributes = [
                'name' => null,
            ];
        };

        $this->expectException(\Exception::class);

        $resource->setAttributes(['name' => 'Example']);
        $resource->setAttributes(['name' => 'Example']);
    }

    /**
     * Tests that when an attribute has a default value the default value is
     * used when no value is provided.
     */
    public function testResourceAttributeValuesExposeDefaults(): void
    {
        $resource = new class() extends Resource {
            protected $attributes = [
                'hasDefault' => 'defaultValue',
            ];
        };

        $this->assertSame('defaultValue', $resource->hasDefault);
    }

    /**
     * Tests that when a value is provided for an undefined attribute that the
     * value is ignored and not merged into the attributes.
     */
    public function testUndefinedAttributesAreNotMerged(): void
    {
        $values = [
            'undefined' => 'Example',
        ];

        $resource = new class($values) extends Resource {
            protected $attributes = [
                'defined' => null,
            ];
        };

        $this->expectException(ResourceAttributeUndefined::class);

        $resource->unpermitted;
    }

    /**
     * Tests that an attribute can be accessed using a normalized key, where
     * dashes have been replaced with underscores.
     */
    public function testAttributeIsAccessibleWithNormalizedKey(): void
    {
        $resource = new class() extends Resource {
            protected $attributes = [
                'normalized-key' => 'value',
            ];
        };

        $this->assertSame('value', $resource->normalized_key);
    }

    /**
     * Tests that attribute values are updated and attributes that have not been
     * provided remain with their original values.
     */
    public function testAttributesAreUpdated(): void
    {
        $resource = new class() extends Resource {
            protected $attributes = [
                'name'     => 'Initialised Name',
                'hostname' => 'example.com',
                'counter'  => 1,
            ];
        };

        $resource->update([
            'name'    => 'Changed Name',
            'counter' => 2,
        ]);

        $this->assertSame('Changed Name', $resource->name);
        $this->assertSame('example.com', $resource->hostname);
        $this->assertSame(2, $resource->counter);
    }

    /**
     * Tests that later updates to an attribute replace earlier, providing a
     * single value per attribute.
     */
    public function testSubsequentAttributeChangesOverwriteEarlier(): void
    {
        $resource = new class() extends Resource {
            protected $attributes = [
                'name'     => 'Initialised Name',
                'hostname' => 'example.com',
            ];
        };

        $resource->update([
            'name'     => 'First Changed Name',
            'hostname' => 'First Hostname Change',
        ]);

        $resource->update([
            'name' => 'Second Changed Name',
        ]);

        $this->assertSame([
            'name'     => 'Second Changed Name',
            'hostname' => 'First Hostname Change',
        ], $resource->attributes());
    }

    /**
     * Tests that when an attribute value is changed that it is exposed through
     * the `changes()` method.
     */
    public function testChangedAttributeValuesAreProvidedAsChanges(): void
    {
        $resource = new class() extends Resource {
            protected $attributes = [
                'name'     => 'Initialised Name',
                'hostname' => 'example.com',
            ];
        };

        $resource->update([
            'name' => 'Changed Name',
        ]);

        $this->assertSame(['name' => 'Changed Name'], $resource->changes());
    }

    /**
     * Tests that all attribute values and attribute defaults are combined to
     * produce a full set of attributes.
     */
    public function testAllAttributesAreProvidedAsAttributes(): void
    {
        $resource = new class() extends Resource {
            protected $attributes = [
                'name'     => 'Initialised Name',
                'hostname' => 'example.com',
            ];
        };

        $resource->setAttributes([
            'hostname' => 'changed.com',
        ]);

        $this->assertSame([
            'name'     => 'Initialised Name',
            'hostname' => 'changed.com',
        ], $resource->attributes());
    }

    /**
     * Tests that the Resource ID is set.
     */
    public function testResourceIdIsSet(): void
    {
        $resource = new class() extends Resource {
        };
        $resource->setId('1');

        $this->assertSame('1', $resource->id());
    }

    /**
     * Tests that a resource can have an ID (string) or not have an ID (null).
     */
    public function testResourceIdIsOptional(): void
    {
        $with = new class() extends Resource {
        };
        $with->setId('1');

        $without = new class() extends Resource {
        };

        $this->assertSame('1', $with->id());
        $this->assertNull($without->id());
    }

    /**
     * Tests that Relations are populated.
     */
    public function testRelationsArePopulated(): void
    {
        $resource = new class() extends Resource {
            protected $relationships = [
                'children' => [Resource::class, self::RELATIONSHIP_MANY],
                'parent'   => [Resource::class, self::RELATIONSHIP_ONE],
            ];
        };

        $relation = new class() extends Resource {
        };

        $resource->setRelations([
            'children' => [$relation, $relation, $relation],
            'parent'   => $relation,
        ]);

        $this->assertCount(3, $resource->children());
        $this->assertInstanceOf(Resource::class, $resource->parent());
    }

    /**
     * Tests that a toOne relationship returns a single Resource, or null when
     * no relation has been provided for the relationship.
     */
    public function testResourceToOneRelationshipReturnsOne(): void
    {
        $relations = [
            'parent' => new class() extends Resource {
            },
        ];

        $resource = new class() extends Resource {
            protected $relationships = [
                'parent' => [Resource::class, self::RELATIONSHIP_ONE],
                'child'  => [Resource::class, self::RELATIONSHIP_ONE],
            ];
        };

        $resource->setRelations($relations);

        $this->assertInstanceOf(Resource::class, $resource->parent());
        $this->assertNull($resource->child());
    }

    /**
     * Tests that a toMany relationship returns an array of Resources, or an
     * empty array when no relation has been provided for the relationship.
     */
    public function testResourceToManyRelationshipReturnsMany(): void
    {
        $relations = [
            'children' => [
                new class() extends Resource {
                },
                new class() extends Resource {
                },
            ],
        ];

        $resource = new class() extends Resource {
            protected $relationships = [
                'children' => [Resource::class, self::RELATIONSHIP_MANY],
                'parents'  => [Resource::class, self::RELATIONSHIP_MANY],
            ];
        };

        $resource->setRelations($relations);

        $this->assertCount(2, $resource->children());
        $this->assertEquals(new Collection(), $resource->parents());
    }

    /**
     * Tests that an undefined relationship exception is thrown when an attempt
     * is made to access a relationship that has not been defined.
     */
    public function testResourceRelationshipUndefinedThrowsException(): void
    {
        $resource = new class() extends Resource {
        };

        $this->expectException(ResourceRelationshipUndefined::class);

        $resource->undefinedRelationship();
    }

    /**
     * Tests that new relations are added to a relationship.
     */
    public function testNewRelationsAreAdded(): void
    {
        $resource = new class() extends Resource {
            protected $relationships = [
                'children' => [Resource::class, self::RELATIONSHIP_MANY],
                'parent'  => [Resource::class, self::RELATIONSHIP_ONE],
            ];
        };

        $parent = new class() extends Resource {
            protected $type = 'parent';
        };

        $child  = new class() extends Resource {
            protected $type = 'child';
        };

        $resource->add('parent', $parent);
        $resource->add('children', $child);
        $resource->add('children', $child, $child);

        $this->assertEquals(new Collection([$child, $child, $child]), $resource->children());
        $this->assertEquals($parent, $resource->parent());
    }

    /**
     * Tests that a Resource's properties are rendered for a JSON:API.
     */
    public function testResourceIsRenderedForApi(): void
    {
        $resource = new MockResource([
            'name' => 'John Doe',
        ]);

        $a = (new MockResource)->setId('a');
        $b = (new MockResource)->setId('b');
        $c = (new MockResource)->setId('c');

        $resource->add('mock', $a);
        $resource->add('mocks', $b, $c);

        $expected = [
            'data' => [
                'type' => 'mocks',
                'attributes' => [
                    'name' => 'John Doe',
                ],
                'relationships' => [
                    'mock' => [
                        'data' => ['type' => 'mocks', 'id' => 'a'],
                    ],
                    'mocks' => [
                        ['data' => ['type' => 'mocks', 'id' => 'b']],
                        ['data' => ['type' => 'mocks', 'id' => 'c']],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $resource->toData());
    }

    /**
     * Tests that when a Resource has an ID that the ID is included in the
     * properties rendered for a JSON:API.
     */
    public function testPersistedResourceIsRenderedForApiWithId(): void
    {
        $resource = (new MockResource([
            'name' => 'John Doe',
        ]))->setId('a');

        $expected = [
            'data' => [
                'type' => 'mocks',
                'id' => 'a',
                'attributes' => [
                    'name' => 'John Doe',
                ],
            ],
        ];

        $this->assertEquals($expected, $resource->toData());
    }
}
