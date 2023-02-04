<?php

namespace App\Services;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponseServices
{
    public static function success(JsonResource $resource = null, int $status_code = 200, string $massage = '')
    {
        if (!is_null($resource)) {

            return $resource;
        }

        $data['status'] = true;
        $data['data'] = [];
        $data['message'] = $massage;
        $data['type'] = 'success';

        return response()->json($data, $status_code);
    }

    public static function successCustomData($customData = false, int $status_code = 200, string $massage = 'success'): \Illuminate\Http\JsonResponse
    {
        $data['status'] = true;
        $data['data'] = $customData;
        $data['message'] = $massage;
        $data['type'] = 'success';

        return response()->json($data, $status_code);
    }

    public static function fail($errors, string $type = 'danger'): \Illuminate\Http\JsonResponse
    {
        $data['message'] = $errors;
        $data['type'] = $type;
        $data['status'] = false;

        return response()->json($data);
    }
}
