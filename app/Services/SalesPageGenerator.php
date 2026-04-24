<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * OpenAI-compatible chat client. Works with OpenAI, Groq, OpenRouter, vLLM,
 * Ollama (with /v1 enabled), or anything else that speaks the chat-completions API.
 *
 * Returns the parsed JSON sales-page payload.
 */
class SalesPageGenerator
{
    public function __construct(private ContextManager $context) {}

    /**
     * Field-specific instruction + static fallback list. Used for suggestions.
     *
     * @var array<string, array{label:string, instruction:string, fallback:array<int,string>}>
     */
    private const FIELDS = [
        'description' => [
            'label' => 'Deskripsi produk',
            'instruction' => 'Tiap saran adalah ringkasan produk 1-2 kalimat yang jelas dan menjual.',
            'fallback' => [
                'Platform all-in-one untuk mengelola penjualan dan pelanggan dari satu dashboard.',
                'Alat produktivitas yang membantu tim menyelesaikan pekerjaan 2x lebih cepat.',
                'Solusi digital yang dirancang khusus untuk kebutuhan bisnis Anda.',
                'Aplikasi modern dengan fitur lengkap dan tampilan intuitif.',
            ],
        ],
        'features' => [
            'label' => 'Fitur utama',
            'instruction' => 'Tiap saran adalah satu nama fitur singkat (maks 6 kata).',
            'fallback' => [
                'Dashboard analitik real-time',
                'Integrasi dengan WhatsApp Business',
                'Laporan otomatis harian',
                'Manajemen pengguna multi-role',
                'Template siap pakai',
                'Dukungan 24/7 via chat',
            ],
        ],
        'target_audience' => [
            'label' => 'Target audiens',
            'instruction' => 'Tiap saran adalah persona target audiens singkat (2-6 kata).',
            'fallback' => [
                'Pemilik UMKM',
                'Founder startup early-stage',
                'Marketer digital',
                'Freelancer kreatif',
                'Manajer operasional perusahaan menengah',
            ],
        ],
        'price' => [
            'label' => 'Harga',
            'instruction' => 'Tiap saran adalah satu format harga dalam IDR (contoh: "Rp 199.000 / bulan").',
            'fallback' => [
                'Rp 99.000 / bulan',
                'Rp 299.000 / bulan',
                'Rp 1.499.000 / tahun',
                'Rp 499.000 sekali bayar',
            ],
        ],
        'usp' => [
            'label' => 'USP / keunikan',
            'instruction' => 'Tiap saran adalah satu kalimat singkat yang menyoroti keunikan produk.',
            'fallback' => [
                'Satu-satunya solusi yang menggabungkan otomatisasi dan personalisasi.',
                'Setup kurang dari 5 menit tanpa perlu coding.',
                'Harga paling kompetitif di kelasnya dengan fitur premium.',
                'Dibuat oleh praktisi lokal yang paham pasar Indonesia.',
            ],
        ],
    ];

    /**
     * Suggest 3-6 options for a single form field, using history as soft context.
     *
     * @param  array<string,mixed>  $formState  current values the user has typed
     * @return array{suggestions: array<int,string>, source: 'llm'|'fallback'}
     */
    public function suggest(string $field, array $formState, \App\Models\User $user): array
    {
        if (! isset(self::FIELDS[$field])) {
            throw new \InvalidArgumentException("Field tidak didukung: $field");
        }

        // Soft fallback when the LLM isn't configured or fails — keeps UX working.
        if (! $this->isConfigured()) {
            return ['suggestions' => self::FIELDS[$field]['fallback'], 'source' => 'fallback'];
        }

        try {
            $bundle = $this->context->buildForUser($user, 3);
            $raw = $this->callSuggestionLlm($field, $formState, $bundle['summary']);
            $decoded = $this->parseJson($raw);
            $items = array_values(array_filter(array_map('strval', (array) ($decoded['suggestions'] ?? []))));

            if (empty($items)) {
                return ['suggestions' => self::FIELDS[$field]['fallback'], 'source' => 'fallback'];
            }

            return ['suggestions' => array_slice($items, 0, 6), 'source' => 'llm'];
        } catch (\Throwable $e) {
            Log::warning('Suggestion LLM call failed, using fallback', ['field' => $field, 'err' => $e->getMessage()]);

            return ['suggestions' => self::FIELDS[$field]['fallback'], 'source' => 'fallback'];
        }
    }

    private function isConfigured(): bool
    {
        return (string) config('services.openai.api_key') !== '' && (string) config('services.openai.base_url') !== '';
    }

    private function callSuggestionLlm(string $field, array $formState, string $contextSummary): string
    {
        $spec = self::FIELDS[$field];

        $system = <<<'SYS'
You are a senior copywriter helping a user brainstorm ideas for their sales page.
Return ONLY valid JSON matching the schema. No prose, no markdown fences.
Produce 4-5 varied, concise suggestions in Bahasa Indonesia.
Use the user's history (if any) to stay consistent with their tone & audience.
SYS;

        $userMsg = sprintf(
            "HISTORY CONTEXT:\n%s\n\nCURRENT FORM STATE:\n- Product name: %s\n- Description: %s\n- Target audience: %s\n- USP: %s\n- Tone: %s\n\nREQUESTED FIELD: %s\nINSTRUCTION: %s\n\nReturn JSON: { \"suggestions\": string[] }",
            $contextSummary,
            $formState['product_name'] ?? '',
            $formState['description'] ?? '',
            $formState['target_audience'] ?? '',
            $formState['usp'] ?? '',
            $formState['tone'] ?? '',
            $spec['label'],
            $spec['instruction'],
        );

        $base = rtrim((string) config('services.openai.base_url'), '/');
        $key = (string) config('services.openai.api_key');
        $model = (string) config('services.openai.model');

        $payload = [
            'model' => $model,
            'temperature' => 0.9,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userMsg],
            ],
            'response_format' => $this->suggestionResponseFormat(),
        ];

        $response = Http::withToken($key)
            ->timeout(min((int) config('services.openai.timeout', 60), 30))
            ->acceptJson()
            ->post($base.'/chat/completions', $payload);

        if ($response->status() === 400 && config('services.openai.schema_mode', true)) {
            $body = strtolower((string) $response->body());
            if (str_contains($body, 'json_schema') || str_contains($body, 'response_format')) {
                $payload['response_format'] = ['type' => 'json_object'];
                $response = Http::withToken($key)
                    ->timeout(min((int) config('services.openai.timeout', 60), 30))
                    ->acceptJson()
                    ->post($base.'/chat/completions', $payload);
            }
        }

        if ($response->failed()) {
            throw new RuntimeException('LLM suggest request failed: '.$response->status());
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($content) || $content === '') {
            throw new RuntimeException('LLM returned empty suggestion content');
        }

        return $content;
    }

    private function suggestionResponseFormat(): array
    {
        if (! config('services.openai.schema_mode', true)) {
            return ['type' => 'json_object'];
        }

        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'field_suggestions',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['suggestions'],
                    'properties' => [
                        'suggestions' => [
                            'type' => 'array',
                            'minItems' => 3,
                            'maxItems' => 6,
                            'items' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string,mixed>  $input
     * @return array{content: array<string,mixed>, context_summary: string}
     */
    public function generate(array $input, \App\Models\User $user): array
    {
        $bundle = $this->context->buildForUser($user);
        $prompt = $this->buildPrompt($input, $bundle['summary']);

        $payload = $this->callLlm($prompt);
        $content = $this->parseJson($payload);
        $content = $this->validateShape($content);

        $summary = $this->context->summarizePage($input, $content);

        return ['content' => $content, 'context_summary' => $summary];
    }

    private function buildPrompt(array $input, string $contextSummary): array
    {
        $system = <<<'SYS'
You are a senior conversion copywriter.

Your task is to generate a high-converting sales page.

Use the provided CONTEXT to maintain consistency in tone, style, and messaging.

Return ONLY valid JSON. No prose, no markdown fences.

Structure:
{
  "headline": string,
  "subheadline": string,
  "description": string,
  "benefits": string[],
  "features": string[],
  "social_proof": string,
  "price": string,
  "call_to_action": string
}

Rules:
- Follow AIDA principles (Attention, Interest, Desire, Action).
- Maintain consistency with previous outputs (tone, audience framing, positioning).
- Avoid repetition: do not reuse previous headlines or CTAs verbatim.
- Keep it natural and persuasive.
- Language: Bahasa Indonesia.
SYS;

        $user = sprintf(
            "CONTEXT (previous user generations):\n%s\n\nCURRENT INPUT:\nProduct Name: %s\nDescription: %s\nFeatures: %s\nTarget Audience: %s\nPrice: %s\nUSP: %s\nTone: %s\n\nReturn JSON only.",
            $contextSummary,
            (string) ($input['product_name'] ?? ''),
            (string) ($input['description'] ?? ''),
            is_array($input['features'] ?? null) ? implode(', ', $input['features']) : (string) ($input['features'] ?? ''),
            (string) ($input['target_audience'] ?? ''),
            (string) ($input['price'] ?? ''),
            (string) ($input['usp'] ?? ''),
            (string) ($input['tone'] ?? 'profesional & meyakinkan'),
        );

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }

    private function callLlm(array $messages): string
    {
        $base = rtrim((string) config('services.openai.base_url'), '/');
        $key = (string) config('services.openai.api_key');
        $model = (string) config('services.openai.model');

        if ($key === '' || $base === '') {
            throw new RuntimeException('LLM not configured. Set OPENAI_API_KEY and OPENAI_BASE_URL in .env');
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'response_format' => $this->responseFormat(),
        ];

        $response = Http::withToken($key)
            ->timeout((int) config('services.openai.timeout', 60))
            ->acceptJson()
            ->post($base.'/chat/completions', $payload);

        // If the endpoint doesn't support json_schema (older models / some proxies),
        // automatically retry with the broader json_object mode.
        if ($response->status() === 400 && config('services.openai.schema_mode', true)) {
            $body = (string) $response->body();
            if (str_contains(strtolower($body), 'json_schema') || str_contains(strtolower($body), 'response_format')) {
                $payload['response_format'] = ['type' => 'json_object'];
                $response = Http::withToken($key)
                    ->timeout((int) config('services.openai.timeout', 60))
                    ->acceptJson()
                    ->post($base.'/chat/completions', $payload);
            }
        }

        if ($response->failed()) {
            Log::error('LLM call failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('LLM request failed: '.$response->status());
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($content) || $content === '') {
            throw new RuntimeException('LLM returned empty content');
        }

        return $content;
    }

    /**
     * Prefer the stricter json_schema mode (gpt-4o-2024-08-06+, Groq, most modern endpoints).
     * Falls back to json_object if OPENAI_SCHEMA_MODE is disabled or the endpoint rejects it.
     */
    private function responseFormat(): array
    {
        if (! config('services.openai.schema_mode', true)) {
            return ['type' => 'json_object'];
        }

        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'sales_page',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['headline', 'subheadline', 'description', 'benefits', 'features', 'social_proof', 'price', 'call_to_action'],
                    'properties' => [
                        'headline' => ['type' => 'string'],
                        'subheadline' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'benefits' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 3, 'maxItems' => 8],
                        'features' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 3, 'maxItems' => 8],
                        'social_proof' => ['type' => 'string'],
                        'price' => ['type' => 'string'],
                        'call_to_action' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
    }

    private function parseJson(string $raw): array
    {
        // Strip markdown code fences if the model added them anyway.
        $cleaned = trim($raw);
        $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $cleaned) ?? $cleaned;

        $decoded = json_decode($cleaned, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('LLM returned non-JSON content: '.\Illuminate\Support\Str::limit($raw, 200));
        }

        return $decoded;
    }

    /**
     * Coerce missing fields to safe defaults so the view never breaks.
     */
    private function validateShape(array $content): array
    {
        return [
            'headline' => (string) ($content['headline'] ?? ''),
            'subheadline' => (string) ($content['subheadline'] ?? ''),
            'description' => (string) ($content['description'] ?? ''),
            'benefits' => array_values(array_filter(array_map('strval', (array) ($content['benefits'] ?? [])))),
            'features' => array_values(array_filter(array_map('strval', (array) ($content['features'] ?? [])))),
            'social_proof' => (string) ($content['social_proof'] ?? ''),
            'price' => (string) ($content['price'] ?? ''),
            'call_to_action' => (string) ($content['call_to_action'] ?? ''),
        ];
    }
}
