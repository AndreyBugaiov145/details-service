<?php

namespace App\Http\Requests;

use App\Services\ApiResponseServices;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(ApiResponseServices::fail($validator->errors()->first()));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string',
            's_number' => 'required',
            'price' => 'numeric',
            'us_shipping_price' => 'integer',
            'ua_shipping_price' => 'integer',
            'price_markup' => 'integer',
            'stock' => 'integer',
            'category_id' => 'required|integer',
            'analogy_details' => 'nullable|array',
        ];
    }
}
