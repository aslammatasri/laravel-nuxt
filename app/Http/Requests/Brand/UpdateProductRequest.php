<?php

namespace App\Http\Requests\Brand;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
             'name'            => 'sometimes|string|max:255',
             'description'     => 'sometimes|string',
             'price'           => 'sometimes|numeric|min:0',
             'category'        => 'sometimes|string',
             'commission_rate' => 'sometimes|numeric|min:0',
             'commission_type' => 'sometimes|in:fixed,percentage',
             'campaign_start'  => 'sometimes|date',
             'campaign_end'    => 'sometimes|date|after:campaign_start',
             'max_affiliates'  => 'nullable|integer|min:1',
             'status'          => 'sometimes|in:active,paused,closed',
             'images'          => 'nullable|array',
             'images.*'        => 'image|mimes:jpeg,png,jpg,webp|max:2048',
             'delete_images'   => 'nullable|array',
             'delete_images.*' => 'string',
         ];
    }
}
