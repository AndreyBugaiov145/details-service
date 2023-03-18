<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalogueDetailRequest;
use App\Models\DetailAnalogue;
use App\Services\ApiResponseServices;

class AnalogueDetails extends Controller
{
    public function create(AnalogueDetailRequest $request)
    {
        try {
            $detailAnalogue = DetailAnalogue::create($request->all());
        } catch (\Exception $e) {
            \Log::warning($e->getMessage(),$e->getTrace());

            return ApiResponseServices::fail('Something was wrong , try again later');
        }

        return ApiResponseServices::successCustomData($detailAnalogue->toArray());
    }

    public function delete($id)
    {
        DetailAnalogue::where('id', $id)->delete();

        return ApiResponseServices::successCustomData();
    }
}
