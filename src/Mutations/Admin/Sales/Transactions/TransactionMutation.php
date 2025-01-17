<?php

namespace Wyzo\GraphQLAPI\Mutations\Admin\Sales\Transactions;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Wyzo\Admin\Http\Controllers\Controller;
use Wyzo\GraphQLAPI\Validators\CustomException;
use Wyzo\Payment\Facades\Payment;
use Wyzo\Sales\Models\Invoice;
use Wyzo\Sales\Models\Order;
use Wyzo\Sales\Repositories\InvoiceRepository;
use Wyzo\Sales\Repositories\OrderRepository;
use Wyzo\Sales\Repositories\OrderTransactionRepository;
use Wyzo\Sales\Repositories\ShipmentRepository;

class TransactionMutation extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected ShipmentRepository $shipmentRepository,
        protected OrderTransactionRepository $orderTransactionRepository
    ) {}

    /**
     * Store a newly created resource in storage.
     *
     * @return array
     *
     * @throws CustomException
     */
    public function store(mixed $rootValue, array $args, GraphQLContext $context)
    {
        $paymentMethods = collect(Payment::getPaymentMethods())->pluck('method')->toArray();

        wyzo_graphql()->validate($args, [
            'invoice_id'     => 'required',
            'payment_method' => 'required|in:'.implode(',', $paymentMethods),
            'amount'         => 'required|numeric',
        ]);

        $invoice = $this->invoiceRepository->find($args['invoice_id']);

        if (! $invoice) {
            throw new CustomException(trans('wyzo_graphql::app.admin.sales.invoices.not-found'));
        }

        $transactionAmtBefore = $this->orderTransactionRepository
            ->where('invoice_id', $invoice->id)
            ->sum('amount');

        $transactionAmtFinal = $args['amount'] + $transactionAmtBefore;

        if ($invoice->state == 'paid') {
            throw new CustomException(trans('wyzo_graphql::app.admin.sales.transactions.already-paid'));
        }

        if ($transactionAmtFinal > $invoice->base_grand_total) {
            throw new CustomException(trans('wyzo_graphql::app.admin.sales.transactions.amount-exceed'));
        }

        if ($args['amount'] <= 0) {
            throw new CustomException(trans('wyzo_graphql::app.admin.sales.transactions.zero-amount'));
        }

        $order = $this->orderRepository->find($invoice->order_id);

        $transaction = $this->orderTransactionRepository->create([
            'transaction_id' => bin2hex(random_bytes(20)),
            'type'           => $args['payment_method'],
            'payment_method' => $args['payment_method'],
            'invoice_id'     => $invoice->id,
            'order_id'       => $invoice->order_id,
            'amount'         => $args['amount'],
            'status'         => 'paid',
            'data'           => json_encode([
                'paidAmount' => $args['amount'],
            ]),
        ]);

        $transactionTotal = $this->orderTransactionRepository
            ->where('invoice_id', $invoice->id)
            ->sum('amount');

        if ($transactionTotal >= $invoice->base_grand_total) {
            $shipments = $this->shipmentRepository->findOneByField('order_id', $invoice->order_id);

            $status = isset($shipments)
                ? Order::STATUS_COMPLETED
                : Order::STATUS_PROCESSING;

            $this->orderRepository->updateOrderStatus($order, $status);

            $this->invoiceRepository->updateState($invoice, Invoice::STATUS_PAID);
        }

        return [
            'success'     => true,
            'message'     => trans('wyzo_graphql::app.admin.sales.transactions.zero-amount'),
            'transaction' => $transaction,
        ];
    }
}
