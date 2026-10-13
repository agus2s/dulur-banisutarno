<?php
// RSS 2.0 feed publik — 20 artikel terbaru berstatus published.
// URL: feed.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/Markdown.php';

header('Content-Type: application/rss+xml; charset=utf-8');

function xml_esc(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function abs_url(string $path = ''): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . base_url($path);
}

try {
    $posts = db()->query(
        "SELECT title, slug, excerpt, content_md, created_at FROM posts
         WHERE status = 'published' ORDER BY created_at DESC LIMIT 20"
    )->fetchAll();
} catch (Throwable $e) {
    $posts = [];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
<title><?= xml_esc(site_name()) ?></title>
<link><?= xml_esc(abs_url('index.php')) ?></link>
<description><?= xml_esc(site_desc()) ?></description>
<language>id</language>
<lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>
<?php foreach ($posts as $p): ?>
<?php
    $link = abs_url('post.php?slug=' . urlencode($p['slug']));
    $desc = $p['excerpt'] !== null && $p['excerpt'] !== '' ? $p['excerpt'] : markdown_excerpt($p['content_md']);
    $html = str_replace(']]>', ']]&gt;', Markdown::toHtml($p['content_md']));
?>
<item>
<title><?= xml_esc($p['title']) ?></title>
<link><?= xml_esc($link) ?></link>
<guid isPermaLink="true"><?= xml_esc($link) ?></guid>
<pubDate><?= date(DATE_RSS, strtotime($p['created_at'])) ?></pubDate>
<description><?= xml_esc($desc) ?></description>
<content:encoded><![CDATA[<?= $html ?>]]></content:encoded>
</item>
<?php endforeach; ?>
</channel>
</rss>
