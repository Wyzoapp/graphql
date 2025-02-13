<?php

namespace Wyzo\GraphQLAPI\Mutations\Shop\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Wyzo\Core\Repositories\SubscribersListRepository;
use Wyzo\Customer\Repositories\CustomerGroupRepository;
use Wyzo\Customer\Repositories\CustomerRepository;
use Wyzo\GraphQLAPI\Validators\CustomException;

class RegistrationMutation extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected SubscribersListRepository $subscriptionRepository
    ) {
        Auth::setDefaultDriver('api');
    }

    /**
     * Method to store user's sign up form data to DB.
     *
     * @return array
     */
    public function signUp(mixed $rootValue, array $args, GraphQLContext $context)
    {
        wyzo_graphql()->validate($args, [
            'email'      => 'email|required|unique:customers,email',
            'first_name' => 'string|required',
            'last_name'  => 'string|required',
            'password'   => 'min:6|required|confirmed',
        ]);

        $this->create($args);

        if (core()->getConfigData('customer.settings.email.verification')) {
            return [
                'success' => false,
                'message' => trans('wyzo_graphql::app.shop.customers.signup.success-verify'),
            ];
        }

        return $this->login($args);
    }

    /**
     * Method to store user's sign up form data to DB.
     *
     * @return array
     *
     * @throws CustomException
     */
    public function socialSignIn(mixed $rootValue, array $args, GraphQLContext $context)
    {
        wyzo_graphql()->validate($args, [
            'email'       => 'email|required',
            'first_name'  => 'string|required',
            'last_name'   => 'string|required',
            'signup_type' => 'string|required',
        ]);

        if ($args['signup_type'] == 'truecaller') {
            wyzo_graphql()->validate($args, [
                'phone' => 'required',
            ]);

            $customer = $this->customerRepository->findOneByField('phone', $args['phone']);

            if (
                $customer
                && $customer->email != $args['email']
            ) {
                throw new CustomException(
                    trans('wyzo_graphql::app.shop.customers.login.validation.unique', ['phone' => $args['phone']])
                );
            }
        } else {
            $customer = $this->customerRepository->findOneByField('email', $args['email']);
        }

        $args['password'] = $args['password_confirmation'] = $password = rand(000000, 999999);

        if (! $customer) {
            $customer = $this->create($args);
        }

        return $this->login($args, $password);
    }

    /**
     * Method to store user's sign up form data to DB.
     *
     * @param  array  $data
     * @return \Wyzo\Customer\Contracts\Customer
     */
    public function create($data)
    {
        $data = array_merge($data, [
            'api_token'                 => Str::random(80),
            'customer_group_id'         => $this->customerGroupRepository->findOneByField('code', 'general')->id,
            'is_verified'               => ! core()->getConfigData('customer.settings.email.verification'),
            'password'                  => bcrypt($data['password']),
            'subscribed_to_news_letter' => $data['subscribed_to_news_letter'] ?? 0,
            'token'                     => md5(uniqid(rand(), true)),
        ]);

        Event::dispatch('customer.registration.before');

        $customer = $this->customerRepository->create($data);

        if (! $customer) {
            return [
                'success' => false,
                'message' => trans('wyzo_graphql::app.shop.customers.signup.error-registration'),
            ];
        }

        if (! empty($data['subscribed_to_news_letter'])) {
            Event::dispatch('customer.subscription.before');

            $subscription = $this->subscriptionRepository->updateOrCreate([
                'email' => $data['email'],
            ], [
                'channel_id'    => core()->getCurrentChannel()->id,
                'is_subscribed' => 1,
                'token'         => uniqid(),
                'customer_id'   => $customer->id,
            ]);

            Event::dispatch('customer.subscription.after', $subscription);
        }

        Event::dispatch('customer.registration.after', $customer);

        return $customer;
    }

    /**
     * Method to login user after registration.
     *
     * @return array
     *
     * @throws CustomException
     */
    public function login($data, $password = null)
    {
        if (! $jwtToken = JWTAuth::attempt([
            'email'    => $data['email'],
            'password' => $data['password_confirmation'],
        ], $data['remember'] ?? 0)
        ) {
            throw new CustomException(trans('wyzo_graphql::app.shop.customers.login.invalid-creds'));
        }

        $loginCustomer = auth()->user();

        if (! empty($password)) {
            $this->customerRepository->update([
                'password' => $password,
            ], $loginCustomer->id);
        } else {
            request()->merge([
                'token' => $jwtToken,
            ]);

            if (! $loginCustomer->status) {
                auth()->logout();

                return [
                    'success' => false,
                    'message' => trans('wyzo_graphql::app.shop.customers.login.not-activated'),
                ];
            }

            if (! $loginCustomer->is_verified) {
                Cookie::queue(Cookie::make('enable-resend', 'true', 1));

                Cookie::queue(Cookie::make('email-for-resend', $loginCustomer->email, 1));

                auth()->logout();

                return [
                    'success' => false,
                    'message' => trans('wyzo_graphql::app.shop.customers.login.verify-first'),
                ];
            }
        }

        Event::dispatch('customer.after.login', $loginCustomer);

        return [
            'success'      => true,
            'message'      => trans('wyzo_graphql::app.shop.customers.success-login'),
            'access_token' => "Bearer $jwtToken",
            'token_type'   => 'Bearer',
            'expires_in'   => Auth::factory()->getTTL() * 60,
            'customer'     => $this->customerRepository->find($loginCustomer->id),
        ];
    }
}
