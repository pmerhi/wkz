<?php

namespace App\Support;

use Illuminate\Http\Request;

class AbTesting
{
    private array $resolved = [];

    public function __construct(private array $config) {}

    /** Liefert die zugewiesene Variante für ein Experiment (sticky, Fallback 'a'). */
    public function variant(string $key, Request $request): string
    {
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        $exp = $this->config[$key] ?? null;
        if (! $exp || ! ($exp['enabled'] ?? false) || empty($exp['variants'])) {
            return $this->resolved[$key] = 'a';
        }

        $variants = $exp['variants'];
        $cookie = $request->cookie('ab_'.$key);
        if (is_string($cookie) && isset($variants[$cookie])) {
            return $this->resolved[$key] = $cookie;
        }

        return $this->resolved[$key] = $this->weightedPick($variants);
    }

    /** Alle aktiven Experimente => zugewiesene Variante. */
    public function all(Request $request): array
    {
        $out = [];
        foreach ($this->config as $key => $exp) {
            if ($exp['enabled'] ?? false) {
                $out[$key] = $this->variant($key, $request);
            }
        }
        return $out;
    }

    private function weightedPick(array $variants): string
    {
        $total = array_sum($variants);
        if ($total <= 0) {
            return (string) array_key_first($variants);
        }
        $r = random_int(1, $total);
        $acc = 0;
        foreach ($variants as $name => $weight) {
            $acc += $weight;
            if ($r <= $acc) {
                return (string) $name;
            }
        }
        return (string) array_key_first($variants);
    }
}
