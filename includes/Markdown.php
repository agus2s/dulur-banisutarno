<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
/**
 * Markdown — parser ringan tanpa dependensi (support GFM subset).
 * Penyimpanan: markdown mentah disimpan di kolom content_md.
 * Render: Markdown::toHtml($md) saat tampil di public & preview admin.
 *
 * Support: heading, bold, italic, strike, code block + inline,
 * link, image, blockquote, hr, ul, ol, task list, table, paragraph.
 */
class Markdown {
    public static function toHtml(string $md): string {
        $md = str_replace(["\r\n", "\r"], "\n", $md);

        // 1. Amankan code block ```...``` dulu
        $codes = [];
        $md = preg_replace_callback('/```(\w*)\n(.*?)```/s', function ($m) use (&$codes) {
            $lang = $m[1] !== '' ? ' class="language-' . htmlspecialchars($m[1]) . '"' : '';
            $key = "%%CODEBLOCK" . count($codes) . "%%";
            $codes[$key] = '<pre><code' . $lang . '>' . htmlspecialchars($m[2]) . '</code></pre>';
            return "\n$key\n";
        }, $md);

        $lines = explode("\n", $md);
        $html = '';
        $inUl = false; $inOl = false; $inQuote = false;
        $inTable = false; $tableRows = []; $tableAlign = [];

        $closeLists = function () use (&$html, &$inUl, &$inOl) {
            if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
            if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
        };
        $closeQuote = function () use (&$html, &$inQuote) {
            if ($inQuote) { $html .= "</blockquote>\n"; $inQuote = false; }
        };
        $flushTable = function () use (&$html, &$inTable, &$tableRows, &$tableAlign) {
            if (!$inTable || count($tableRows) < 1) { $inTable = false; $tableRows = []; return; }
            $html .= "<div class=\"table-wrap\"><table>\n<thead><tr>";
            foreach ($tableRows[0] as $i => $cell) {
                $a = $tableAlign[$i] ?? '';
                $style = $a ? " style=\"text-align:$a\"" : '';
                $html .= "<th$style>" . self::inline($cell) . "</th>";
            }
            $html .= "</tr></thead>\n<tbody>\n";
            for ($r = 1; $r < count($tableRows); $r++) {
                $html .= "<tr>";
                foreach ($tableRows[$r] as $i => $cell) {
                    $a = $tableAlign[$i] ?? '';
                    $style = $a ? " style=\"text-align:$a\"" : '';
                    $html .= "<td$style>" . self::inline($cell) . "</td>";
                }
                $html .= "</tr>\n";
            }
            $html .= "</tbody></table></div>\n";
            $inTable = false; $tableRows = []; $tableAlign = [];
        };

        foreach ($lines as $line) {
            $trim = trim($line);

            // placeholder code block
            if (preg_match('/^%%CODEBLOCK\d+%%$/', $trim)) {
                $closeLists(); $closeQuote(); $flushTable();
                $html .= ($codes[$trim] ?? '') . "\n";
                continue;
            }
            if ($trim === '') {
                $closeLists(); $closeQuote(); $flushTable();
                continue;
            }
            // HR
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', $trim)) {
                $closeLists(); $closeQuote(); $flushTable();
                $html .= "<hr>\n"; continue;
            }
            // Heading
            if (preg_match('/^(#{1,6})\s+(.*)$/', $trim, $m)) {
                $closeLists(); $closeQuote(); $flushTable();
                $lv = strlen($m[1]);
                $html .= "<h$lv>" . self::inline($m[2]) . "</h$lv>\n";
                continue;
            }
            // Blockquote
            if (preg_match('/^>\s?(.*)$/', $trim, $m)) {
                $closeLists(); $flushTable();
                if (!$inQuote) { $html .= "<blockquote>\n"; $inQuote = true; }
                $html .= "<p>" . self::inline($m[1]) . "</p>\n";
                continue;
            }
            // Table
            if (str_contains($trim, '|')) {
                // baris separator |---|:---:|
                if (preg_match('/^\|?[\s:|-]+\|?$/', $trim) && str_contains($trim, '-')) {
                    // parse align
                    $parts = explode('|', trim(trim($trim), '|'));
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if (str_starts_with($p, ':') && str_ends_with($p, ':')) $tableAlign[] = 'center';
                        elseif (str_ends_with($p, ':')) $tableAlign[] = 'right';
                        else $tableAlign[] = '';
                    }
                    $inTable = true;
                    continue;
                }
                // deteksi baris tabel: diawali/berisi pipe dan baris sblm/sesudahnya separator
                // Sederhananya: jika baris mengandung | dan sebelumnya header menunggu separator,
                // kita anggap tabel jika baris berikutnya adalah separator — ditangani dengan buffer.
                // Di sini: kumpulkan baris ber-pipe berurutan sebagai kandidat tabel.
                // Kita perlakukan tiap baris pipe sebagai row tabel bila inTable atau baris next adalah separator.
                // Implementasi praktis: jika mengandung | minimal 2 kolom, mulai tabel.
                $cells = array_map('trim', explode('|', trim($trim, '|')));
                if (count($cells) >= 2) {
                    $closeLists(); $closeQuote();
                    $inTable = true;
                    $tableRows[] = $cells;
                    continue;
                }
            }
            $flushTableIfNotTable = false;
            // Task list / UL
            if (preg_match('/^([-*+])\s+\[([ xX])\]\s+(.*)$/', $trim, $m)) {
                $flushTable(); $closeQuote();
                if (!$inUl) { $closeLists(); $html .= "<ul class=\"task-list\">\n"; $inUl = true; }
                $checked = strtolower($m[2]) === 'x' ? ' checked' : '';
                $html .= '<li class="task"><input type="checkbox" disabled' . $checked . '> ' . self::inline($m[3]) . "</li>\n";
                continue;
            }
            if (preg_match('/^([-*+])\s+(.*)$/', $trim, $m)) {
                $flushTable(); $closeQuote();
                if (!$inUl) { $closeLists(); $html .= "<ul>\n"; $inUl = true; }
                $html .= "<li>" . self::inline($m[2]) . "</li>\n";
                continue;
            }
            if (preg_match('/^(\d+)[.)]\s+(.*)$/', $trim, $m)) {
                $flushTable(); $closeQuote();
                if (!$inOl) { $closeLists(); $html .= "<ol>\n"; $inOl = true; }
                $html .= "<li>" . self::inline($m[2]) . "</li>\n";
                continue;
            }

            // Paragraf biasa
            $flushTable(); $closeLists(); $closeQuote();
            $html .= "<p>" . self::inline($trim) . "</p>\n";
        }
        $closeLists(); $closeQuote(); $flushTable();

        // kembalikan code block
        $html = str_replace(array_keys($codes), array_values($codes), $html);
        return $html;
    }

    private static function inline(string $t): string {
        $t = htmlspecialchars($t, ENT_QUOTES, 'UTF-8');

        // inline code `kode` — proses dulu agar isi kode tidak diparse bold/italic/link
        $codeSpans = [];
        $t = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$codeSpans) {
            $key = "%%CODESPAN" . count($codeSpans) . "%%";
            $codeSpans[$key] = '<code>' . $m[1] . '</code>';
            return $key;
        }, $t);

        // image ![alt](src)
        $t = preg_replace('/!\[([^\]]*)\]\(([^)\s]+)(?:\s+&quot;([^&]*?)&quot;)?\)/', '<img src="$2" alt="$1" loading="lazy">', $t);
        // link [teks](url)
        $t = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)(?:\s+&quot;([^&]*?)&quot;)?\)/', function ($m) {
            $url = $m[2];
            // shorthand permalink antar-halaman: [Kontak](page:kontak) -> /halaman/kontak
            if (str_starts_with($url, 'page:')) {
                $slug = substr($url, 5);
                $url = function_exists('page_url') ? page_url($slug) : 'halaman/' . rawurlencode($slug);
            } elseif (!preg_match('~^(https?://|mailto:|#|/)~', $url)) {
                $url = htmlspecialchars($url);
            }
            return '<a href="' . $url . '">' . $m[1] . '</a>';
        }, $t);
        // autolink http(s)
        $t = preg_replace('/(?<!href=&quot;|href="|src=")(https?:\/\/[^\s&lt;]+)/', '<a href="$1">$1</a>', $t);
        // bold **teks** dan __teks__
        $t = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $t);
        $t = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $t);
        // strikethrough ~~teks~~
        $t = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $t);
        // italic *teks* dan _teks_ (hati-hati dengan bold yg sudah diproses)
        $t = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $t);
        $t = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/s', '<em>$1</em>', $t);
        // kembalikan inline code
        if ($codeSpans) $t = str_replace(array_keys($codeSpans), array_values($codeSpans), $t);
        // ==mark== dan footnote sederhana ^[^1] diabaikan
        return $t;
    }
}
