<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'features' => ['nullable', 'string', 'max:2000'],
            'target_audience' => ['required', 'string', 'max:255'],
            'price' => ['required', 'string', 'max:100'],
            'usp' => ['nullable', 'string', 'max:500'],
            'tone' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Normalize features into an array of trimmed lines.
     */
    public function normalized(): array
    {
        $features = collect(preg_split('/\r?\n|,/', (string) $this->input('features', '')))
            ->map(fn ($f) => trim((string) $f))
            ->filter()
            ->values()
            ->all();

        return [
            'product_name' => $this->input('product_name'),
            'description' => $this->input('description'),
            'features' => $features,
            'target_audience' => $this->input('target_audience'),
            'price' => $this->input('price'),
            'usp' => (string) $this->input('usp', ''),
            'tone' => (string) $this->input('tone', 'profesional & meyakinkan'),
        ];
    }
}
