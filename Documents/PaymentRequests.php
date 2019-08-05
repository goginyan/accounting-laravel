<?php

namespace App\Http\Controllers\Accounting\Documents;

use App\Models\Accounting\Documents\Operation;
use App\Models\Accounting\Documents\PaymentRequest;
use App\Models\Expense\Vendor;
use App\Models\Setting\AccountChart;
use App\Models\Setting\Currency;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentRequests extends Controller
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

        return view('accounting.documents.payment_requests.create', compact('vendors', 'currencies', 'currency', 'charts_of_accounts'));
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
        $payment_requests = new PaymentRequest();

        $payment_requests->company_id = session('company_id');
        $payment_requests->date = $request->date;
        $payment_requests->document_number = $request->document_number;
        $payment_requests->registration_date = $request->registration_date;
        $payment_requests->company_name = setting('general.company_name');
        $payment_requests->company_tax_code = $request->company_tax_code;
        $payment_requests->debit_account = $request->debit_account;
        $payment_requests->debit_bank = $request->debit_bank;
        $payment_requests->debit_currency = $request->debit_currency;
        $payment_requests->partner_id = $request->partner_id;
        $payment_requests->credit_account = $request->credit_account;
        $payment_requests->credit_bank = $request->credit_bank;
        $payment_requests->related_account = $request->related_account;
        $payment_requests->amount = $request->amount;
        $payment_requests->currency_amount = $request->currency_amount;
        $payment_requests->payment_objective = $request->payment_objective;

        $payment_requests->save();

        if ($request->operation) {
            foreach ($request->operation as $operation) {
                $new_operation = new Operation();

                $new_operation->company_id = session('company_id');
                $new_operation->document_id = $payment_requests->id;
                $new_operation->document_type = 'PaymentRequest';
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

        $message = trans('messages.success.added', ['type' => 'Payment Request']);

        flash($message)->success();

        return redirect()->back();
    }
}
