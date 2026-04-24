<?php

namespace App\Services;

use App\Models\SalesPage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * MCP-lite context layer.
 *
 * Responsibilities:
 *  - Retrieve recent generations for a user (3-5 entries by default).
 *  - Extract reusable patterns (tone, audience, positioning).
 *  - Produce a compact, token-budgeted context summary string.
 *  - Build "do not repeat" guards for headlines / CTAs.
 *  - Persist a per-page summary so retrieval stays cheap.
 *
 * The goal is consistency without bloating the prompt: we only carry the
 * structured fields the model actually needs to stay aligned with prior work.
 */
class ContextManager
{
    public const DEFAULT_RECENT = 5;
    public const CHAR_BUDGET = 2400;

    /**
     * Build a context bundle ready for prompt injection.
     *
     * @return array{summary: string, patterns: array<string,mixed>, avoid: array<string,array<int,string>>, used: int}
     */
    public function buildForUser(User $user, int $limit = self::DEFAULT_RECENT): array
    {
        $recent = SalesPage::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit($limit)
            ->get();

        if ($recent->isEmpty()) {
            return [
                'summary' => '(belum ada riwayat — ini generasi pertama untuk pengguna)',
                'patterns' => [],
                'avoid' => ['headlines' => [], 'ctas' => []],
                'used' => 0,
            ];
        }

        $patterns = $this->extractPatterns($recent);
        $avoid = $this->collectAvoidLists($recent);
        $summary = $this->compose($recent, $patterns, $avoid);

        return [
            'summary' => $this->budget($summary, self::CHAR_BUDGET),
            'patterns' => $patterns,
            'avoid' => $avoid,
            'used' => $recent->count(),
        ];
    }

    /**
     * Build a single-page summary at write time so we don't re-summarize on every read.
     */
    public function summarizePage(array $input, array $generated): string
    {
        $headline = (string) ($generated['headline'] ?? '');
        $audience = (string) ($input['target_audience'] ?? '');
        $tone = (string) ($input['tone'] ?? '');
        $usp = (string) ($input['usp'] ?? '');
        $benefits = collect($generated['benefits'] ?? [])->take(2)->implode(' | ');

        return collect([
            'product' => $input['product_name'] ?? null,
            'audience' => $audience ?: null,
            'tone' => $tone ?: null,
            'usp' => Str::limit($usp, 120) ?: null,
            'headline' => Str::limit($headline, 120) ?: null,
            'top_benefits' => $benefits ?: null,
            'cta' => $generated['call_to_action'] ?? null,
        ])
            ->filter()
            ->map(fn ($v, $k) => "$k: $v")
            ->implode(' :: ');
    }

    /**
     * @param  Collection<int,SalesPage>  $pages
     * @return array<string,mixed>
     */
    private function extractPatterns(Collection $pages): array
    {
        $tones = $this->modeOf($pages->pluck('input_data.tone')->filter()->all());
        $audiences = $this->modeOf($pages->pluck('input_data.target_audience')->filter()->all());
        $usps = $pages->pluck('input_data.usp')->filter()->take(3)->all();

        return [
            'dominant_tone' => $tones,
            'dominant_audience' => $audiences,
            'recent_usps' => $usps,
            'sample_size' => $pages->count(),
        ];
    }

    /**
     * @param  Collection<int,SalesPage>  $pages
     * @return array{headlines: array<int,string>, ctas: array<int,string>}
     */
    private function collectAvoidLists(Collection $pages): array
    {
        return [
            'headlines' => $pages
                ->pluck('generated_content.headline')
                ->filter()
                ->map(fn ($h) => (string) $h)
                ->take(5)
                ->values()
                ->all(),
            'ctas' => $pages
                ->pluck('generated_content.call_to_action')
                ->filter()
                ->map(fn ($c) => (string) $c)
                ->take(5)
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int,SalesPage>  $pages
     * @param  array<string,mixed>  $patterns
     * @param  array<string,array<int,string>>  $avoid
     */
    private function compose(Collection $pages, array $patterns, array $avoid): string
    {
        $lines = [];
        $lines[] = '=== POLA DARI '.$pages->count().' GENERASI TERAKHIR ===';

        if ($patterns['dominant_tone']) {
            $lines[] = '- Tone dominan: '.$patterns['dominant_tone'];
        }
        if ($patterns['dominant_audience']) {
            $lines[] = '- Audiens dominan: '.$patterns['dominant_audience'];
        }
        if (! empty($patterns['recent_usps'])) {
            $lines[] = '- USP terkini: '.collect($patterns['recent_usps'])->map(fn ($u) => Str::limit((string) $u, 80))->implode(' | ');
        }

        $lines[] = '';
        $lines[] = '=== RINGKASAN HALAMAN SEBELUMNYA (terbaru → terlama) ===';
        foreach ($pages as $p) {
            $summary = $p->context_summary ?: $this->summarizePage((array) $p->input_data, (array) $p->generated_content);
            $lines[] = '• '.$summary;
        }

        if (! empty($avoid['headlines']) || ! empty($avoid['ctas'])) {
            $lines[] = '';
            $lines[] = '=== JANGAN DIULANG (gunakan variasi baru) ===';
            if (! empty($avoid['headlines'])) {
                $lines[] = '- Headline sebelumnya: '.collect($avoid['headlines'])->map(fn ($h) => '"'.Str::limit($h, 80).'"')->implode(' ; ');
            }
            if (! empty($avoid['ctas'])) {
                $lines[] = '- CTA sebelumnya: '.collect($avoid['ctas'])->map(fn ($c) => '"'.Str::limit($c, 60).'"')->implode(' ; ');
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Truncate from the tail (oldest entries) so newer context survives.
     */
    private function budget(string $text, int $maxChars): string
    {
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return mb_substr($text, 0, $maxChars - 32)."\n…[ringkasan dipotong demi token budget]";
    }

    /**
     * @param  array<int,string>  $values
     */
    private function modeOf(array $values): ?string
    {
        if (empty($values)) {
            return null;
        }

        $counts = array_count_values(array_map('strtolower', $values));
        arsort($counts);

        return (string) array_key_first($counts);
    }
}
