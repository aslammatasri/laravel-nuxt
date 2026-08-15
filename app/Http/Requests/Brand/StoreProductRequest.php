<?php

namespace App\Http\Requests\Brand;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       return [
            'name'            => 'required|string|max:255',
            'description'     => 'required|string',
            'price'           => 'required|numeric|min:0',
            'category'        => 'required|string',
            'commission_rate' => 'required|numeric|min:0',
            'commission_type' => 'required|in:fixed,percentage',
            'campaign_start'  => 'required|date',
            'campaign_end'    => 'required|date|after:campaign_start',
            'max_affiliates'  => 'nullable|integer|min:1',
            'images'          => 'nullable|array',
            'images.*'        => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'target_niches'   => 'nullable|array',
        ];
    }
}
