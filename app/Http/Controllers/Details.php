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
            $currency = Currency::where('code', Currency::UAH_CODE);
            $data = array_merge($request->all(), ['currency_id' => $currency->id, 'is_parsing_analogy_details' => true]);
            $detail = Detail::create($data);

            if ($request->has('analogy_details')) {
                $analogy_details_data = array_map(function ($item) use ($detail) {
                    $item['detail_id'] = $detail->id;
                }, $request->get('analogy_details'));
                DetailAnalogue::upsert($analogy_details_data, [], ['brand', 'model', 'years', 'detail_id']);
            }
        } catch (\Exception $e) {
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

            if ($request->has('analogy_details')) {
                $analogy_details_data = array_map(function ($item) use ($detail) {
                    $item['detail_id'] = $detail->id;
                }, $request->get('analogy_details'));
                DetailAnalogue::upsert($analogy_details_data, ['id'], ['brand', 'model', 'years', 'detail_id']);
            }
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
}
