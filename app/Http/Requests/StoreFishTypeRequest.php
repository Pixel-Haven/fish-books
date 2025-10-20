<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFishTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'OWNER';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:fish_types,name'],
            'default_rate_per_kilo' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'is_active' => ['boolean'],
        ];
    }
}
