<?php
namespace WoohooLabs\Yin\JsonApi\Request;

interface RequestInterface
{
    /**
     * @param string $resourceType
     * @return array
     */
    public function getIncludedFields($resourceType);

    /**
     * @param string $resourceType
     * @param string $field
     * @return bool
     */
    public function isIncludedField($resourceType, $field);

    /**
     * @param string $baseRelationshipPath
     * @return array
     */
    public function getIncludedRelationships($baseRelationshipPath);

    /**
     * @param string $baseRelationshipPath
     * @param string $relationshipName
     * @return bool
     */
    public function isIncludedRelationship($baseRelationshipPath, $relationshipName);

    /**
     * @return array
     */
    public function getSorting();

    /**
     * @return array|null
     */
    public function getPagination();

    /**
     * @return array
     */
    public function getFiltering();

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($name, $default = null);

    /**
     * @param string $name
     * @param mixed $default
     * @return array|string
     */
    public function getQueryParam($name, $default = null);
}
