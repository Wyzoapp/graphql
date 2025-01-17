<?php

namespace Wyzo\GraphQLAPI\Mutations\Shop\Common;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Wyzo\Core\Rules\PhoneNumber;
use Wyzo\GraphQLAPI\Validators\CustomException;
use Wyzo\Shop\Mail\ContactUs;

class ContactUsMutation extends Controller
{
    /**
     * Subscribe a newly resource in storage.
     *
     * @return array
     *
     * @throws CustomException
     */
    public function sendContactUsEmail(mixed $rootValue, array $args)
    {
        wyzo_graphql()->validate($args, [
            'name'    => ['required', 'string', 'max:50'],
            'email'   => ['required', 'email'],
            'contact' => ['required', new PhoneNumber()],
            'message' => ['required', 'string', 'max:500'],
        ]);

        try {
            Mail::queue(new ContactUs($args));

            return [
                'success' => true,
                'message' => trans('wyzo_graphql::app.shop.contact-us.thanks-for-contact'),
            ];
        } catch (TransportException $e) {
            throw new CustomException(trans('wyzo_graphql::app.email.configuration-error'));
        } catch (\Exception $e) {
            throw new CustomException($e->getMessage());
        }
    }
}
