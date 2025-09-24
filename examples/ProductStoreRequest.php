<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255|unique:products,name',
            'description' => 'required|string|min:10',
            'price' => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:categories,id',
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
            'name.required' => 'The product name is required.',
            'name.unique' => 'A product with this name already exists.',
            'description.min' => 'The description must be at least 10 characters.',
            'price.min' => 'The price must be greater than 0.',
        ];
    }
}
