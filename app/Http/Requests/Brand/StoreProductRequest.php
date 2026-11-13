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
            'platforms'            => 'nullable|array',
            'platforms.*'          => 'in:youtube,tiktok,instagram',
            'content_types'        => 'nullable|array',
            'content_types.*'      => 'in:review,unboxing,tutorial,short_video,lifestyle',
            'posts_required'       => 'nullable|integer|min:1|max:20',
            'brief'                => 'nullable|string|max:3000',
            'hashtags'             => 'nullable|string|max:255',
            'product_url'          => 'nullable|url|max:500',
            'min_subscribers'      => 'nullable|integer|min:0|max:100000000',
            'target_niches.*'      => 'string|max:50',
            'free_sample'          => 'nullable|boolean',
            'flat_fee'             => 'nullable|numeric|min:0|max:99999999',
            'application_deadline' => 'nullable|date|before_or_equal:campaign_end',
            'images'          => 'nullable|array',
            'images.*'        => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'target_niches'   => 'nullable|array',
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
            'platforms.*.in'                    => 'Choose from YouTube, TikTok or Instagram.',
            'content_types.*.in'                => 'Choose a valid content type.',
            'posts_required.min'                => 'Posts per creator must be at least 1.',
            'posts_required.max'                => 'Posts per creator cannot be more than 20.',
            'brief.max'                         => 'The brief is too long (max 3000 characters).',
            'product_url.url'                   => 'Enter a full link, for example https://shop.example.com/product.',
            'min_subscribers.integer'           => 'Minimum subscribers must be a whole number.',
            'flat_fee.numeric'                  => 'Flat fee must be an amount.',
            'application_deadline.date'         => 'Application deadline is not a valid date.',
            'application_deadline.before_or_equal' => 'Applications must close on or before the campaign end date.',
            'images.*.image'           => 'Only image files are allowed.',
            'images.*.mimes'           => 'Images must be JPG, PNG or WEBP.',
            'images.*.max'             => 'Each image must be 2 MB or smaller.',
            'images.*.uploaded'        => 'An image failed to upload. It is probably larger than 2 MB.',
        ];
    }
}
