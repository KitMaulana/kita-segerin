<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class PurchaseRequest extends FormRequest
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
        return [
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'update_cost_price' => ['boolean'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'integer', 'min:0', 'max:999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'supplier_id' => 'Pemasok',
            'purchase_date' => 'Tanggal pembelian',
            'notes' => 'Catatan',
            'items' => 'Daftar produk',
            'items.*.product_id' => 'Produk',
            'items.*.qty' => 'Jumlah',
            'items.*.unit_cost' => 'Harga modal satuan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal satu baris produk.',
            'items.*.product_id.distinct' => 'Produk yang sama tidak boleh ditulis dua kali.',
            'purchase_date.before_or_equal' => 'Tanggal pembelian tidak boleh di masa depan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'update_cost_price' => $this->boolean('update_cost_price'),
            // Baris kosong yang ditinggalkan pengguna dibuang lebih dulu.
            'items' => collect($this->input('items', []))
                ->filter(fn ($b) => filled($b['product_id'] ?? null) && (int) ($b['qty'] ?? 0) > 0)
                ->values()
                ->all(),
        ]);
    }
}
