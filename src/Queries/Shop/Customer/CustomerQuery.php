<?php

namespace Wyzo\GraphQLAPI\Queries\Shop\Customer;

use Wyzo\Core\Repositories\CountryStateRepository;
use Wyzo\GraphQLAPI\Queries\BaseFilter;
use Wyzo\Sales\Repositories\InvoiceRepository;

class CustomerQuery extends BaseFilter
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected InvoiceRepository $invoiceRepository,
        protected CountryStateRepository $countryStateRepository
    ) {}

    /**
     * Returns a current customer data.
     *
     * @return \Wyzo\Customer\Contracts\Customer
     */
    public function get()
    {
        return wyzo_graphql()->authorize();
    }

    /**
     * Filter the query by the given input.
     */
    public function getTransactions(mixed $rootValue, array $args)
    {
        return $this->invoiceRepository->whereHas('order', function ($q) use ($args) {
            $q->where('customer_id', $args['customer_id']);
        })->get();
    }

    /**
     * Return the country name for the address
     */
    public function getCountryName(object $address): string
    {
        return core()->country_name($address->country);
    }

    /**
     * Return the state name for the address
     */
    public function getStateName(object $address): ?string
    {
        return $this->countryStateRepository->findOneWhere([
            'country_code' => $address->country,
            'code'         => $address->state,
        ])?->default_name;
    }
}
