<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/layout.php';
require_login();
adminlte_head('Pustaka Media', 'media');
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0 d-flex align-items-center gap-2"><i data-lucide="image"></i> Pustaka Media</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dasbor</a></li>
            <li class="breadcrumb-item active">Media</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="card" id="panelUnggah">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h3 class="card-title mb-0">Unggah Berkas Media <small id="countInfo" class="text-muted fw-normal"></small></h3>
          <div class="d-flex gap-2">
            <input type="file" id="fileInput" accept="image/*" class="form-control form-control-sm" style="max-width:260px">
            <button class="btn btn-sm btn-primary" id="btnUpload"><i data-lucide="upload"></i> Unggah</button>
            <button class="btn btn-sm btn-secondary" id="btnRefresh"><i data-lucide="refresh-cw"></i> Muat Ulang</button>
          </div>
        </div>
        <div class="card-body">
          <div id="msg" class="mb-3"></div>
          <div class="row g-3" id="mediaGrid"></div>
        </div>
        <div class="card-footer text-center" id="moreWrap" style="display:none">
          <button class="btn btn-sm btn-outline-primary" id="btnMore"><i data-lucide="chevron-down"></i> Muat Lainnya</button>
        </div>
      </div>
    </div>
  </div>
</main>
<script>
const grid = document.getElementById('mediaGrid');
const fileInput = document.getElementById('fileInput');
const btnUpload = document.getElementById('btnUpload');
const btnRefresh = document.getElementById('btnRefresh');
const btnMore = document.getElementById('btnMore');
const moreWrap = document.getElementById('moreWrap');
const countInfo = document.getElementById('countInfo');
const msg = document.getElementById('msg');
const PAGE_LIMIT = 48;
let page = 1, shown = 0, total = 0;

function showMsg(text, type='info'){
  msg.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show">${text}<button class="btn-close" data-bs-dismiss="alert"></button></div>`;
  setTimeout(()=>{msg.innerHTML='';}, 4000);
}

function cardEl(f){
  const col = document.createElement('div');
  col.className = 'col-12 col-sm-6 col-md-4 col-lg-3';
  col.innerHTML = `
    <div class="card h-100">
      <img src="${f.url}" class="card-img-top" style="height:160px;object-fit:cover" loading="lazy" onerror="this.src='https://placehold.co/400x200?text=No+Image'">
      <div class="card-body p-2">
        <div class="small text-truncate" title="${f.name}">${f.name}</div>
        <div class="d-flex gap-2 mt-2 flex-wrap">
          <button class="btn btn-sm btn-outline-primary" data-copy="${f.url}" title="Salin URL"><i data-lucide="copy"></i></button>
          <button class="btn btn-sm btn-outline-secondary" data-mdcopy="${f.url}" data-name="${f.name}" title="Salin Markdown"><i data-lucide="copy-check"></i></button>
          <button class="btn btn-sm btn-outline-danger" data-del="${f.name}" title="Hapus berkas"><i data-lucide="trash-2"></i></button>
        </div>
      </div>
    </div>`;
  col.querySelector('[data-copy]').addEventListener('click', async (e)=>{
    try{ await navigator.clipboard.writeText(f.url); showMsg('URL disalin ke papan klip.', 'success'); }catch(err){ showMsg('Gagal menyalin URL.', 'warning'); }
  });
  col.querySelector('[data-mdcopy]').addEventListener('click', async ()=>{
    try{ await navigator.clipboard.writeText(`![${f.name}](${f.url})`); showMsg('Markdown tersalin ke papan klip.', 'success'); }catch(err){ showMsg('Gagal menyalin Markdown.', 'warning'); }
  });
  col.querySelector('[data-del]').addEventListener('click', async ()=>{
    if(!confirm('Hapus berkas ini?')) return;
    const res = await fetch('<?= base_url('api/media.php') ?>?id='+encodeURIComponent(f.name),{method:'DELETE',credentials:'same-origin'});
    const data = await res.json();
    if(data.ok){ showMsg('Berkas dihapus.', 'success'); load(true); } else showMsg(data.error||'Gagal menghapus berkas.', 'danger');
  });
  return col;
}

async function load(reset){
  if(reset){ page = 1; shown = 0; grid.innerHTML = ''; }
  btnMore.disabled = true;
  btnMore.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memuat...';
  try{
    const res = await fetch('<?= base_url('api/media.php') ?>?limit='+PAGE_LIMIT+'&page='+page,{credentials:'same-origin'});
    if (!res.ok) { showMsg('Gagal memuat media (HTTP ' + res.status + ').', 'danger'); return; }
    const data = await res.json();
    if(!data.ok){ showMsg(data.error||'Gagal memuat', 'danger'); return; }
    total = data.total ?? data.files.length;
    if(page === 1 && !data.files.length){ grid.innerHTML = '<div class="col-12 text-muted">Belum ada berkas media.</div>'; }
    for(const f of data.files){ grid.appendChild(cardEl(f)); }
    shown += data.files.length;
    countInfo.textContent = total ? `(menampilkan ${shown} dari ${total})` : '';
    moreWrap.style.display = data.has_more ? '' : 'none';
    if(window.lucide) lucide.createIcons();
  }catch(e){ showMsg('Gagal memuat media', 'danger'); }
  btnMore.disabled = false;
  btnMore.innerHTML = '<i data-lucide="chevron-down"></i> Muat Lainnya';
  if(window.lucide) lucide.createIcons();
}

btnRefresh.addEventListener('click', ()=>load(true));
btnMore.addEventListener('click', ()=>{ page++; load(false); });
btnUpload.addEventListener('click', async ()=>{
  const file = fileInput.files[0];
  if(!file){ showMsg('Pilih berkas gambar terlebih dahulu.', 'warning'); return; }
  const fd = new FormData();
  fd.append('file', file);
  btnUpload.disabled = true;
  btnUpload.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mengunggah...';
  try{
    const res = await fetch('<?= base_url('api/media.php') ?>',{method:'POST',body:fd,credentials:'same-origin'});
    const data = await res.json();
    if(data.ok){ showMsg('Berkas berhasil diunggah.', 'success'); fileInput.value=''; load(true); } else showMsg(data.error||'Gagal mengunggah.', 'danger');
  }catch(e){ showMsg('Gagal mengunggah.', 'danger'); }
  btnUpload.disabled=false; btnUpload.innerHTML='<i data-lucide="upload"></i> Unggah';
  if(window.lucide) lucide.createIcons();
});

load(true);

// Deep-link dari submenu Media > Tambah Baru (?fokus=unggah):
// gulir ke panel unggah dan fokuskan pemilih berkas.
if (new URLSearchParams(location.search).get('fokus') === 'unggah') {
  document.getElementById('panelUnggah')?.scrollIntoView({block: 'start'});
  fileInput.focus({preventScroll: true});
  showMsg('Pilih gambar lalu klik Unggah.', 'info');
}
</script>
<?php adminlte_footer(); ?>
</body></html>
