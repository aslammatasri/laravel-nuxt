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

    /**
     * Friendly validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'            => 'Enter a product name.',
            'description.required'     => 'Add a short description.',
            'price.required'           => 'Enter the product price.',
            'price.numeric'            => 'Price must be a number.',
            'price.min'                => 'Price cannot be negative.',
            'category.required'        => 'Choose a category.',
            'commission_rate.required' => 'Enter the commission rate.',
            'commission_rate.numeric'  => 'Commission must be a number.',
            'campaign_start.required'  => 'Choose a campaign start date.',
            'campaign_start.date'      => 'Start date is not a valid date.',
            'campaign_end.required'    => 'Choose a campaign end date.',
            'campaign_end.date'        => 'End date is not a valid date.',
            'campaign_end.after'       => 'End date must be after the start date.',
            'max_affiliates.integer'   => 'Max affiliates must be a whole number.',
            'max_affiliates.min'       => 'Max affiliates must be at least 1.',
            'images.*.image'           => 'Only image files are allowed.',
            'images.*.mimes'           => 'Images must be JPG, PNG or WEBP.',
            'images.*.max'             => 'Each image must be 2 MB or smaller.',
            'images.*.uploaded'        => 'An image failed to upload. It is probably larger than 2 MB.',
        ];
    }
}
