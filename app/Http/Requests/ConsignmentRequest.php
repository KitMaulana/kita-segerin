<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Services\StockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ConsignmentRequest extends FormRequest
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
            'store_id' => ['required', 'exists:stores,id'],
            'sent_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.qty_sent' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    /**
     * Jumlah kirim tidak boleh melebihi stok gudang yang tersedia.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $stok = app(StockService::class);

            foreach ($this->input('items', []) as $i => $baris) {
                $produk = Product::find($baris['product_id'] ?? null);

                if (! $produk) {
                    continue;
                }

                $cek = $stok->periksaKetersediaan($produk, (int) $baris['qty_sent']);

                if (! $cek['cukup']) {
                    $validator->errors()->add(
                        "items.{$i}.qty_sent",
                        "Stok gudang {$produk->name} tinggal {$cek['tersedia']} {$produk->unit}, tidak cukup untuk mengirim {$cek['diminta']}."
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'store_id' => 'Toko',
            'sent_date' => 'Tanggal kirim',
            'notes' => 'Catatan',
            'items' => 'Daftar produk',
            'items.*.product_id' => 'Produk',
            'items.*.qty_sent' => 'Jumlah kirim',
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
            'sent_date.before_or_equal' => 'Tanggal kirim tidak boleh di masa depan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'items' => collect($this->input('items', []))
                ->filter(fn ($b) => filled($b['product_id'] ?? null) && (int) ($b['qty_sent'] ?? 0) > 0)
                ->values()
                ->all(),
        ]);
    }
}
