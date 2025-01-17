<?php

namespace Wyzo\GraphQLAPI\Mutations\Shop\Common;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Event;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Wyzo\Core\Repositories\SubscribersListRepository;
use Wyzo\Customer\Repositories\CustomerRepository;
use Wyzo\GraphQLAPI\Validators\CustomException;

class NewsletterSubscriberMutation extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected SubscribersListRepository $subscriptionRepository,
        protected CustomerRepository $customerRepository
    ) {}

    /**
     * Subscribe a newly resource in storage.
     *
     * @return array
     *
     * @throws CustomException
     */
    public function subscribe(mixed $rootValue, array $args, GraphQLContext $context)
    {
        wyzo_graphql()->validate($args, [
            'email' => 'email|required',
        ]);

        $subscription = $this->subscriptionRepository->findOneByField('email', $args['email']);

        if ($subscription) {
            throw new CustomException(trans('wyzo_graphql::app.shop.subscription.already-subscribed'));
        }

        try {
            Event::dispatch('customer.subscription.before');

            $customer = $this->customerRepository->findOneByField('email', $args['email']);

            $subscription = $this->subscriptionRepository->create([
                'email'         => $args['email'],
                'channel_id'    => core()->getCurrentChannel()->id,
                'is_subscribed' => 1,
                'token'         => uniqid(),
                'customer_id'   => $customer?->id,
            ]);

            if ($customer) {
                $customer->subscribed_to_news_letter = 1;

                $customer->save();
            }

            Event::dispatch('customer.subscription.after', $subscription);

            return [
                'success'      => true,
                'message'      => trans('wyzo_graphql::app.shop.subscription.subscribe-success'),
            ];
        } catch (\Exception $e) {
            throw new CustomException($e->getMessage());
        }
    }
}
