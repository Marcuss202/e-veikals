<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
    // User is not admin, redirect to login page
    header("Location: ../views/loginRegister.html?error=access_denied");
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Admin - e-veikals</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="../assets/styles/admin.css">
</head>
<body>
  <div style="display:flex;align-items:center;gap:12px;justify-content:space-between;">
    <h1 style="margin:0;">Admin panel</h1>
    <div>
      <a href="../index.html" class="btn" style="background: #6c757d; margin-right: 10px;">← Back to Site</a>
      <button id="showCreateBtn" class="btn">Create NEW</button>
    </div>
  </div>
  <div class="container">
    <div class="left">
      <div id="bigCard" class="card">
        <div class="small">Select an item from the list to view / edit</div>
        <img id="bigImage" src="" alt="" style="display:none">
        <h2 id="bigTitle">No item selected</h2>
        <p id="bigDesc"></p>
        <div class="small">Category: <span id="bigCategory"></span></div>
        <div class="small">Likes: <span id="bigLikes"></span> · Views: <span id="bigViews"></span></div>
      </div>

      <div style="height:16px;"></div>

      <div class="card" id="createCard">
        <h3>Create new item</h3>
        <form id="createForm" enctype="multipart/form-data">
          <div class="row"><input name="title" placeholder="Title" required></div>
          <div class="row"><textarea name="description" placeholder="Description" rows="3" required></textarea></div>
          <div class="row"><label>Image file (optional) <input type="file" name="image_file" accept="image/*"></label></div>
          <div class="row"><input name="hashtags" placeholder="Hashtag" required></div>
          <div class="row"><button class="btn primary" type="submit">Create</button></div>
        </form>
        <div id="createMsg" class="small"></div>
      </div>

      <!-- Edit card moved here so create/edit toggle on left column -->
      <div class="card hidden" id="editCard">
        <h3>Edit item <span id="editId"></span></h3>
        <form id="editForm" enctype="multipart/form-data">
          <input type="hidden" name="id">
          <input type="hidden" name="current_image_url">
          <div class="row"><input name="title" placeholder="Title" required></div>
          <div class="row"><textarea name="description" placeholder="Description" rows="3" required></textarea></div>
          <div class="row"><label>Replace image <input type="file" name="image_file" accept="image/*"></label></div>
          <div class="row"><input name="hashtags" placeholder="Hashtags (comma-separated)" required></div>
          <div class="row">
            <button class="btn primary" type="submit">Save</button>
            <button class="btn" type="button" id="cancelEdit">Cancel</button>
          </div>
        </form>
        <div id="editMsg" class="small"></div>
      </div>
    </div>

    <div class="right">
      <div class="card">
        <h3>All items</h3>
        <table id="itemsTable">
          <thead>
            <tr><th>ID</th><th>Title</th><th>Hashtags</th><th>Actions</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div style="height:12px;"></div>
      <div class="card" id="usersCard">
        <h3>Users</h3>
        <table id="usersTable">
          <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Admin</th><th>Actions</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

<script>
/* ...existing JavaScript from your previous admin.html... */
const apiBase = ''; // same folder

async function fetchItems() {
  const res = await fetch(apiBase + 'get_items.php');
  if(!res.ok) throw new Error('Failed to fetch items');
  return res.json();
}

function renderList(items) {
  const tbody = document.querySelector('#itemsTable tbody');
  tbody.innerHTML = '';
  items.forEach(it => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${it.id}</td>
      <td><a href="#" data-id="${it.id}" class="select">${escapeHtml(it.title)}</a></td>
      <td>${escapeHtml(it.category ?? '')}</td>
      <td class="actions">
        <button class="btn" data-action="edit" data-id="${it.id}">Edit</button>
        <button class="btn" data-action="delete" data-id="${it.id}">Delete</button>
      </td>`;
    tbody.appendChild(tr);
  });
}

function escapeHtml(s){ return String(s || '').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

function showBig(item) {
  if(!item){ document.getElementById('bigTitle').textContent = 'No item selected'; document.getElementById('bigImage').style.display='none'; document.getElementById('bigDesc').textContent=''; return; }
  document.getElementById('bigTitle').textContent = item.title;
  const img = document.getElementById('bigImage');
  if(item.image_url){ img.src = item.image_url; img.style.display='block'; } else img.style.display='none';
  document.getElementById('bigDesc').textContent = item.description || '';
  document.getElementById('bigCategory').textContent = item.category || '';
  document.getElementById('bigLikes').textContent = item.likes ?? 0;
  document.getElementById('bigViews').textContent = item.views ?? 0;
}

async function reload() {
  try {
    const items = await fetchItems();
    renderList(items);
    showBig(null);
  } catch (e) {
    alert('Error loading items: ' + e.message);
  }
}

// Users management
// expose current user id to client-side JS so we can disable self-actions
const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;

async function fetchUsers() {
  const res = await fetch(apiBase + 'get_users.php');
  if(!res.ok) throw new Error('Failed to fetch users');
  return res.json();
}

function renderUsers(users) {
  const tbody = document.querySelector('#usersTable tbody');
  tbody.innerHTML = '';
  users.forEach(u => {
    const tr = document.createElement('tr');
    // Disable checkbox and delete button for the current logged-in user
    const disabledAttr = (u.id == currentUserId) ? 'disabled' : '';
    const checkboxChecked = u.isAdmin == 1 ? 'checked' : '';
    tr.innerHTML = `
      <td>${u.id}</td>
      <td>${escapeHtml(u.username)}</td>
      <td>${escapeHtml(u.email)}</td>
      <td><input type="checkbox" data-action="admin-checkbox" data-id="${u.id}" ${checkboxChecked} ${disabledAttr}></td>
      <td class="actions">
        <button class="btn danger" data-action="delete-user" data-id="${u.id}" ${disabledAttr}>Delete</button>
      </td>`;
    tbody.appendChild(tr);
  });
}

async function reloadUsers() {
  try {
    const users = await fetchUsers();
    renderUsers(users);
  } catch (e) {
    console.error('Error loading users', e);
  }
}

document.addEventListener('click', async (ev) => {
  const a = ev.target.closest('a.select');
  if(a){ ev.preventDefault(); const id = a.dataset.id; const items = await fetchItems(); const item = items.find(x=>x.id==id); showBig(item); return; }
  const btn = ev.target.closest('button[data-action]');
  if(!btn) return;
  const id = btn.dataset.id;
  if(btn.dataset.action === 'delete'){
    if(!confirm('Delete item '+id+'?')) return;
    const res = await fetch(apiBase + 'delete_item.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
    const json = await res.json();
    if(json.success){ reload(); } else alert('Delete failed: '+json.message);
  } else if(btn.dataset.action === 'edit'){
    const items = await fetchItems();
    const item = items.find(x=>x.id==id);
    if(!item) return alert('Item not found');
    // populate edit form
    const editForm = document.getElementById('editForm');
    editForm.elements['id'].value = item.id;
    editForm.elements['title'].value = item.title;
    editForm.elements['description'].value = item.description;
    editForm.elements['hashtags'].value = item.category || '';
    editForm.elements['current_image_url'].value = item.image_url || '';
    // show edit, hide create
    document.getElementById('createCard').classList.add('hidden');
    document.getElementById('editCard').classList.remove('hidden');
    document.getElementById('editId').textContent = item.id;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  // Users actions
  if(btn.dataset.action === 'toggle-admin'){
    const uid = btn.dataset.id;
    const makeAdmin = btn.textContent.includes('Make Admin');
    if(!confirm((makeAdmin ? 'Grant' : 'Revoke') + ' admin for user ' + uid + '?')) return;
    const res = await fetch(apiBase + 'update_user.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id: uid, isAdmin: makeAdmin }) });
    const json = await res.json();
    if(json.success) reloadUsers(); else alert('Update failed');
  }

  if(btn.dataset.action === 'delete-user'){
    const uid = btn.dataset.id;
    if(!confirm('Delete user ' + uid + '?')) return;
    const res = await fetch(apiBase + 'delete_user.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id: uid }) });
    const json = await res.json();
    if(json.success) reloadUsers(); else alert('Delete failed');
  }
});

// Delegated handler for admin checkbox changes
document.querySelector('#usersTable').addEventListener('change', async (ev) => {
  const cb = ev.target.closest('input[data-action="admin-checkbox"]');
  if(!cb) return;
  const uid = cb.dataset.id;
  const isAdmin = cb.checked ? 1 : 0;
  if(!confirm((isAdmin ? 'Grant' : 'Revoke') + ' admin for user ' + uid + '?')) {
    // revert checkbox
    cb.checked = !cb.checked;
    return;
  }
  const res = await fetch(apiBase + 'update_user.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id: uid, isAdmin }) });
  const json = await res.json();
  if(!json.success) { alert('Update failed'); reloadUsers(); }
});

document.getElementById('createForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.currentTarget;
  const fd = new FormData(form);
  const res = await fetch(apiBase + 'add_item_with_sql.php', { method:'POST', body:fd });
  const json = await res.json();
  const msg = document.getElementById('createMsg');
  if(json.success){ msg.textContent = 'Created successfully'; form.reset(); reload(); }
  else msg.textContent = 'Create failed: ' + json.message;
});

document.getElementById('editForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.currentTarget);
  // send FormData so file upload works
  const res = await fetch(apiBase + 'update_item.php', { method:'POST', body: fd });
  const json = await res.json();
  const msg = document.getElementById('editMsg');
  if(json.success){ msg.textContent = 'Saved'; document.getElementById('editCard').classList.add('hidden'); document.getElementById('createCard').classList.remove('hidden'); reload(); }
  else msg.textContent = 'Save failed: ' + json.message;
});

document.getElementById('cancelEdit').addEventListener('click', () => {
  document.getElementById('editCard').classList.add('hidden');
  document.getElementById('createCard').classList.remove('hidden');
});

// show create button handler
document.getElementById('showCreateBtn').addEventListener('click', () => {
  document.getElementById('createCard').classList.remove('hidden');
  document.getElementById('editCard').classList.add('hidden');
});

reload();
reloadUsers();
</script>
</body>
</html>
