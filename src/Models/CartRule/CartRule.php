<?php

namespace Wyzo\GraphQLAPI\Models\CartRule;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Wyzo\CartRule\Models\CartRule as BaseCartRule;
use Wyzo\CartRule\Models\CartRuleCouponProxy;

class CartRule extends BaseCartRule
{
    /**
     * Get the coupons that owns the cart rule.
     */
    public function cart_rule_coupons(): HasMany
    {
        return $this->hasMany(CartRuleCouponProxy::modelClass());
    }
}
