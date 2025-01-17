<?php

namespace Wyzo\GraphQLAPI\Queries\Admin\Setting;

use Illuminate\Database\Eloquent\Builder;
use Wyzo\GraphQLAPI\Queries\BaseFilter;

class FilterExchangeRate extends BaseFilter
{
    /**
     * Filter the query by the given input.
     */
    public function __invoke(Builder $query, array $input): Builder
    {
        if (isset($input['currency'])) {
            $query = $query->whereHas('currency', function ($q) use ($input) {
                $q->where('name', $input['currency']);
            });

            unset($input['currency']);
        }

        return $query->where($input);
    }
}
