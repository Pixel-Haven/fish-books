<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeeklySheetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only OWNER can create weekly sheets
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
            'vessel_id' => ['required', 'string', 'exists:vessels,id'],
            'week_start' => ['required', 'date', function ($attribute, $value, $fail) {
                $date = \Carbon\Carbon::parse($value);
                if ($date->dayOfWeek !== 6) { // 6 = Saturday
                    $fail('The week must start on a Saturday.');
                }
            }],
            'week_end' => ['required', 'date', 'after:week_start'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'vessel_id.required' => 'Please select a vessel.',
            'vessel_id.exists' => 'The selected vessel does not exist.',
            'week_start.required' => 'Week start date is required.',
            'week_start.date' => 'Week start must be a valid date.',
            'week_end.after' => 'The week end date must be after the start date.',
        ];
    }
}
