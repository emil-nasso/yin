<?php
namespace WoohooLabs\Yin\Examples\Book\JsonApi\Document;

use WoohooLabs\Yin\Examples\Book\JsonApi\Resource\BookResourceTransformer;
use WoohooLabs\Yin\JsonApi\Schema\Link;
use WoohooLabs\Yin\JsonApi\Schema\Links;
use WoohooLabs\Yin\JsonApi\Transformer\AbstractSingleResourceDocument;

class BookDocument extends AbstractSingleResourceDocument
{
    /**
     * @param \WoohooLabs\Yin\Examples\Book\JsonApi\Resource\BookResourceTransformer $transformer
     */
    public function __construct(BookResourceTransformer $transformer)
    {
        parent::__construct($transformer);
    }

    /**
     * @return \WoohooLabs\Yin\JsonApi\Schema\JsonApi|null
     */
    public function getJsonApi()
    {
        return null;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return [];
    }

    /**
     * @return \WoohooLabs\Yin\JsonApi\Schema\Links|null
     */
    public function getLinks()
    {
        return new Links(
            [
                "self" => new Link("http://example.com/api/books/" . $this->transformer->getId($this->resource))
            ]
        );
    }
}