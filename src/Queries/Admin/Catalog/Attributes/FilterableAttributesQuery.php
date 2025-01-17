<?php

namespace Wyzo\GraphQLAPI\Queries\Admin\Catalog\Attributes;

use Wyzo\Attribute\Repositories\AttributeRepository;
use Wyzo\GraphQLAPI\Queries\BaseFilter;

class FilterableAttributesQuery extends BaseFilter
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected AttributeRepository $attributeRepository) {}

    /**
     * Get the filterable attributes.
     *
     * @return \Wyzo\Attribute\Contracts\Attribute
     */
    public function getFilterableAttributes()
    {
        return $this->attributeRepository->findByField('is_filterable', 1);
    }
}
