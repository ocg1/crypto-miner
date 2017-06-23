<?php

namespace App\Http\Controllers\Panel\Admin\Api;

use DB;
use App\Exceptions\GeneralException;
use App\{Currency, Investment, Revenue, Log};
use App\Http\Controllers\Controller;

class RevenueController extends Controller
{

    public function store()
    {
        $json     = request()->json();
        $currency = $json->get('currency');
        $amount   = $json->get('amount');

        $currency = Currency::name($currency)->firstOrFail();

        if (!$currency->is_crypto) {
            throw new GeneralException(100);
        }

        $transfered = false;
        DB::transaction(function() use ($currency, $amount, &$transfered) {
            $percentages = investors($currency->id);
            foreach ($percentages as $userId => $percentage) {
                Revenue::create([
                    'currency_id' => $currency->id,
                    'amount'      => $amount * $percentage,
                    'user_id'     => $userId,
                    'percentage'  => $percentage,
                ]);
            }

            Log::create([
                'currency_id' => $currency->id,
                'amount'      => $amount,
            ]);

            $transfered = true;
        });

        return response()->json(compact('transfered'));
    }
}
