<?php
namespace WoohooLabsTest\Yin\JsonApi\Schema;

use PHPUnit_Framework_TestCase;
use WoohooLabs\Yin\JsonApi\Schema\Links;
use WoohooLabs\Yin\JsonApi\Schema\LinksTrait;

class LinksTraitTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getLinks()
    {
        $links = new Links("http://example.com/api");

        $linksTrait = $this->createLinksTrait()->setLinks($links);
        $this->assertEquals($links, $linksTrait->getLinks());
    }

    /**
     * @return \WoohooLabs\Yin\JsonApi\Schema\LinksTrait
     */
    private function createLinksTrait()
    {
        return $this->getObjectForTrait(LinksTrait::class);
    }
}
