// Editor Markdown Blog Dulur: toolbar + live preview + autosave
(function () {
  const ta = document.getElementById('md-input');
  const pv = document.getElementById('md-preview');
  const count = document.getElementById('md-count');
  if (!ta || !pv) return;

  const titleInput = document.getElementById('title-input');

  // --- autosave ke localStorage ---
  const KEY = 'blogdulur_md_draft_' + (document.getElementById('post-id')?.value || 'new');
  if (!ta.value && localStorage.getItem(KEY)) {
    if (confirm('Ditemukan draft tersimpan. Muat draft?')) ta.value = localStorage.getItem(KEY);
  }
  ta.addEventListener('input', () => {
    localStorage.setItem(KEY, ta.value);
    render();
  });
  document.querySelector('form')?.addEventListener('submit', () => localStorage.removeItem(KEY));

  // --- toolbar insert ---
  function wrap(before, after = '', placeholder = 'teks') {
    const s = ta.selectionStart, e = ta.selectionEnd;
    const sel = ta.value.slice(s, e) || placeholder;
    ta.value = ta.value.slice(0, s) + before + sel + after + ta.value.slice(e);
    ta.focus();
    ta.selectionStart = s + before.length;
    ta.selectionEnd = s + before.length + sel.length;
    ta.dispatchEvent(new Event('input'));
  }
  function linePrefix(prefix) {
    const s = ta.selectionStart;
    const start = ta.value.lastIndexOf('\n', s - 1) + 1;
    ta.value = ta.value.slice(0, start) + prefix + ta.value.slice(start);
    ta.focus(); ta.dispatchEvent(new Event('input'));
  }
  document.querySelectorAll('[data-md]').forEach(btn => {
    btn.addEventListener('click', () => {
      const a = btn.dataset.md;
      if (a === 'bold') wrap('**', '**', 'teks tebal');
      if (a === 'italic') wrap('*', '*', 'teks miring');
      if (a === 'strike') wrap('~~', '~~', 'coret');
      if (a === 'code') wrap('`', '`', 'kode');
      if (a === 'codeblock') wrap('```php\n', '\n```', 'echo "Hello";');
      if (a === 'h1') linePrefix('# ');
      if (a === 'h2') linePrefix('## ');
      if (a === 'h3') linePrefix('### ');
      if (a === 'quote') linePrefix('> ');
      if (a === 'ul') linePrefix('- ');
      if (a === 'ol') linePrefix('1. ');
      if (a === 'task') linePrefix('- [ ] ');
      if (a === 'hr') wrap('\n\n---\n\n');
      if (a === 'link') wrap('[', '](https://)', 'judul link');
      if (a === 'image') wrap('![', '](https://.../gambar.jpg)', 'alt gambar');
      if (a === 'table') wrap('\n\n| Kolom 1 | Kolom 2 |\n|---|---|\n| Isi | Isi |\n\n');
    });
  });

  // Tab = 2 spasi
  ta.addEventListener('keydown', (ev) => {
    if (ev.key === 'Tab') {
      ev.preventDefault();
      const s = ta.selectionStart;
      ta.value = ta.value.slice(0, s) + '  ' + ta.value.slice(ta.selectionEnd);
      ta.selectionStart = ta.selectionEnd = s + 2;
      ta.dispatchEvent(new Event('input'));
    }
  });

  // --- mini renderer JS (mirror server, untuk preview offline) ---
  function esc(s) { return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
  function inline(t) {
    t = esc(t);
    t = t.replace(/!\[([^\]]*)\]\(([^)\s]+)\)/g, '<img src="$2" alt="$1" style="max-width:100%">');
    t = t.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, '<a href="$2">$1</a>');
    t = t.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    t = t.replace(/~~(.+?)~~/g, '<del>$1</del>');
    t = t.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
    t = t.replace(/`([^`]+)`/g, '<code>$1</code>');
    return t;
  }
  function toHtml(md) {
    // code block
    const blocks = [];
    md = md.replace(/```(\w*)\n([\s\S]*?)```/g, (_, l, c) => {
      blocks.push('<pre><code>' + esc(c) + '</code></pre>');
      return '\u0000' + (blocks.length - 1) + '\u0000';
    });
    const out = [];
    let ul = false, ol = false;
    md.split('\n').forEach(line => {
      const tr = line.trim();
      if (/^\u0000\d+\u0000$/.test(tr)) { if (ul) { out.push('</ul>'); ul = false; } if (ol) { out.push('</ol>'); ol = false; } out.push(blocks[+tr.replace(/\u0000/g, '')]); return; }
      if (!tr) { if (ul) { out.push('</ul>'); ul = false; } if (ol) { out.push('</ol>'); ol = false; } return; }
      let m;
      if (m = tr.match(/^(#{1,6})\s+(.*)/)) { out.push(`<h${m[1].length}>${inline(m[2])}</h${m[1].length}>`); return; }
      if (/^(-{3,}|\*{3,})$/ .test(tr)) { out.push('<hr>'); return; }
      if (m = tr.match(/^&gt;|^>\s?(.*)/) || tr.startsWith('>')) { out.push('<blockquote><p>' + inline(tr.replace(/^>\s?/, '')) + '</p></blockquote>'); return; }
      if (m = tr.match(/^[-*+]\s+(.*)/)) { if (!ul) { out.push('<ul>'); ul = true; } out.push('<li>' + inline(m[1]) + '</li>'); return; }
      if (m = tr.match(/^\d+[.)]\s+(.*)/)) { if (!ol) { out.push('<ol>'); ol = true; } out.push('<li>' + inline(m[1]) + '</li>'); return; }
      if (ul) { out.push('</ul>'); ul = false; } if (ol) { out.push('</ol>'); ol = false; }
      out.push('<p>' + inline(tr) + '</p>');
    });
    if (ul) out.push('</ul>'); if (ol) out.push('</ol>');
    return out.join('\n');
  }
  function render() {
    const v = ta.value || '*Pratinjau kosong — mulai mengetik Markdown…*';
    pv.innerHTML = toHtml(v);
    const words = ta.value.trim() ? ta.value.trim().split(/\s+/).length : 0;
    if (count) count.textContent = `${ta.value.length} karakter • ${words} kata • ${ta.value.split('\n').length} baris`;
  }
  render();

  // --- upload file .md ke textarea ---
  const up = document.getElementById('md-upload');
  if (up) {
    up.addEventListener('change', () => {
      const f = up.files && up.files[0];
      if (!f) return;
      const rd = new FileReader();
      rd.onload = () => {
        if (ta.value.trim() && !confirm('Ganti isi editor dengan file "' + f.name + '"?')) { up.value = ''; return; }
        ta.value = String(rd.result || '');
        ta.dispatchEvent(new Event('input'));
        if (titleInput && !titleInput.value.trim()) {
          const base = f.name.replace(/\.(md|markdown|txt)$/i, '').replace(/[-_]+/g, ' ').trim();
          if (base) titleInput.value = base;
        }
        up.value = '';
      };
      rd.readAsText(f);
    });
  }
})();
