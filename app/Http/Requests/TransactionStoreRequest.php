<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionStoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|in:income,expense,transfer,withdrawal',
            'transaction_date' => 'required|date',
            'total_amount' => 'required|numeric',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.subcategory_id' => 'nullable|exists:subcategories,id',
            'items.*.description' => 'nullable|string',
            'items.*.amount' => 'required|numeric',
        ];
    }

    protected function passedValidation()
    {
        $totalItemsAmount = collect($this->items)->sum('amount');
        if (abs($this->total_amount - $totalItemsAmount) > 0.01) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'total_amount' => ['The total amount must equal the sum of transaction items.'],
            ]);
        }
    }
}
