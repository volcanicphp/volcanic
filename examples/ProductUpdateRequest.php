<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $productId = $this->route('product') ?? $this->route('id');

        return [
            'name' => 'sometimes|string|max:255|unique:products,name,'.$productId,
            'description' => 'sometimes|string|min:10',
            'price' => 'sometimes|numeric|min:0.01',
            'category_id' => 'sometimes|exists:categories,id',
            'tags' => 'sometimes|array|max:10',
            'tags.*' => 'string|max:50',
            'status' => 'sometimes|string|in:active,inactive,draft',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A product with this name already exists.',
            'description.min' => 'The description must be at least 10 characters.',
            'price.min' => 'The price must be greater than 0.',
        ];
    }
}
