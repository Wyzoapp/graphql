<?php

namespace Wyzo\GraphQLAPI\Mutations\Shop\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Wyzo\GraphQLAPI\Validators\CustomException;

class SessionMutation extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        Auth::setDefaultDriver('api');
    }

    /**
     * Login user resource in storage.
     *
     * @return array
     *
     * @throws CustomException
     */
    public function login(mixed $rootValue, array $args, GraphQLContext $context)
    {
        wyzo_graphql()->validate($args, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (! $jwtToken = JWTAuth::attempt([
            'email'    => $args['email'],
            'password' => $args['password'],
        ], $args['remember'] ?? 0)) {
            throw new CustomException(trans('wyzo_graphql::app.shop.customers.login.invalid-creds'));
        }

        try {
            $customer = wyzo_graphql()->authorize(token: $jwtToken);

            /**
             * Event passed to prepare cart after login.
             */
            Event::dispatch('customer.after.login', $customer);

            return [
                'success'      => true,
                'message'      => trans('wyzo_graphql::app.shop.customers.success-login'),
                'access_token' => "Bearer $jwtToken",
                'token_type'   => 'Bearer',
                'expires_in'   => Auth::factory()->getTTL() * 60,
                'customer'     => $customer,
            ];
        } catch (\Exception $e) {
            throw new CustomException($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return array
     */
    public function logout()
    {
        $customer = wyzo_graphql()->authorize();

        auth()->logout();

        Event::dispatch('customer.after.logout', $customer->id);

        return [
            'success' => true,
            'message' => trans('wyzo_graphql::app.shop.customers.success-logout'),
        ];
    }
}
