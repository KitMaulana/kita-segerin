<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
        $produk = $this->route('produk');

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique(Product::class)->ignore($produk?->id)],
            'name' => ['required', 'string', 'max:150'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'variant' => ['required', Rule::in(array_keys(Product::VARIAN))],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'default_selling_price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'default_fee_type' => ['required', Rule::in(['nominal', 'percent'])],
            'default_fee_value' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'hapus_foto' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $jenis = $this->input('default_fee_type');
            $nilai = (int) $this->input('default_fee_value');
            $jual = (int) $this->input('default_selling_price');

            if ($jenis === 'percent' && $nilai > 100) {
                $validator->errors()->add('default_fee_value', 'Fee persen tidak boleh lebih dari 100%.');
            }

            if ($jenis === 'nominal' && $jual > 0 && $nilai > $jual) {
                $validator->errors()->add('default_fee_value', 'Fee nominal tidak boleh melebihi harga jual.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'Kode produk',
            'name' => 'Nama produk',
            'supplier_id' => 'Pemasok',
            'variant' => 'Varian',
            'unit' => 'Satuan',
            'cost_price' => 'Harga modal',
            'default_selling_price' => 'Harga jual default',
            'default_fee_type' => 'Jenis fee',
            'default_fee_value' => 'Nilai fee',
            'min_stock' => 'Stok minimum',
            'photo' => 'Foto',
            'is_active' => 'Status aktif',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
            'hapus_foto' => $this->boolean('hapus_foto'),
        ]);
    }
}
