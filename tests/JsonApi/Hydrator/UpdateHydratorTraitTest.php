<?php
namespace WoohooLabsTest\Yin\JsonApi\Hydrator;

use PHPUnit_Framework_TestCase;
use WoohooLabs\Yin\JsonApi\Exception\ExceptionFactory;
use WoohooLabs\Yin\JsonApi\Request\Request;
use WoohooLabsTest\Yin\JsonApi\Utils\StubUpdateHydrator;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

class UpdateHydratorTraitTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \WoohooLabs\Yin\JsonApi\Exception\DataMemberMissing
     */
    public function hydrateWhenBodyEmpty()
    {
        $body = [];

        $hydrator = $this->createHydrator();
        $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
    }

    /**
     * @test
     * @expectedException \WoohooLabs\Yin\JsonApi\Exception\ResourceIdMissing
     */
    public function hydrateWhenIdMissing()
    {
        $body = [
            "data" => [
                "type" => "user"
            ]
        ];

        $hydrator = $this->createHydrator();
        $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
    }

    /**
     * @test
     */
    public function hydrateId()
    {
        $id = "1";
        $body = [
            "data" => [
                "type" => "user",
                "id" => $id
            ]
        ];

        $hydrator = $this->createHydrator();
        $domainObject = $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
        $this->assertEquals(["id" => $id], $domainObject);
    }

    private function createRequest(array $body)
    {
        $psrRequest = new ServerRequest();
        $psrRequest = $psrRequest
            ->withParsedBody($body)
            ->withBody(new Stream("php://memory", "rw"));
        $psrRequest->getBody()->write(json_encode($body));

        $request = new Request($psrRequest);

        return $request;
    }

    /**
     * @return \WoohooLabs\Yin\JsonApi\Hydrator\UpdateHydratorTrait
     */
    private function createHydrator()
    {
        return new StubUpdateHydrator();
    }
}
