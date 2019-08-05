<?php


namespace App\Http\Controllers\Accounting\Documents;

use App\Models\Accounting\Documents\MemOrder;
use App\Models\Accounting\Documents\ReceivedServiceItem;
use App\Models\Expense\Vendor;
use App\Models\Setting\AccountChart;
use App\Models\Setting\Currency;
use App\Models\Setting\Service;
use App\Models\Setting\Unit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;



class MemorialOrder extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {

        $currencies = Currency::enabled()->orderBy('name')->pluck('name', 'code');

        $currency = Currency::where('code', '=', setting('general.default_currency'))->first();


        return view('accounting.documents.memorial_order.create', compact('currencies'));
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
        $memorialOrder = new MemOrder();

        $memorialOrder->company_id = session('company_id');
        $memorialOrder->date = $request->date;
        $memorialOrder->document_number = $request->document_number;
        $memorialOrder->currency_code = $request->currency_code;
        $memorialOrder->currency_rate = $request->currency_rate;
        $memorialOrder->previous_day_rate = boolval($request->previous_day_rate);
        if ($request->previous_day_rate) {
            $memorialOrder->currency_rate = Currency::where('code', '=', $request->currency_code)->first()->rate;
        }
        $memorialOrder->description = $request->description;

        $memorialOrder->save();



        $message = trans('messages.success.added', ['type' => 'Memorial Order']);

        flash($message)->success();

        return redirect()->back();
    }


}
