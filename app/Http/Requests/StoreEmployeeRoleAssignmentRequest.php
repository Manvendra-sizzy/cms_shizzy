<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRoleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_profile_id' => ['required', 'integer', 'exists:employee_profiles,id'],
            'role_key' => ['required', 'string', 'in:project_manager,finance_manager,hr_manager,developer'],
            'all_systems' => ['nullable', 'boolean'],
            'system_ids' => ['nullable', 'array'],
            'system_ids.*' => ['integer', 'exists:systems,id'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
