<?php
// ...existing code...
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel - Gestão Ponto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Dashboard</h1>
    <div>
      <button id="btnNovaObra" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novaObraModal">Nova Obra</button>
      <button id="btnNovoFuncionario" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novoFuncionarioModal">Novo Funcionário</button>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Obras</h5>
          <ul id="lista-obras" class="list-group list-group-flush"></ul>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Funcionários</h5>
          <ul id="lista-funcionarios" class="list-group list-group-flush"></ul>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Novo Funcionário -->
<div class="modal fade" id="novoFuncionarioModal" tabindex="-1" aria-labelledby="novoFuncionarioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formNovoFuncionario" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="novoFuncionarioLabel">Novo Funcionário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div id="novoFuncionarioAlert"></div>
        <div class="mb-3">
          <label class="form-label">Nome</label>
          <input name="name" id="funcName" class="form-control" required maxlength="255">
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input name="email" id="funcEmail" type="email" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Telefone</label>
          <input name="phone" id="funcPhone" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Associar à Obra (opcional)</label>
          <select id="funcObraSelect" name="obra_id" class="form-select">
            <option value="">-- Nenhuma --</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Status da Associação</label>
          <select id="funcAssocStatus" class="form-select">
            <option value="">-- Padrão (ativo) --</option>
            <option value="ativo">Ativo</option>
            <option value="suspenso">Suspenso</option>
            <option value="concluido">Concluído</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success">Criar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Funcionário -->
<div class="modal fade" id="editarFuncionarioModal" tabindex="-1" aria-labelledby="editarFuncionarioLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formEditarFuncionario" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editarFuncionarioLabel">Editar Funcionário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div id="editarFuncionarioAlert"></div>
        <input type="hidden" id="editarFuncId" name="id">
        <div class="mb-3">
          <label class="form-label">Nome</label>
          <input name="name" id="editarFuncName" class="form-control" required maxlength="255">
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input name="email" id="editarFuncEmail" type="email" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Telefone</label>
          <input name="phone" id="editarFuncPhone" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Associar/Atualizar Obra</label>
          <select id="editarFuncObraSelect" name="obra_id" class="form-select">
            <option value="">-- Nenhuma --</option>
          </select>
          <div class="form-text">Se escolher uma obra, o funcionário será associado a ela.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Função (opcional)</label>
          <input name="role" id="editarFuncRole" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Status da Associação</label>
          <select id="editarFuncAssocStatus" class="form-select">
            <option value="">-- Padrão (ativo) --</option>
            <option value="ativo">Ativo</option>
            <option value="suspenso">Suspenso</option>
            <option value="concluido">Concluído</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Nova Obra -->
<div class="modal fade" id="novaObraModal" tabindex="-1" aria-labelledby="novaObraModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formNovaObra" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="novaObraModalLabel">Nova Obra</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div id="obraAlert"></div>
        <div class="mb-3">
          <label for="obraName" class="form-label">Nome da Obra</label>
          <input type="text" id="obraName" name="name" class="form-control" required maxlength="255">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Criar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Obra -->
<div class="modal fade" id="editarObraModal" tabindex="-1" aria-labelledby="editarObraModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formEditarObra" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editarObraModalLabel">Editar Obra</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div id="editarObraAlert"></div>
        <input type="hidden" id="editarObraId" name="id">
        <div class="mb-3">
          <label for="editarObraName" class="form-label">Nome da Obra</label>
          <input type="text" id="editarObraName" name="name" class="form-control" required maxlength="255">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
  const listaObras = document.getElementById('lista-obras');
  const listaFuncs = document.getElementById('lista-funcionarios');

  // selects/forms (assume modals include these IDs)
  const funcObraSelect = document.getElementById('funcObraSelect');
  const funcAssocStatus = document.getElementById('funcAssocStatus'); // novo
  const editarObraSelect = document.getElementById('editarFuncObraSelect');
  const editarAssocStatus = document.getElementById('editarFuncAssocStatus'); // novo in edit modal

  function statusBadge(status) {
    if (!status) return '<span class="badge bg-secondary">sem status</span>';
    if (status === 'ativo') return '<span class="badge bg-success">'+escapeHtml(status)+'</span>';
    if (status === 'suspenso') return '<span class="badge bg-warning text-dark">'+escapeHtml(status)+'</span>';
    if (status === 'concluido') return '<span class="badge bg-primary">'+escapeHtml(status)+'</span>';
    return '<span class="badge bg-secondary">'+escapeHtml(status)+'</span>';
  }

  function escapeHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function escapeAttr(s) { return String(s).replace(/"/g,'&quot;'); }

  async function carregarObras() {
    try {
      const resp = await fetch('list_obras.php');
      if (!resp.ok) return;
      const data = await resp.json();
      if (!data.success) return;
      listaObras.innerHTML = '';
      funcObraSelect.innerHTML = '<option value="">-- Nenhuma --</option>';
      editarObraSelect.innerHTML = '<option value="">-- Nenhuma --</option>';
      data.obras.forEach(o => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';
        li.dataset.id = o.id;
        li.innerHTML = '<span class="obra-name">'+escapeHtml(o.name)+'</span>' +
          '<div><button class="btn btn-sm btn-outline-secondary btn-editar-obra" data-id="'+o.id+'" data-name="'+escapeAttr(o.name)+'">Editar</button></div>';
        listaObras.appendChild(li);

        const opt = document.createElement('option'); opt.value = o.id; opt.textContent = o.name;
        funcObraSelect.appendChild(opt);
        editarObraSelect.appendChild(opt.cloneNode(true));
      });
    } catch (e) { console.error(e); }
  }

  async function carregarFuncionarios() {
    try {
      const resp = await fetch('list_funcionarios.php');
      if (!resp.ok) return;
      const data = await resp.json();
      if (!data.success) return;
      listaFuncs.innerHTML = '';
      data.funcionarios.forEach(f => listaFuncs.appendChild(renderFuncionarioItem(f)));
    } catch (e) { console.error(e); }
  }

  function renderFuncionarioItem(f) {
    const li = document.createElement('li');
    li.className = 'list-group-item';
    li.dataset.id = f.id;

    const header = document.createElement('div'); header.className = 'd-flex justify-content-between';
    const title = document.createElement('div'); title.className = 'fw-bold'; title.textContent = f.name;
    const actions = document.createElement('div');
    actions.innerHTML = '<button class="btn btn-sm btn-outline-primary btn-editar-func" data-id="'+f.id+'">Editar</button>';
    header.appendChild(title); header.appendChild(actions);

    const meta = document.createElement('div'); meta.className = 'small text-muted';
    meta.textContent = (f.email ? f.email + ' • ' : '') + (f.phone || '');

    const obrasWrap = document.createElement('div'); obrasWrap.className = 'mt-2';
    if (f.obras && f.obras.length) {
      f.obras.forEach(o => {
        const span = document.createElement('span');
        span.className = 'badge me-1';
        // status badge inserted
        span.innerHTML = escapeHtml(o.name) + ' ' + (o.status ? ('<span class="badge '+ (o.status==='ativo' ? 'bg-success' : (o.status==='suspenso' ? 'bg-warning text-dark' : 'bg-secondary')) +' ms-1">'+escapeHtml(o.status)+'</span>') : '');
        obrasWrap.appendChild(span);
      });
    } else {
      obrasWrap.innerHTML = '<small class="text-muted">Sem obras</small>';
    }

    li.appendChild(header);
    li.appendChild(meta);
    li.appendChild(obrasWrap);
    return li;
  }

  // ouvir editar funcionário
  listaFuncs.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-editar-func');
    if (!btn) return;
    const id = btn.dataset.id;
    abrirEditarFuncionario(id);
  });

  // abrir editar funcionário — preenche status conforme primeira associação (se existir)
  async function abrirEditarFuncionario(id) {
    try {
      const resp = await fetch('list_funcionarios.php');
      if (!resp.ok) return;
      const data = await resp.json();
      if (!data.success) return;
      const f = data.funcionarios.find(x => x.id == id);
      if (!f) return;
      document.getElementById('editarFuncId').value = f.id;
      document.getElementById('editarFuncName').value = f.name;
      document.getElementById('editarFuncEmail').value = f.email || '';
      document.getElementById('editarFuncPhone').value = f.phone || '';
      // selecionar obra e status (usa primeira associação como default)
      if (f.obras && f.obras.length) {
        document.getElementById('editarFuncObraSelect').value = f.obras[0].id;
        document.getElementById('editarFuncAssocStatus').value = f.obras[0].status || 'ativo';
      } else {
        document.getElementById('editarFuncObraSelect').value = '';
        document.getElementById('editarFuncAssocStatus').value = '';
      }
      // abrir modal
      const modalEl = document.getElementById('editarFuncionarioModal');
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    } catch (e) { console.error(e); }
  }

  // criar funcionário: envia status se selecionado
  const formNovo = document.getElementById('formNovoFuncionario');
  if (formNovo) {
    formNovo.addEventListener('submit', async function(e){
      e.preventDefault();
      const name = document.getElementById('funcName').value.trim();
      if (!name) return;
      const body = new URLSearchParams({
        name,
        email: document.getElementById('funcEmail').value.trim(),
        phone: document.getElementById('funcPhone').value.trim(),
        obra_id: document.getElementById('funcObraSelect').value || '',
        status: document.getElementById('funcAssocStatus') ? document.getElementById('funcAssocStatus').value : ''
      });
      const resp = await fetch('create_funcionario.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body });
      const data = await resp.json();
      if (data.success) {
        await carregarFuncionarios();
        const modalEl = document.getElementById('novoFuncionarioModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.hide();
      } else {
        console.error(data.error);
      }
    });
  }

  // salvar edição de funcionário e associar com status
  const formEditar = document.getElementById('formEditarFuncionario');
  if (formEditar) {
    formEditar.addEventListener('submit', async function(e){
      e.preventDefault();
      const id = document.getElementById('editarFuncId').value;
      const body = new URLSearchParams({
        id,
        name: document.getElementById('editarFuncName').value.trim(),
        email: document.getElementById('editarFuncEmail').value.trim(),
        phone: document.getElementById('editarFuncPhone').value.trim()
      });
      const resp = await fetch('update_funcionario.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body });
      const data = await resp.json();
      if (!data.success) {
        console.error(data.error);
        return;
      }
      const obraId = document.getElementById('editarFuncObraSelect').value;
      const status = document.getElementById('editarFuncAssocStatus') ? document.getElementById('editarFuncAssocStatus').value : '';
      const role = document.getElementById('editarFuncRole').value.trim();
      if (obraId) {
        await fetch('assign_funcionario.php', {
          method: 'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded'},
          body: new URLSearchParams({ obra_id: obraId, funcionario_id: id, role, status })
        });
      }
      await carregarFuncionarios();
      const modalEl = document.getElementById('editarFuncionarioModal');
      const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      modal.hide();
    });
  }

  // editar obra (já existente)
  listaObras.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-editar-obra');
    if (!btn) return;
    const id = btn.dataset.id;
    const name = btn.dataset.name || btn.getAttribute('data-name') || '';
    document.getElementById('editarObraId').value = id;
    document.getElementById('editarObraName').value = name;
    const modalEl = document.getElementById('editarObraModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  });

  // criar/editar obra handlers (assume create_obra.php & update_obra.php existem)
  const formNovaObra = document.getElementById('formNovaObra');
  if (formNovaObra) formNovaObra.addEventListener('submit', async function(e){
    e.preventDefault();
    const name = document.getElementById('obraName').value.trim();
    if (!name) return;
    const resp = await fetch('create_obra.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ name }) });
    const data = await resp.json();
    if (data.success) {
      await carregarObras();
    } else console.error(data.error);
    const modalEl = document.getElementById('novaObraModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.hide();
  });

  const formEditarObra = document.getElementById('formEditarObra');
  if (formEditarObra) formEditarObra.addEventListener('submit', async function(e){
    e.preventDefault();
    const id = document.getElementById('editarObraId').value;
    const name = document.getElementById('editarObraName').value.trim();
    if (!id || !name) return;
    const resp = await fetch('update_obra.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ id, name }) });
    const data = await resp.json();
    if (data.success) {
      await carregarObras();
    } else console.error(data.error);
    const modalEl = document.getElementById('editarObraModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.hide();
  });

  // init
  (async function init(){ await carregarObras(); await carregarFuncionarios(); })();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// ...existing code...
?>