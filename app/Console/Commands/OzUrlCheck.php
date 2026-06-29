<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Prüft den HTTP-Status jeder Öffnungszeiten-URL (parallel, mit Browser-Headern).
 * Markiert tote URLs (404/410/5xx/nicht erreichbar) in oeffnungszeiten_url_status.
 */
class OzUrlCheck extends Command
{
    protected $signature = 'oz:url-check {--limit=0} {--chunk=25}';
    protected $description = 'Prüft Öffnungszeiten-URLs auf HTTP-Status / tote Links.';

    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    public function handle(): int
    {
        $query = Zulassungsstelle::whereNull('parent_id')->whereNotNull('oeffnungszeiten_url');
        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }
        $stellen = $query->get(['id', 'oeffnungszeiten_url']);
        $chunkSize = max(5, (int) $this->option('chunk'));

        $verteilung = [];
        $tot = 0;

        foreach ($stellen->chunk($chunkSize) as $chunk) {
            $resp = Http::pool(fn ($pool) => $chunk->mapWithKeys(fn ($s) => [
                (string) $s->id => $pool->as((string) $s->id)
                    ->withHeaders(['User-Agent' => self::UA, 'Accept' => 'text/html,*/*'])
                    ->withOptions(['verify' => false, 'allow_redirects' => true])
                    ->timeout(15)->get(html_entity_decode($s->oeffnungszeiten_url)),
            ])->all());

            foreach ($chunk as $s) {
                $r = $resp[(string) $s->id] ?? null;
                $status = $r instanceof Response ? $r->status() : 0;   // 0 = Verbindungsfehler/DNS/Timeout
                $verteilung[$status] = ($verteilung[$status] ?? 0) + 1;
                if ($this->istTot($status)) {
                    $tot++;
                }
                $s->oeffnungszeiten_url_status = $status;
                $s->saveQuietly();
            }
        }

        ksort($verteilung);
        $this->info('HTTP-Status-Verteilung:');
        foreach ($verteilung as $code => $n) {
            $label = $code === 0 ? 'nicht erreichbar' : $code;
            $this->line(sprintf('  %-16s %d%s', $label, $n, $this->istTot($code) ? '  ← tot' : ''));
        }
        $this->info("Tote URLs gesamt: $tot von ".$stellen->count());

        return self::SUCCESS;
    }

    private function istTot(int $status): bool
    {
        return $status === 0 || $status === 404 || $status === 410 || $status >= 500;
    }
}
