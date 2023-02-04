<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class Rate extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currency = Currency::create([
            'code' => Currency::USD_CODE,
            'rate' => '1',
            'symbol' => '$',
        ]);

        Currency::create([
            'code' => Currency::UAH_CODE,
            'rate' => '40',
            'symbol' => '₴',
            'against_id' => $currency->id,
        ]);
    }
}
