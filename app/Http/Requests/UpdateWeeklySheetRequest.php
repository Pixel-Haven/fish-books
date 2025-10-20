<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeeklySheetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only OWNER can update weekly sheets
        return $this->user() && $this->user()->role === 'OWNER';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_date' => ['sometimes', 'date'],
            'to_date' => ['sometimes', 'date', 'after_or_equal:from_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'in:DRAFT,FINALIZED'],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'to_date.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
