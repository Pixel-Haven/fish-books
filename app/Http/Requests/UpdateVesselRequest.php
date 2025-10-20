<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVesselRequest extends FormRequest
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
        // Get the vessel ID from the route parameter
        $vessel = $this->route('vessel');
        $vesselId = $vessel instanceof \App\Models\Vessel ? $vessel->id : $vessel;
        
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'registration_no' => ['nullable', 'string', 'max:100', 'unique:vessels,registration_no,' . $vesselId],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
