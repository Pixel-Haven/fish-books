<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $trip = $this->route('trip');
        
        // Cannot edit closed trips
        if ($trip && $trip->status === 'CLOSED') {
            return $this->user()->role === 'OWNER';
        }
        
        return in_array($this->user()->role, ['OWNER', 'MANAGER']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'vessel_id' => ['sometimes', 'required', 'exists:vessels,id'],
            'date' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'in:DRAFT,ONGOING,CLOSED'],
        ];
    }
}
