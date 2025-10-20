<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Both OWNER and MANAGER can create trips
        return in_array($this->user()->role, ['OWNER', 'MANAGER']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'vessel_id' => ['required', 'exists:vessels,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'vessel_id.required' => 'Please select a vessel.',
            'vessel_id.exists' => 'Selected vessel does not exist.',
            'date.required' => 'Trip date is required.',
            'date.before_or_equal' => 'Trip date cannot be in the future.',
        ];
    }
}
