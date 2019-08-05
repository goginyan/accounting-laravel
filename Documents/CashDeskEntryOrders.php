<?php

namespace App\Http\Controllers\Accounting\Documents;

use App\Models\Accounting\Documents\CashDeskEntryOrder;
use App\Models\Accounting\Documents\Operation;
use App\Models\Expense\Vendor;
use App\Models\Setting\AccountChart;
use App\Models\Setting\Currency;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CashDeskEntryOrders extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $vendors = Vendor::enabled()->orderBy('name')->pluck('name', 'id');

        $currencies = Currency::enabled()->orderBy('name')->pluck('name', 'code');

        $currency = Currency::where('code', '=', setting('general.default_currency'))->first();

        $charts_of_accounts = [];

        $accounts = AccountChart::where('company_id', session('company_id'))->where('selectable', true)->orderBy('code')->get();
        foreach ($accounts as $account) {
            $charts_of_accounts[$account->id] = $account->code . ' ' . $account->name;
        }

        return view('accounting.documents.cash_desk_entry_orders.create', compact('vendors', 'currencies', 'currency', 'charts_of_accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $order = new CashDeskEntryOrder();

        $order->company_id = session('company_id');
        $order->cash_desk = $request->cash_desk;
        $order->date = $request->date;
        $order->document_number = $request->document_number;
        $order->receipt_number = $request->receipt_number;
        $order->related_account = $request->related_account;
        $order->currency = $request->currency;
        $order->amount = $request->amount;
        $order->currency_amount = $request->currency_amount;
        $order->partner_id = $request->partner_id;
        $order->received = $request->received;
        $order->basis = $request->basis;
        $order->attached = $request->attached;

        $order->save();

        if ($request->operation) {
            foreach ($request->operation as $operation) {
                $new_operation = new Operation();

                $new_operation->company_id = session('company_id');
                $new_operation->document_id = $order->id;
                $new_operation->document_type = 'CashDeskEntryOrder';
                $new_operation->debit = $operation['debit'];
                $new_operation->debtor_id = $operation['debtor_id'];
                $new_operation->debit_currency = $operation['debit_currency'];
                $new_operation->credit = $operation['credit'];
                $new_operation->creditor_id = $operation['creditor_id'];
                $new_operation->credit_currency = $operation['credit_currency'];
                $new_operation->amount = $operation['amount'];
                $new_operation->currency_amount = $operation['currency_amount'];
                $new_operation->description = $operation['description'];

                $new_operation->save();
            }
        }

        $message = trans('messages.success.added', ['type' => 'Cash Desk Entry Order']);

        flash($message)->success();

        return redirect()->back();
    }
}
