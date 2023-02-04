<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParsingSettingRequest;
use App\Models\ParsingSetting;
use App\Services\ApiResponseServices;

class ParsingSettingController extends Controller
{

    public function index()
    {
        return ApiResponseServices::successCustomData(ParsingSetting::get()->toArray());
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ParsingSettingRequest $request)
    {
        ParsingSetting::firstOrCreate($request->all());

        return ApiResponseServices::successCustomData();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     */
    public function update(ParsingSettingRequest $request, $id)
    {
        ParsingSetting::where('id', $id)
            ->update($request->only('brand','year_from','year_to'));

        return ApiResponseServices::successCustomData();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     */
    public function destroy($id)
    {
        ParsingSetting::where('id', $id)
            ->delete();

        return ApiResponseServices::successCustomData();
    }
}
