<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFishTypeRequest extends FormRequest
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
        // Get the fish type ID from the route parameter
        $fishType = $this->route('fish_type');
        $fishTypeId = $fishType instanceof \App\Models\FishType ? $fishType->id : $fishType;
        
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:fish_types,name,' . $fishTypeId],
            'default_rate_per_kilo' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999.99'],
            'is_active' => ['boolean'],
        ];
    }
}
