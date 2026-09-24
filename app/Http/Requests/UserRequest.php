<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('kelola-akun');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $akun = $this->route('akun');
        $membuatBaru = $akun === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique(User::class)->ignore($akun?->id),
            ],
            'email' => [
                'nullable', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($akun?->id),
            ],
            'role' => ['required', Rule::in(array_keys(User::PERAN))],
            'is_active' => ['boolean'],
            'password' => [
                $membuatBaru ? 'required' : 'nullable',
                'confirmed',
                Password::min(8),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nama',
            'username' => 'Username',
            'email' => 'Email',
            'role' => 'Peran',
            'is_active' => 'Status aktif',
            'password' => 'Kata sandi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip, dan garis bawah.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => strtolower(trim((string) $this->input('username'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
