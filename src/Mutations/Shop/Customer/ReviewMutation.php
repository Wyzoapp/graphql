<?php

namespace Wyzo\GraphQLAPI\Mutations\Shop\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Wyzo\GraphQLAPI\Validators\CustomException;
use Wyzo\Product\Repositories\ProductReviewAttachmentRepository;
use Wyzo\Product\Repositories\ProductReviewRepository;

class ReviewMutation extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductReviewRepository $productReviewRepository,
        protected ProductReviewAttachmentRepository $productReviewAttachmentRepository
    ) {
        Auth::setDefaultDriver('api');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return array
     *
     * @throws CustomException
     */
    public function store(mixed $rootValue, array $args, GraphQLContext $context)
    {
        wyzo_graphql()->validate($args, [
            'comment'     => 'required',
            'rating'      => 'required|numeric|min:1|max:5',
            'title'       => 'required',
            'product_id'  => 'required',
            'attachments' => 'array',
        ]);

        try {
            if (auth()->check()) {
                $customer = auth()->user();

                $args = array_merge($args, [
                    'customer_id' => $customer->id,
                    'name'        => $customer->name,
                ]);
            }

            $args['status'] = 'pending';

            $attachments = $args['attachments'];

            $review = $this->productReviewRepository->create($args);

            foreach ($attachments as $attachment) {
                if (! empty($attachment['upload_type'])) {
                    if ($attachment['upload_type'] == 'base64') {
                        $attachment['save_path'] = "review/{$review->id}";

                        $records = wyzo_graphql()->storeReviewAttachment($attachment);

                        $this->productReviewAttachmentRepository->create([
                            'path'      => $records['path'],
                            'review_id' => $review->id,
                            'type'      => $records['img_details'][0],
                            'mime_type' => $records['img_details'][1],
                        ]);
                    }
                }
            }

            return [
                'success' => true,
                'message' => trans('wyzo_graphql::app.shop.customers.reviews.create-success'),
                'review'  => $review,
            ];
        } catch (\Exception $e) {
            throw new CustomException($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return array
     *
     * @throws CustomException
     */
    public function delete(mixed $rootValue, array $args, GraphQLContext $context)
    {
        $customer = wyzo_graphql()->authorize();

        $review = $this->productReviewRepository->findOneWhere([
            'id'          => $args['id'],
            'customer_id' => $customer->id,
        ]);

        if (! $review) {
            return [
                'success' => false,
                'message' => trans('wyzo_graphql::app.shop.customers.reviews.not-found'),
                'reviews' => $customer->reviews,
            ];
        }

        Event::dispatch('customer.review.delete.before', $args['id']);

        $isDeleted = $review->delete();

        Event::dispatch('customer.review.delete.after', $args['id']);

        return [
            'success' => $isDeleted,
            'message' => $isDeleted
                ? trans('wyzo_graphql::app.shop.customers.reviews.delete-success')
                : trans('wyzo_graphql::app.shop.customers.reviews.not-found'),
            'reviews' => $customer->reviews,
        ];
    }

    /**
     * Remove all resource based on condition from storage.
     *
     * @return array
     *
     * @throws CustomException
     */
    public function deleteAll(mixed $rootValue, array $args, GraphQLContext $context)
    {
        $customer = wyzo_graphql()->authorize();

        try {
            $customerReviews = $customer->reviews;

            foreach ($customerReviews as $review) {
                Event::dispatch('customer.review.delete.before', $review->id);

                $this->productReviewRepository->delete($review->id);

                Event::dispatch('customer.review.delete.after', $review->id);
            }

            return [
                'status'  => $customerReviews->count() ? true : false,
                'message' => $customerReviews->count()
                    ? trans('wyzo_graphql::app.shop.customers.reviews.mass-delete-success')
                    : trans('wyzo_graphql::app.shop.customers.reviews.not-found'),
            ];
        } catch (\Exception $e) {
            throw new CustomException($e->getMessage());
        }
    }
}
