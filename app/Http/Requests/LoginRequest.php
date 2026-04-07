<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'max:255',
                'regex:/^\S+$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $login = (string) $value;
                    if (str_contains($login, '@')) {
                        if (! filter_var($login, FILTER_VALIDATE_EMAIL)) {
                            $fail('Enter a valid email address.');
                        }
                        return;
                    }

                    if (! preg_match('/^[A-Za-z]+$/', $login)) {
                        $fail('Codename must contain letters only (no spaces, numbers, or symbols).');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:6'],
            'remember_device' => ['nullable', 'boolean'],
        ];
    }
}
