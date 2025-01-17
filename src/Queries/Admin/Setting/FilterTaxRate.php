<?php

namespace Wyzo\GraphQLAPI\Queries\Admin\Setting;

use Illuminate\Database\Eloquent\Builder;
use Wyzo\GraphQLAPI\Queries\BaseFilter;

class FilterTaxRate extends BaseFilter
{
    /**
     * Filter the query by the given input.
     */
    public function __invoke(Builder $query, array $input): Builder
    {
        if (
            isset($arguments['state'])
            && $input['state'] == '*'
        ) {
            $query = $query->where(function ($q) {
                $q->where('state', '');
            });

            unset($input['state']);
        }

        return $query->where($input);
    }
}
