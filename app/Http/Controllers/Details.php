<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetailRequest;
use App\Models\Currency;
use App\Models\Detail;
use App\Models\DetailAnalogue;
use App\Services\ApiResponseServices;
use Illuminate\Http\Request;

class Details extends Controller
{
    public function getCategoryDetails($category_id)
    {

        return ApiResponseServices::successCustomData(optional(Detail::where('category_id', $category_id)->get())->toArray());
    }

    public function getAnalogyDetails($id)
    {
        return ApiResponseServices::successCustomData(optional(DetailAnalogue::where('detail_id', $id)->get())->toArray());
    }

    public function getDetail($id)
    {
        return ApiResponseServices::successCustomData(optional(Detail::find($id))->toArray());
    }

    public function create(DetailRequest $request)
    {
        try {
            $currency = Currency::where('code', Currency::UAH_CODE)->first();
            $data = array_merge($request->all(), ['currency_id' => $currency->id, 'is_parsing_analogy_details' => true, 'is_manual_added' => true]);
            $detail = Detail::create($data);

            if ($request->has('analogy_details')) {
                $analogy_details_data = array_map(function ($item) use ($detail) {
                    $item['detail_id'] = $detail->id;
                }, $request->get('analogy_details'));
                DetailAnalogue::upsert($analogy_details_data, [], ['brand', 'model', 'years', 'detail_id']);
            }
        } catch (\Exception $e) {
            \Log::warning($e->getMessage(), $e->getTrace());

            return ApiResponseServices::fail('Something was wrong , try again later');
        }

        return ApiResponseServices::successCustomData($detail->toArray());
    }

    public function update(DetailRequest $request, $id)
    {
        try {
            $data = $request->all();
            $detail = Detail::find($id);
            $detail->update($data);

            Detail::where([['title', $request->get('title')], ['s_number', $request->get('s_number')]])
                ->update([
                    'title' => $request->get('title'),
                    'short_description' => $request->get('short_description'),
                    'stock' => $request->get('stock'),
                    'price' => $request->get('price'),
                    'us_shipping_price' => $request->get('us_shipping_price'),
                    'ua_shipping_price' => $request->get('ua_shipping_price'),
                    'price_markup' => $request->get('price_markup'),
                ]);

        } catch (\Exception $e) {
            return ApiResponseServices::fail('Something was wrong , try again later');
        }

        return ApiResponseServices::successCustomData($detail->toArray());
    }

    public function delete($id)
    {
        Detail::where('id', $id)->delete();

        return ApiResponseServices::successCustomData();
    }

    public function search(Request $request)
    {
        $details = \DB::table('details')->where('s_number', 'like', $request->get('search') . '%')
            ->orWhere('interchange_numbers', 'like', $request->get('search') . '%')->get();

        $detailsData = $details->groupBy(function ($item) {
            return $item->title . '-' . $item->s_number;
        })->map(function ($details) {
            return $details[0];
        });

        $sortedDetails = collect();

        foreach ($detailsData as $detail) {
            if ($detail->s_number == $request->get('search') || $detail->interchange_numbers == $request->get('search')) {
                $sortedDetails->prepend($detail);
            } else {
                $sortedDetails->push($detail);
            }
        }

        return ApiResponseServices::successCustomData(Detail::hydrate($sortedDetails->toArray())->toArray());
    }
}
