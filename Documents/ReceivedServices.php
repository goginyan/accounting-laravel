<?php

namespace App\Http\Controllers\Accounting\Documents;

use App\Models\Accounting\Documents\Operation;
use App\Models\Accounting\Documents\ReceivedService;
use App\Models\Accounting\Documents\ReceivedServiceItem;
use App\Models\Expense\Vendor;
use App\Models\Income\Customer;
use App\Models\Setting\AccountChart;
use App\Models\Setting\Currency;
use App\Models\Setting\Service;
use App\Models\Setting\Unit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReceivedServices extends Controller
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

        $services = Service::where('company_id', session('company_id'))->orderBy('code')->pluck('code', 'id');
        $services->put(0, 'Select');
        $service_names = Service::where('company_id', session('company_id'))->orderBy('code')->pluck('name', 'id');

        $units = Unit::where('company_id', session('company_id'))->orderBy('code')->pluck('name', 'id');

        return view('accounting.documents.received_services.create', compact('vendors', 'currencies', 'currency', 'charts_of_accounts', 'services', 'service_names', 'units'));
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
        $receivedService = new ReceivedService();

        $receivedService->company_id = session('company_id');
        $receivedService->date = $request->date;
        $receivedService->document_number = $request->document_number;
        $receivedService->currency_code = $request->currency_code;
        $receivedService->currency_rate = $request->currency_rate;
        $receivedService->previous_day_rate = boolval($request->previous_day_rate);
        if ($request->previous_day_rate) {
            $receivedService->currency_rate = Currency::where('code', '=', $request->currency_code)->first()->rate;
        }
        $receivedService->vendor_id = $request->vendor_id;
        $receivedService->vendor_name = $request->vendor_name;
        $receivedService->vendor_email = $request->vendor_email;
        $receivedService->vendor_tax_number = $request->vendor_tax_number;
        $receivedService->vendor_phone = $request->vendor_phone;
        $receivedService->vendor_address = $request->vendor_address;
        $receivedService->vendor_account = $request->vendor_account;
        $receivedService->given_prepay_account = $request->given_prepay_account;
        $receivedService->export_type = $request->export_type;
        $receivedService->acquisition_document_number = $request->acquisition_document_number;
        $receivedService->service_acquisition_type = $request->service_acquisition_type;
        $receivedService->vat_calculation_type = $request->vat_calculation_type;
        $receivedService->acquisition_document_number = $request->acquisition_document_number;
        $receivedService->include_vat = boolval($request->include_vat);
        $receivedService->description = $request->description;

        $receivedService->save();

        if ($request->item) {
            foreach ($request->item as $item) {
                $new_item = new ReceivedServiceItem();

                $new_item->company_id = session('company_id');
                $new_item->received_service_id = $receivedService->id;
                $new_item->service_code = $item['service_code'];
                $new_item->name = $item['name'];
                $new_item->unit = $item['unit'];
                $new_item->quantity = $item['quantity'];
                $new_item->price = $item['price'];
                $new_item->amount = $item['amount'];
                $new_item->vat = boolval(isset($item['vat']) ? $item['vat'] : false);
                $new_item->chart_id = $item['chart_id'];

                $new_item->save();
            }
        }

        if ($request->operation) {
            foreach ($request->operation as $operation) {
                $new_operation = new Operation();

                $new_operation->company_id = session('company_id');
                $new_operation->document_id = $receivedService->id;
                $new_operation->document_type = 'ReceivedService';
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

        $message = trans('messages.success.added', ['type' => 'Received Service']);

        flash($message)->success();

        return redirect()->back();
    }

    public function addItem(Request $request)
    {
        $item_row = $request['item_row'];

        $charts_of_accounts = [];

        $services = Service::where('company_id', session('company_id'))->orderBy('code')->pluck('code', 'id');

        $service_names = Service::where('company_id', session('company_id'))->orderBy('code')->pluck('name', 'id');

        $accounts = AccountChart::where('company_id', session('company_id'))->where('selectable', true)->orderBy('code')->get();
        foreach ($accounts as $account) {
            $charts_of_accounts[$account->id] = $account->code . ' ' . $account->name;
        }

        $units = Unit::where('company_id', session('company_id'))->orderBy('code')->pluck('name', 'id');

        $html = view('accounting.documents.received_services.item', compact('item_row', 'services', 'service_names', 'charts_of_accounts', 'units'))->render();

        return response()->json([
            'success' => true,
            'error'   => false,
            'data'    => [
                'units' => $units,
                'services' => $services,
                'charts_of_accounts' => $charts_of_accounts
            ],
            'message' => 'null',
            'html'    => $html,
        ]);
    }

    public function addOperation(Request $request)
    {
        $operation_row = $request['operation_row'];

        $charts_of_accounts = [];

        $accounts = AccountChart::where('company_id', session('company_id'))->where('selectable', true)->orderBy('code')->get();
        foreach ($accounts as $account) {
            $charts_of_accounts[$account->id] = $account->code . ' ' . $account->name;
        }

        $vendors = Vendor::enabled()->orderBy('name')->pluck('name', 'id');

        $customers = Customer::enabled()->orderBy('name')->pluck('name', 'id');

        $currencies = Currency::enabled()->orderBy('name')->pluck('name', 'code');

        $html = view('accounting.documents.received_services.operation', compact('operation_row', 'charts_of_accounts', 'vendors', 'customers', 'currencies'))->render();

        return response()->json([
            'success' => true,
            'error'   => false,
            'data'    => [
            ],
            'message' => 'null',
            'html'    => $html,
        ]);
    }
}
