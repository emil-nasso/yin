<?php
namespace WoohooLabsTest\Yin\JsonApi\Transformer;

use PHPUnit_Framework_TestCase;
use WoohooLabs\Yin\JsonApi\Exception\ExceptionFactory;
use WoohooLabs\Yin\JsonApi\Request\Request;
use WoohooLabs\Yin\JsonApi\Request\RequestInterface;
use WoohooLabs\Yin\JsonApi\Schema\Data\DataInterface;
use WoohooLabs\Yin\JsonApi\Schema\Data\SingleResourceData;
use WoohooLabs\Yin\JsonApi\Schema\Link;
use WoohooLabs\Yin\JsonApi\Schema\Links;
use WoohooLabs\Yin\JsonApi\Schema\Relationship\ToOneRelationship;
use WoohooLabs\Yin\JsonApi\Transformer\AbstractResourceTransformer;
use WoohooLabs\Yin\JsonApi\Transformer\Transformation;
use WoohooLabsTest\Yin\JsonApi\Utils\StubResourceTransformer;
use Zend\Diactoros\ServerRequest as DiactorosServerRequest;

class AbstractResourceTransformerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function transformToResourceIdentifierWhenDomainObjectIsNull()
    {
        $domainObject = null;

        $transformer = $this->createTransformer();
        $transformedResourceIdentifier = $transformer->transformToResourceIdentifier($domainObject);
        $this->assertNull($transformedResourceIdentifier);
    }

    /**
     * @test
     */
    public function transformToResourceIdentifierWhenDomainObjectIsNotNull()
    {
        $domainObject = [];
        $type = "user";
        $id = "1";

        $transformer = $this->createTransformer($type, $id);
        $transformedResourceIdentifier = $transformer->transformToResourceIdentifier($domainObject);
        $this->assertEquals($type, $transformedResourceIdentifier["type"]);
        $this->assertEquals($id, $transformedResourceIdentifier["id"]);
        $this->assertArrayNotHasKey("meta", $transformedResourceIdentifier);
    }

    /**
     * @test
     */
    public function transformToResourceIdentifierWithMeta()
    {
        $domainObject = [];
        $meta = ["abc" => "def"];

        $transformer = $this->createTransformer("", "", $meta);
        $transformedResourceIdentifier = $transformer->transformToResourceIdentifier($domainObject);
        $this->assertEquals($meta, $transformedResourceIdentifier["meta"]);
    }

    /**
     * @test
     */
    public function transformToResourceWhenNull()
    {
        $domainObject = null;

        $transformer = $this->createTransformer();
        $transformedResource = $this->transformToResource($transformer, $domainObject);
        $this->assertNull($transformedResource);
    }

    /**
     * @test
     */
    public function transformToResourceWhenAlmostEmpty()
    {
        $domainObject = [];
        $type = "user";
        $id = "1";

        $transformer = $this->createTransformer($type, $id);
        $transformedResource = $this->transformToResource($transformer, $domainObject);
        $this->assertEquals($type, $transformedResource["type"]);
        $this->assertEquals($id, $transformedResource["id"]);
        $this->assertArrayNotHasKey("meta", $transformedResource);
        $this->assertArrayNotHasKey("links", $transformedResource);
        $this->assertArrayNotHasKey("attributes", $transformedResource);
        $this->assertArrayNotHasKey("relationships", $transformedResource);
    }

    /**
     * @test
     */
    public function transformToResourceWithMeta()
    {
        $domainObject = [];
        $meta = ["abc" => "def"];

        $transformer = $this->createTransformer("", "", $meta);
        $transformedResource = $this->transformToResource($transformer, $domainObject);
        $this->assertEquals($meta, $transformedResource["meta"]);
    }

    /**
     * @test
     */
    public function transformToResourceWithLinks()
    {
        $domainObject = [];
        $links = Links::createWithoutBaseUri()->setSelf(new Link("http://example.com/api/users"));

        $transformer = $this->createTransformer("", "", [], $links);
        $transformedResource = $this->transformToResource($transformer, $domainObject);
        $this->assertCount(1, $transformedResource["links"]);
        $this->assertArrayHasKey("self", $transformedResource["links"]);
    }

    /**
     * @test
     */
    public function transformToResourceWithAttributes()
    {
        $domainObject = [
            "name" => "John Doe",
            "age" => 50
        ];
        $attributes = [
            "full_name" => function (array $object, RequestInterface $request) use ($domainObject) {
                $this->assertEquals($object, $domainObject);
                $this->assertInstanceOf(RequestInterface::class, $request);
                return "James Bond";
            },
            "birth" => function (array $object) {
                return 2015 - $object["age"];
            }
        ];

        $transformer = $this->createTransformer("", "", [], null, $attributes);
        $transformedResource = $this->transformToResource($transformer, $domainObject);
        $this->assertEquals("James Bond", $transformedResource["attributes"]["full_name"]);
        $this->assertEquals(2015 - 50, $transformedResource["attributes"]["birth"]);
        $this->assertArrayNotHasKey("name", $transformedResource["attributes"]);
        $this->assertArrayNotHasKey("name", $transformedResource);
        $this->assertArrayNotHasKey("age", $transformedResource["attributes"]);
        $this->assertArrayNotHasKey("age", $transformedResource);
    }

    /**
     * @test
     */
    public function transformToResourceWithDefaultRelationship()
    {
        $domainObject = [
            "name" => "John Doe",
            "age" => 50
        ];
        $defaultRelationships = ["father"];
        $relationships = [
            "father" => function (array $object, RequestInterface $request) use ($domainObject) {
                $this->assertEquals($object, $domainObject);
                $this->assertInstanceOf(RequestInterface::class, $request);

                $relationship = new ToOneRelationship();
                $relationship->setData(["Father Vader"], new StubResourceTransformer("user", "2"));
                return $relationship;
            }
        ];

        $data = new SingleResourceData();
        $transformer = $this->createTransformer("user", "1", [], null, [], $defaultRelationships, $relationships);
        $transformedResource = $this->transformToResource($transformer, $domainObject, null, $data);
        $this->assertArrayHasKey("father", $transformedResource["relationships"]);
        $this->assertEquals("user", $transformedResource["relationships"]["father"]["data"]["type"]);
        $this->assertEquals("2", $transformedResource["relationships"]["father"]["data"]["id"]);
        $this->assertArrayNotHasKey("name", $transformedResource["relationships"]);
        $this->assertArrayNotHasKey("age", $transformedResource["relationships"]);
        $this->assertInternalType("array", $data->getResource("user", "2"));
    }

    /**
     * @test
     */
    public function transformToResourceWithoutIncludedRelationship()
    {
        $defaultRelationships = [];
        $relationships = [
            "father" => function () {
                $relationship = new ToOneRelationship();
                $relationship->setData([], new StubResourceTransformer("user", "2"));
                return $relationship;
            }
        ];
        $request = new Request(new DiactorosServerRequest());
        $request = $request->withQueryParams(["fields" => ["user" => ""]]);

        $data = new SingleResourceData();
        $transformer = $this->createTransformer("user", "1", [], null, [], $defaultRelationships, $relationships);
        $transformedResource = $this->transformToResource($transformer, [], $request, $data);
        $this->assertArrayNotHasKey("relationships", $transformedResource);
        $this->assertNull($data->getResource("user", "2"));
    }

    /**
     * @test
     * @expectedException \WoohooLabs\Yin\JsonApi\Exception\InclusionUnrecognized
     */
    public function transformToResourceWithInvalidRelationship()
    {
        $defaultRelationships = ["father"];
        $relationships = [
            "father" => function () {
                return new ToOneRelationship();
            }
        ];
        $request = new Request(new DiactorosServerRequest());
        $request = $request->withQueryParams(["include" => "mother"]);

        $transformer = $this->createTransformer("user", "1", [], null, [], $defaultRelationships, $relationships);
        $this->transformToResource($transformer, [], $request);
    }

    /**
     * @test
     */
    public function transformToResourceToRelationshipWhenEmpty()
    {
        $defaultRelationships = ["father"];
        $relationships = [];

        $request = new Request(new DiactorosServerRequest());
        $data = new SingleResourceData();
        $transformer = $this->createTransformer("user", "1", [], null, [], $defaultRelationships, $relationships);
        $transformation = new Transformation($request, $data, new ExceptionFactory(), "");
        $transformedResource = $transformer->transformRelationship("father", $transformation, []);
        $this->assertNull($transformedResource);
    }

    /**
     * @test
     */
    public function transformToRelationship()
    {
        $defaultRelationships = ["father"];
        $relationships = [
            "father" => function () {
                $relationship = new ToOneRelationship();
                $relationship->setData(["Father Vader"], new StubResourceTransformer("user", "2"));
                return $relationship;
            }
        ];

        $request = new Request(new DiactorosServerRequest());
        $data = new SingleResourceData();
        $transformer = $this->createTransformer("user", "1", [], null, [], $defaultRelationships, $relationships);
        $transformation = new Transformation($request, $data, new ExceptionFactory(), "");
        $transformedResource = $transformer->transformRelationship("father", $transformation, []);
        $this->assertEquals("user", $transformedResource["data"]["type"]);
        $this->assertEquals("2", $transformedResource["data"]["id"]);
    }

    /**
     * @param \WoohooLabs\Yin\JsonApi\Transformer\AbstractResourceTransformer $transformer
     * @param mixed $domainObject
     * @param \WoohooLabs\Yin\JsonApi\Request\RequestInterface $request
     * @param \WoohooLabs\Yin\JsonApi\Schema\Data\DataInterface $data
     * @return array|null
     */
    protected function transformToResource(
        AbstractResourceTransformer $transformer,
        $domainObject,
        RequestInterface $request = null,
        DataInterface $data = null
    ) {
        $transformation = new Transformation(
            $request ? $request : new Request(new DiactorosServerRequest()),
            $data ? $data : new SingleResourceData(),
            new ExceptionFactory(),
            ""
        );

        return $transformer->transformToResource($transformation, $domainObject);
    }

    /**
     * @param string $type
     * @param string $id
     * @param array $meta
     * @param \WoohooLabs\Yin\JsonApi\Schema\Links|null $links
     * @param array $attributes
     * @param array $defaultRelationships
     * @param array $relationships
     * @return \WoohooLabsTest\Yin\JsonApi\Utils\StubResourceTransformer
     */
    protected function createTransformer(
        $type = "",
        $id = "",
        array $meta = [],
        Links $links = null,
        array $attributes = [],
        array $defaultRelationships = [],
        array $relationships = []
    ) {
        return new StubResourceTransformer(
            $type,
            $id,
            $meta,
            $links,
            $attributes,
            $defaultRelationships,
            $relationships
        );
    }
}
