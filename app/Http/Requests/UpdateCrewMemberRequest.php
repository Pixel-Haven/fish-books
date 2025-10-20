<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCrewMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Both OWNER and MANAGER can update crew members
        // Only OWNER can change 'active' status if member has payouts
        return in_array($this->user()->role, ['OWNER', 'MANAGER']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get the crew member ID from the route parameter
        $crewMember = $this->route('crew_member');
        $crewMemberId = $crewMember instanceof \App\Models\CrewMember ? $crewMember->id : $crewMember;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'local_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'id_card_no' => ['nullable', 'string', 'max:50', 'unique:crew_members,id_card_no,' . $crewMemberId],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Crew member name is required.',
            'id_card_no.unique' => 'This ID card number is already registered.',
        ];
    }
}
