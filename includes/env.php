<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
// Loader .env minimal tanpa dependensi.
// Format: KEY=VALUE, baris kosong & diawali # diabaikan.
// Nilai boleh dikutip ("..." / '...'), komentar inline pakai # (di luar kutip).
// Variabel environment asli (getenv) tidak ditimpa.

function load_env(string $path): void {
    if (!is_readable($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if ($key === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) continue;
        // nilai dikutip: ambil isi sampai kutip penutup, sisa baris (komentar) diabaikan
        if (($len = strlen($val)) >= 2 && ($val[0] === '"' || $val[0] === "'")) {
            $quote = $val[0];
            $end = strpos($val, $quote, 1);
            $val = $end === false ? substr($val, 1) : substr($val, 1, $end - 1);
            if ($quote === '"') $val = str_replace(['\\n', '\\"', '\\\\'], ["\n", '"', "\\"], $val);
        } else {
            // komentar inline di luar nilai tak dikutip
            $hash = strpos($val, ' #');
            if ($hash !== false) $val = rtrim(substr($val, 0, $hash));
        }
        if (getenv($key) === false) {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

function env(string $key, string $default = ''): string {
    $v = getenv($key);
    if ($v !== false) return $v;
    if (isset($_ENV[$key])) return (string)$_ENV[$key];
    return $default;
}
