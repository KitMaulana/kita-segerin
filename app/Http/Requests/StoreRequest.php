<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('input-transaksi');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $toko = $this->route('toko');

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique(Store::class)->ignore($toko?->id)],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(Store::JENIS))],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'payment_term_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'Kode toko',
            'name' => 'Nama toko',
            'type' => 'Jenis',
            'contact_person' => 'Nama kontak',
            'phone' => 'Telepon',
            'address' => 'Alamat',
            'payment_term_days' => 'Tempo bayar',
            'notes' => 'Catatan',
            'is_active' => 'Status aktif',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
            'payment_term_days' => $this->input('payment_term_days') === '' ? null : $this->input('payment_term_days'),
        ]);
    }
}
