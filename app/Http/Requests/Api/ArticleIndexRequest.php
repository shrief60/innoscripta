<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ArticleIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Public endpoint - no authentication required
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     * Set default values for optional parameters
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 20),
            'sort' => $this->input('sort', 'published_at'),
            'order' => $this->input('order', 'desc'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'searchTerm' => 'sometimes|string|max:255',
            'source' => 'sometimes|array',
            'source.*' => 'string|max:255',
            'category' => 'sometimes|array',
            'category.*' => 'string|max:255',
            'author' => 'sometimes|string|max:255',
            'from_date' => 'sometimes|date|date_format:Y-m-d',
            'to_date' => 'sometimes|date|date_format:Y-m-d|after_or_equal:from_date',
            'sort' => 'sometimes|in:published_at,created_at,title',
            'order' => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source.array' => 'The source filter must be an array.',
            'category.array' => 'The category filter must be an array.',
            'from_date.date_format' => 'The from_date must be in Y-m-d format (e.g., 2025-01-01).',
            'to_date.date_format' => 'The to_date must be in Y-m-d format (e.g., 2025-01-01).',
            'to_date.after_or_equal' => 'The to_date must be equal to or after from_date.',
            'sort.in' => 'The sort field must be one of: published_at, created_at, title.',
            'order.in' => 'The order field must be either asc or desc.',
            'per_page.min' => 'The per_page must be at least 1.',
            'per_page.max' => 'The per_page cannot exceed 100.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'from_date' => 'start date',
            'to_date' => 'end date',
            'per_page' => 'items per page',
        ];
    }
}
