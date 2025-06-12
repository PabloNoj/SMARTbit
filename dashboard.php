<?php
session_start();

// Seguridad: solo meseros
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'mesero') {
    header("Location: login.php");
    exit();
}

require 'conexion.php';

// Inicializar pedidos en sesión
if (!isset($_SESSION['pedidos'])) {
    $_SESSION['pedidos'] = [];
}

// Cargar productos activos
$productosArr = [];
$res = $conn->query("SELECT id, nombre, precio FROM productos WHERE activo = 1");
while ($row = $res->fetch_assoc()) {
    $productosArr[] = $row;
}
$res->free();

// JSON para JS
$pedidosJson   = json_encode($_SESSION['pedidos'], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS);
$productosJson = json_encode($productosArr,       JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard Mesero</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Segoe UI, Tahoma, Verdana, sans-serif; }
    body {
      display:flex; min-height:100vh;
      background:linear-gradient(rgba(246,152,62,0.9), rgba(246,152,62,0.9)),
                 url('img/portada.jpeg') center/cover no-repeat;
      color:#2c3e50;
    }
    .sidebar { width:220px; background:#2c3e50; color:#fff; padding:20px; display:flex; flex-direction:column; gap:10px; }
    .sidebar h2 { text-align:center; margin-bottom:20px; }
    .sidebar a { color:#fff; text-decoration:none; padding:10px; background:#34495e; border-radius:6px; text-align:center; transition:background .3s; }
    .sidebar a:hover { background:#1abc9c; }
    .main { flex:1; padding:30px; overflow-y:auto; }
    .main h2 { margin-bottom:20px; }
    .mesas { display:grid; grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); gap:15px; }
    .mesa {
      background:#FDD58D; height:100px; display:flex; align-items:center; justify-content:center;
      border-radius:12px; cursor:pointer; box-shadow:0 4px 8px rgba(0,0,0,0.1);
      transition:transform .2s, background .3s; user-select:none;
    }
    .mesa:hover { transform:scale(1.05); }
    .mesa.ocupada { background:#dc3545; color:#fff; }
    .overlay {
      position:fixed; top:0; left:0; right:0; bottom:0;
      background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:1000;
    }
    .overlay.active { display:flex; }
    .modal {
      background:#fff; border-radius:8px; padding:20px; width:800px; max-width:95%; max-height:90vh;
      overflow-y:auto; position:relative;
    }
    .modal-header {
      display:flex; justify-content:space-between; align-items:center;
      border-bottom:2px solid #eee; margin-bottom:15px;
    }
    .modal-header span { font-size:24px; font-weight:bold; }
    .close-btn { background:transparent; border:none; font-size:28px; cursor:pointer; color:#888; }
    .close-btn:hover { color:#e74c3c; }
    .comensales-section { margin-bottom:15px; }
    .comensales-section label { display:block; margin-bottom:5px; }
    .comensales-section input,
    .comensales-section select {
      width:100%; padding:6px; border:1px solid #ccc; border-radius:6px; margin-bottom:10px;
    }
    .error-msg { color:#c0392b; font-size:.9rem; margin-bottom:10px; }
    .menu-section {
      display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr));
      gap:15px; margin-bottom:20px;
    }
    .menu-item {
      background:#fefefe; border:1px solid #ccc; border-radius:8px; padding:10px;
      text-align:center; cursor:pointer; transition:background .3s;
    }
    .menu-item:hover { background:#f1f1f1; }
    .menu-item span { display:block; font-weight:bold; margin:5px 0; }
    .pedido-section h3 { margin-bottom:10px; }
    .pedido-list {
      list-style:none; max-height:200px; overflow-y:auto;
      margin-bottom:10px; padding:0;
    }
    .pedido-list li {
      display:flex; justify-content:space-between;
      background:#f1f1f1; padding:6px 10px; border-radius:6px; margin-bottom:6px;
    }
    .pedido-list button {
      background:#e74c3c; color:#fff; border:none;
      padding:2px 8px; border-radius:4px; cursor:pointer;
    }
    .total { font-weight:bold; text-align:right; margin-bottom:15px; }
    .btn-finalizar, .btn-cuenta {
      width:100%; padding:12px; border:none; border-radius:8px;
      font-size:1rem; cursor:pointer; margin-bottom:10px; transition:background .3s;
    }
    .btn-finalizar { background:#27ae60; color:#fff; }
    .btn-finalizar:hover { background:#1e8449; }
    .btn-cuenta { background:#2980b9; color:#fff; }
    .btn-cuenta:hover { background:#1b4f72; }
  </style>
</head>
<body>

  <div class="sidebar">
    <h2>Mesero</h2>
    <a href="#">Tomar Pedido</a>
    <a href="logout.php">Cerrar sesión</a>
  </div>

  <main class="main">
    <h2>Mesas</h2>
    <div class="mesas" id="mesas">
      <?php for ($i = 1; $i <= 10; $i++):
        $ocupada = isset($_SESSION['pedidos'][$i]) ? 'ocupada' : '';
      ?>
        <div class="mesa <?= $ocupada ?>" data-mesa="<?= $i ?>" tabindex="0">
          Mesa <?= $i ?><?= $ocupada ? ' – Ocupada' : '' ?>
        </div>
      <?php endfor; ?>
    </div>
  </main>

  <div class="overlay" id="overlay">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <header class="modal-header">
        <span id="modalTitle">Mesa</span>
        <button class="close-btn" id="btnCerrarModal">&times;</button>
      </header>

      <section class="comensales-section">
        <label for="inputComensales">Número de comensales</label>
        <input type="number" id="inputComensales" min="1" max="12" value="1" />
        <div id="errorComensales" class="error-msg" aria-live="polite"></div>

        <label for="selectComensal">Comensal activo</label>
        <select id="selectComensal"></select>
      </section>

      <section class="menu-section" id="menuProductos" aria-label="Menú de productos">
        <!-- Productos inyectados por JS -->
      </section>

      <section class="pedido-section">
        <h3>Pedido Comensal Actual</h3>
        <ul class="pedido-list" id="listaPedido" aria-live="polite"></ul>
        <div class="total" id="totalPedido">Total: $0.00</div>
        <button class="btn-finalizar" id="btnGuardarPedido">Guardar pedido</button>
        <button class="btn-cuenta" id="btnFinalizarCuenta">Finalizar Cuenta</button>
      </section>
    </div>
  </div>

  <script>
  (() => {
    const pedidos   = <?= $pedidosJson ?>;
    const productos = <?= $productosJson ?>;

    let mesaActual     = null,
        pedidoActual   = { comensales:1, pedidosComensales:[[]] },
        comensalActivo = 0;

    const mesas           = document.querySelectorAll('.mesa'),
          overlay         = document.getElementById('overlay'),
          modalTitle      = document.getElementById('modalTitle'),
          btnCerrarModal  = document.getElementById('btnCerrarModal'),
          inputComensales = document.getElementById('inputComensales'),
          errorComensales = document.getElementById('errorComensales'),
          selectComensal  = document.getElementById('selectComensal'),
          menuProductos   = document.getElementById('menuProductos'),
          listaPedido     = document.getElementById('listaPedido'),
          totalPedido     = document.getElementById('totalPedido'),
          btnGuardarPedido= document.getElementById('btnGuardarPedido'),
          btnFinalizarCuenta = document.getElementById('btnFinalizarCuenta');

    function abrirModal(mesa) {
      mesaActual = mesa;
      modalTitle.textContent = `Mesa ${mesaActual}`;

      if (pedidos[mesaActual]) {
        pedidoActual = {
          comensales: pedidos[mesaActual].comensales,
          pedidosComensales: pedidos[mesaActual].pedidosComensales
        };
      } else {
        pedidoActual = { comensales:1, pedidosComensales:[[]] };
      }

      inputComensales.value = pedidoActual.comensales;
      errorComensales.textContent = '';
      actualizarSelectComensales();
      comensalActivo = 0;
      selectComensal.selectedIndex = 0;
      renderMenu();
      renderPedido();
      overlay.classList.add('active');
      inputComensales.focus();
    }

    function cerrarModal() {
      overlay.classList.remove('active');
      mesaActual = null;
    }

    function actualizarSelectComensales() {
      selectComensal.innerHTML = '';
      for (let i = 0; i < pedidoActual.comensales; i++) {
        const opt = document.createElement('option');
        opt.value = i;
        opt.textContent = `Comensal ${i+1}`;
        selectComensal.appendChild(opt);
      }
    }

    function renderMenu() {
      menuProductos.innerHTML = '';
      productos.forEach(p => {
        const div = document.createElement('div');
        div.className = 'menu-item';
        div.setAttribute('tabindex','0');
        div.dataset.id = p.id;
        div.innerHTML = `<span>${p.nombre}</span><span>$${parseFloat(p.precio).toFixed(2)}</span>`;
        div.addEventListener('click', ()=> agregarProducto(p.id));
        div.addEventListener('keydown', e => {
          if (e.key==='Enter'||e.key===' ') {
            e.preventDefault();
            agregarProducto(p.id);
          }
        });
        menuProductos.appendChild(div);
      });
    }

    function agregarProducto(idProd) {
      const lista = pedidoActual.pedidosComensales[comensalActivo]||[];
      const prod  = productos.find(x=>x.id===idProd);
      if (!prod) return;
      const item = lista.find(x=>x.id===idProd);
      if (item) item.cantidad++;
      else lista.push({ id:prod.id, nombre:prod.nombre, precio:parseFloat(prod.precio), cantidad:1 });
      pedidoActual.pedidosComensales[comensalActivo]=lista;
      renderPedido();
    }

    function renderPedido() {
      listaPedido.innerHTML = '';
      let total=0;
      (pedidoActual.pedidosComensales[comensalActivo]||[]).forEach((it,i)=>{
        const li = document.createElement('li');
        const subtotal = it.precio*it.cantidad;
        total += subtotal;
        li.textContent = `${it.nombre} x${it.cantidad} – $${subtotal.toFixed(2)}`;
        const btn = document.createElement('button');
        btn.textContent = 'X';
        btn.setAttribute('aria-label',`Eliminar ${it.nombre}`);
        btn.addEventListener('click', ()=>{
          pedidoActual.pedidosComensales[comensalActivo].splice(i,1);
          renderPedido();
        });
        li.appendChild(btn);
        listaPedido.appendChild(li);
      });
      totalPedido.textContent = `Total: $${total.toFixed(2)}`;
    }

    inputComensales.addEventListener('input', ()=>{ /* igual que antes */ });
    selectComensal.addEventListener('change', ()=>{ /* igual que antes */ });

    btnCerrarModal.addEventListener('click', cerrarModal);
    overlay.addEventListener('click', e=>{ if(e.target===overlay) cerrarModal(); });

    btnGuardarPedido.addEventListener('click', ()=>{ /* igual que antes */ });
    btnFinalizarCuenta.addEventListener('click', ()=>{ /* igual que antes */ });

    mesas.forEach(m=>{
      const n=parseInt(m.dataset.mesa,10);
      m.addEventListener('click', ()=> abrirModal(n));
      m.addEventListener('keydown', e=>{
        if(e.key==='Enter'||e.key===' '){
          e.preventDefault();
          abrirModal(n);
        }
      });
    });
  })();
  </script>
</body>
</html>
