<?php

namespace App\Console\Commands;

use App\Models\Kategorie;
use App\Models\RatgeberArtikel;
use App\Models\SeoMeta;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportRatgeber extends Command
{
    protected $signature = 'import:ratgeber {--path= : Verzeichnis mit Markdown-Dateien}';

    protected $description = 'Importiert Ratgeber-Artikel (Markdown mit Frontmatter) ins CMS.';

    public function handle(): int
    {
        $path = $this->option('path')
            ?: base_path('../../ratgeber');   // wunschkennzeichen-portal/ratgeber

        $path = realpath($path) ?: $path;
        if (! is_dir($path)) {
            $this->error('Verzeichnis nicht gefunden: '.$path);
            return self::FAILURE;
        }

        $files = glob(rtrim($path, '/').'/*.md');
        $this->info('Dateien: '.count($files));

        $imported = 0;
        foreach ($files as $file) {
            $raw = (string) file_get_contents($file);
            if (! preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $raw, $m)) {
                $this->warn('Übersprungen (kein Frontmatter): '.basename($file));
                continue;
            }

            $meta = $this->parseFrontmatter($m[1]);
            $body = trim($m[2]);

            $slug = $meta['slug'] ?? \App\Support\Slug::de($meta['titel'] ?? basename($file, '.md'));

            $kategorieId = null;
            if (! empty($meta['kategorie'])) {
                $kategorieId = Kategorie::firstOrCreate(
                    ['slug' => \App\Support\Slug::de($meta['kategorie'])],
                    ['name' => $meta['kategorie']]
                )->id;
            }

            $artikel = RatgeberArtikel::updateOrCreate(
                ['slug' => $slug],
                [
                    'titel'        => $meta['titel'] ?? $slug,
                    'kategorie_id' => $kategorieId,
                    'intro'        => $meta['intro'] ?? null,
                    'body'         => $body,
                    'stand_datum'  => $meta['stand'] ?? null,
                    'quelle'       => $meta['quelle'] ?? null,
                    'published_at' => now(),
                ]
            );

            // Tags
            $tagIds = [];
            foreach ($this->parseList($meta['tags'] ?? '') as $tagName) {
                $tagIds[] = Tag::firstOrCreate(
                    ['slug' => \App\Support\Slug::de($tagName)],
                    ['name' => $tagName]
                )->id;
            }
            $artikel->tags()->sync($tagIds);

            // SEO-Meta
            if (! empty($meta['meta_title']) || ! empty($meta['meta_description'])) {
                SeoMeta::updateOrCreate(
                    ['metable_type' => RatgeberArtikel::class, 'metable_id' => $artikel->id],
                    ['title' => $meta['meta_title'] ?? null, 'description' => $meta['meta_description'] ?? null]
                );
            }

            $imported++;
            $this->line('  ✓ '.$artikel->titel);
        }

        $this->info("Fertig. Importiert/aktualisiert: $imported");
        return self::SUCCESS;
    }

    private function parseFrontmatter(string $front): array
    {
        $meta = [];
        foreach (preg_split('/\n/', $front) as $line) {
            if (! str_contains($line, ':')) continue;
            [$key, $val] = explode(':', $line, 2);
            $meta[trim($key)] = trim(trim($val), "\"'");
        }
        return $meta;
    }

    /** "[a, b, c]" oder "a, b, c" => ['a','b','c'] */
    private function parseList(string $s): array
    {
        $s = trim($s, "[] \t");
        if ($s === '') return [];
        return array_values(array_filter(array_map('trim', explode(',', $s))));
    }
}
