<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['SCRIPT_NAME']); 
$base_url = rtrim($protocol . '://' . $host . $path, '/') . '/';
$api_url = $base_url . 'api';

// Obtener ID de solicitud desde URL
$id_solicitud = isset($_GET['id']) ? (int)$_GET['id'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Solicitud de Reintegro de Combustible</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  body {background:#fff;font-family:"Segoe UI",sans-serif;font-size:0.95rem;}
  .container-fixed {width:1200px;margin:0 auto;}

  /* --- ICON HEADERS --- */
  .icon-toolbar{display:flex;align-items:center;}
  .icon-toolbar i{font-size:1.2rem;color:#6c757d;margin-left:0.75rem;cursor:pointer;transition:color .15s ease;}
  .icon-toolbar i:last-child{margin-right:1.2rem;}
  .icon-toolbar .bi-plus-circle:hover{color:green;}
  .icon-toolbar .bi-plus-circle:hover::before{content:"\f4f9";}
  .icon-toolbar .bi-play-circle:hover{color:blue;}
  .icon-toolbar .bi-play-circle:hover::before{content:"\f4f2";}
  .icon-toolbar .bi-dash-circle:hover{color:red;}
  .icon-toolbar .bi-dash-circle:hover::before{content:"\f2e5";}
  .icon-toolbar .bi-arrow-left-circle{margin-right:0.5rem;}
  .icon-toolbar .bi-arrow-left-circle:hover{color:#0d6efd;}

  /* --- HEADER --- */
  .header-title{font-size:1.35rem;font-weight:700;text-transform:uppercase;margin-top:1rem;margin-bottom:0.5rem;display:flex;align-items:center;gap:1rem;}
  .header-columns{display:flex;justify-content:flex-start;align-items:flex-start;gap:1.5rem;margin-left:20px;margin-bottom:0.75rem;}
  .header-left,.header-right{width:auto;}
  .header-table{border-collapse:collapse;}
  .header-table td{padding:3px 10px;vertical-align:middle;}
  .header-table strong{font-weight:600;}

  /* --- SECTION HEADERS --- */
  .section-header{display:flex;justify-content:space-between;align-items:center;margin-top:1.2rem;margin-bottom:0.4rem;}
  .section-title{text-transform:uppercase;font-weight:600;letter-spacing:.5px;margin:0;}

  /* --- TABLES --- */
  .table-custom{width:100%;border:1px solid #bdbdbd;border-collapse:collapse;}
  .table-custom th,.table-custom td{border:1px solid #bdbdbd!important;padding:.35rem .45rem;text-align:center;vertical-align:middle;}
  .table-custom thead th{background-color:#d6d6d6!important;color:#212529;font-weight:600;text-transform:uppercase;}
  .table-custom tbody tr:nth-child(odd) td{background-color:#fff!important;}
  .table-custom tbody tr:nth-child(even) td{background-color:#f3f3f3!important;}
  .table-custom tbody tr:hover td{background-color:#eef3f8!important;}
  .table-custom tfoot td,.facturas-totales td{background-color:#f3f3f3!important;font-weight:600;}
  .facturas-totales td.text-end{text-align:right!important;}
  .form-check{display:flex;justify-content:center;align-items:center;margin:0;}
  .form-check-input{cursor:pointer;}

  /* --- TABS --- */
  .nav-tabs{border-bottom:1px solid #dee2e6;margin-left:0;margin-top:1rem;}
  .nav-tabs .nav-link{border:1px solid transparent;border-top-left-radius:0.25rem;border-top-right-radius:0.25rem;color:#495057;font-weight:500;text-align:center;padding:0.5rem 1.5rem;transition:background-color .15s ease,color .15s ease;}
  .nav-tabs .nav-link:hover{background-color:#e9ecef;color:#212529;}
  .nav-tabs .nav-link.active{color:#212529;background-color:#e0e0e0;border-color:#dee2e6 #dee2e6 #dee2e6;}

  /* --- MODAL HEADER DARKEN --- */
  .modal-header{background-color:#e4e4e4;border-bottom:1px solid #ccc;}
  
  /* --- FUNCIONALIDAD --- */
  .text-end.pe-3 {padding-right:1rem;}
  .autocomplete-items {position:absolute;background:white;border:1px solid #ddd;z-index:999;max-height:150px;overflow-y:auto;}
  .autocomplete-items div {padding:8px;cursor:pointer;}
  .autocomplete-items div:hover {background:#f0f0f0;}
</style>
</head>

<body>
<div class="container-fixed">

  <!-- HEADER -->
  <div class="header-title">
    <i class="bi bi-arrow-left-circle" onclick="window.location.href='pregas.php'" title="Volver al listado" style="cursor:pointer;"></i>
    <span>SOLICITUD DE REINTEGRO DE COMBUSTIBLE</span>
  </div>
  <div class="header-columns">
    <div class="header-left">
      <table class="header-table">
        <tr><td><strong>Solicitud #</strong></td><td id="header-solicitud">-</td></tr>
        <tr><td><strong>Fecha</strong></td><td id="header-fecha">-</td></tr>
        <tr><td><strong>Período</strong></td><td id="header-periodo">-</td></tr>
        <tr><td><strong>Valor Litro</strong></td><td id="header-valor-litro">-</td></tr>
        <tr><td><strong>Factor GpK</strong></td><td id="header-factor-gpk">-</td></tr>
      </table>
    </div>
    <div class="header-right">
      <table class="header-table">
        <tr><td><strong>Empleado</strong></td><td id="header-empleado">-</td></tr>
        <tr><td><strong>Vehículo</strong></td><td id="header-vehiculo">-</td></tr>
        <tr><td><strong>Placa</strong></td><td id="header-placa">-</td></tr>
        <tr><td><strong>Tipo Combustible</strong></td><td id="header-combustible">-</td></tr>
        <tr><td><strong>Consumo Promedio</strong></td><td id="header-consumo">-</td></tr>
      </table>
    </div>
  </div>

  <!-- TABS -->
  <ul class="nav nav-tabs" id="reintegroTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" id="kilometraje-tab" data-bs-toggle="tab" data-bs-target="#kilometraje" type="button" role="tab">KILOMETRAJE</button></li>
    <li class="nav-item"><button class="nav-link" id="combustible-tab" data-bs-toggle="tab" data-bs-target="#combustible" type="button" role="tab">COMBUSTIBLE</button></li>
  </ul>

  <div class="tab-content" id="reintegroTabsContent">

    <!-- TAB 1 -->
    <div class="tab-pane fade show active" id="kilometraje" role="tabpanel">
      <div class="section-header mt-3">
        <h5 class="section-title">DETALLE KILOMETRAJE</h5>
        <div class="icon-toolbar">
          <i class="bi bi-plus-circle" id="addVisitaIcon"></i>
          <i class="bi bi-play-circle"></i>
          <i class="bi bi-dash-circle" id="deleteKmIcon"></i>
        </div>
      </div>
      <table class="table-custom text-center">
        <thead><tr><th>#</th><th></th><th>Fecha</th><th>Visita</th><th>Km Inicial</th><th>Km Final</th><th>Km Total</th><th>Cons. L</th><th>Costo</th><th>GpK</th><th>Total</th></tr></thead>
        <tbody id="tabla-km"></tbody>
        <tfoot id="totales-km"></tfoot>
      </table>
    </div>

    <!-- TAB 2 -->
    <div class="tab-pane fade" id="combustible" role="tabpanel">
      <div class="section-header mt-3">
        <h5 class="section-title">DETALLE FACTURAS COMBUSTIBLE</h5>
        <div class="icon-toolbar">
          <i class="bi bi-plus-circle" id="addCombustibleIcon"></i>
          <i class="bi bi-play-circle"></i>
          <i class="bi bi-dash-circle" id="deleteFacturaIcon"></i>
        </div>
      </div>
      <table class="table-custom text-center">
        <thead><tr><th>#</th><th></th><th>Fecha</th><th>No. Factura</th><th>Proveedor</th><th>Litros</th><th>Pagado</th><th>Costo L</th></tr></thead>
        <tbody id="tabla-facturas"></tbody>
        <tfoot id="totales-facturas" class="facturas-totales"></tfoot>
      </table>
    </div>
  </div>

<!-- NOTAS -->
<div class="card border-secondary-subtle bg-light mt-3 mb-5 rounded-2" style="width:100%;">
  <div class="card-body py-3 px-4" style="font-size:0.9rem;color:#444;line-height:1.45;">
    <p class="fw-semibold mb-2">NOTAS:</p>
    <ol class="mb-3 ps-3" style="margin-bottom:0.5rem;">
      <li class="mb-2">Se reconocerá el gasto de combustible únicamente cuando el vehículo sea utilizado para actividades directamente relacionadas con la labor de ventas, como visitas a clientes, reuniones comerciales, entrega de material promocional o cualquier otra actividad aprobada por la empresa.</li>
      <li class="mb-2">No se reconocerán gastos por desplazamientos personales, trayectos desde o hacia la residencia del empleado, ni viajes realizados fuera del horario laboral sin autorización previa.</li>
      <li class="mb-2">Quien reporta deberá registrar cada desplazamiento, indicando: Fecha, Prospecto quien visita, Kilometraje inicial y final según lectura de odómetro.</li>
      <li class="mb-2">Se deberá presentar este reporte, junto con facturas electrónicas de combustible.</li>
      <li class="mb-2">El reintegro se calculará con base en una tarifa fija por kilómetro recorrido, determinada por la empresa considerando: Precio del combustible utilizado, Consumo estimado del vehículo y un porcentaje adicional por mantenimiento y depreciación. La tarifa será revisada periódicamente y podrá ajustarse según las condiciones del mercado.</li>
      <li class="mb-2">Cualquier desplazamiento extraordinario deberá ser autorizado previamente por el supervisor directo.</li>
      <li class="mb-2">La empresa se reserva el derecho de auditar los registros y denegar reintegros en caso de inconsistencias o gastos no justificados.</li>
      <li class="mb-2">El reintegro se aplicará solo a vehículos previamente registrados en la empresa. Si el trabajador utiliza un vehículo distinto al habitual, deberá notificarlo previamente para su autorización.</li>
      <li class="mb-2">No se reembolsarán multas o reparaciones mecánicas de índole alguna. Estacionamiento y peajes deben de ser solicitados en el reporte de reintegro de gastos.</li>
      <li class="mb-2">Si el trabajador recibe un vehículo de la empresa con gastos de combustible cubiertos, no tendrá derecho a reembolso adicional alguno.</li>
      <li class="mb-2">El trabajador acepta que aplicar para este reintegro de combustible y gasto por depreciación y mantenimiento, no constituyen un salario en especie.</li>
    </ol>
    <p class="mb-0 fst-italic text-secondary small text-center">Material para uso interno Worldcom de Costa Rica, S.A. • ver. 2.5 • Nov 2025</p>
  </div>
</div>

<!-- MODALS -->
<div class="modal fade" id="addVisitaModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title fw-semibold">Agregando Visita</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-3 position-relative">
        <label class="form-label mb-1">Visita</label>
        <input type="text" class="form-control form-control-sm" id="buscar-prospecto" placeholder="Buscar prospecto..." autocomplete="off">
        <div id="autocomplete-prospecto" class="autocomplete-items" style="display:none;"></div>
        <input type="hidden" id="id_prospecto">
      </div>
      <div class="mb-3"><label class="form-label mb-1">Fecha</label><input type="date" class="form-control form-control-sm" id="modal-fecha-visita" required></div>
      <div class="mb-3"><label class="form-label mb-1">Km Inicial</label><input type="number" class="form-control form-control-sm" id="modal-km-inicial" min="0" step="1" required></div>
      <div class="mb-3"><label class="form-label mb-1">Km Final</label><input type="number" class="form-control form-control-sm" id="modal-km-final" min="0" step="1" required></div>
    </div>
    <div class="modal-footer py-2"><button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success btn-sm" id="addVisitaBtn">Agregar</button></div>
  </div></div>
</div>

<div class="modal fade" id="addCombustibleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title fw-semibold">Agregando Combustible</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-3"><label class="form-label mb-1">Fecha</label><input type="date" class="form-control form-control-sm" id="modal-fecha-factura" required></div>
      <div class="mb-3"><label class="form-label mb-1">No. Factura</label><input type="number" class="form-control form-control-sm" id="modal-numero-factura" inputmode="numeric" required></div>
      <div class="mb-3"><label class="form-label mb-1">Proveedor</label><input type="text" class="form-control form-control-sm" id="modal-proveedor" required></div>
      <div class="mb-3"><label class="form-label mb-1">Litros</label><input type="number" class="form-control form-control-sm" id="modal-litros" step="0.01" min="0" required></div>
      <div class="mb-3"><label class="form-label mb-1">Pagado</label><input type="number" class="form-control form-control-sm" id="modal-monto" step="0.01" min="0" required></div>
    </div>
    <div class="modal-footer py-2"><button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success btn-sm" id="addCombustibleBtn">Agregar</button></div>
  </div></div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API_URL = <?php echo json_encode($api_url); ?>;
let currentSolicitudId = <?php echo $id_solicitud ?: 'null'; ?>;
let prospectos = [];
let valorLitro = 677;
let factorGpK = 0.02;
let consumoPromedio = 0.165;
let vehiculoData = null;

// === INICIALIZACIÓN COMPLETA ===
document.addEventListener('DOMContentLoaded', async () => {
  console.log('DOM Cargado, ID:', currentSolicitudId);
  
  if (!currentSolicitudId) {
    alert('No se especificó ID de solicitud');
    window.location.href = 'pregas.php';
    return;
  }

  await cargarProspectos();
  
  const today = new Date().toISOString().split('T')[0];
  ['modal-fecha-visita', 'modal-fecha-factura'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = today;
  });
  
  // Event listeners para iconos
  const addVisitaIcon = document.getElementById('addVisitaIcon');
  const addCombustibleIcon = document.getElementById('addCombustibleIcon');
  const deleteKmIcon = document.getElementById('deleteKmIcon');
  const deleteFacturaIcon = document.getElementById('deleteFacturaIcon');
  
  if (addVisitaIcon) {
    addVisitaIcon.onclick = () => {
      const modal = new bootstrap.Modal(document.getElementById('addVisitaModal'));
      modal.show();
    };
  }
  
  if (addCombustibleIcon) {
    addCombustibleIcon.onclick = () => {
      const modal = new bootstrap.Modal(document.getElementById('addCombustibleModal'));
      modal.show();
    };
  }
  
  if (deleteKmIcon) {
    deleteKmIcon.onclick = () => eliminarSeleccionados('km');
  }
  
  if (deleteFacturaIcon) {
    deleteFacturaIcon.onclick = () => eliminarSeleccionados('factura');
  }
  
  // Event listeners para botones Agregar en modales
  document.getElementById('addVisitaBtn').onclick = confirmarAgregarKM;
  document.getElementById('addCombustibleBtn').onclick = confirmarAgregarFactura;

  // Cargar solicitud actual
  await verDetalle(currentSolicitudId);
});

// === PROSPECTOS ===
async function cargarProspectos() {
  try {
    const res = await fetch(`${API_URL}/prospectos.php`);
    prospectos = await res.json();
  } catch (e) {
    console.error('Error cargando prospectos:', e);
  }
}

// Autocomplete para búsqueda de prospectos
document.addEventListener('DOMContentLoaded', () => {
  const inputProspecto = document.getElementById('buscar-prospecto');
  if (inputProspecto) {
    inputProspecto.addEventListener('input', function() {
      const term = this.value.trim();
      const container = document.getElementById('autocomplete-prospecto');
      container.innerHTML = '';
      
      if (!term) {
        container.style.display = 'none';
        return;
      }
      
      const matches = prospectos.filter(p => 
        p.nombre_prospecto.toLowerCase().includes(term.toLowerCase()) ||
        p.empresa?.toLowerCase().includes(term.toLowerCase())
      ).slice(0, 6);
      
      if (matches.length === 0) {
        container.style.display = 'none';
        return;
      }
      
      matches.forEach(p => {
        const div = document.createElement('div');
        div.textContent = `${p.nombre_prospecto} - ${p.empresa || ''}`;
        div.onclick = () => {
          inputProspecto.value = p.nombre_prospecto;
          document.getElementById('id_prospecto').value = p.id_prospecto;
          container.style.display = 'none';
        };
        container.appendChild(div);
      });
      container.style.display = 'block';
    });
  }
});

// === CARGAR VEHÍCULO COMPLETO ===
async function cargarVehiculo(idVehiculo) {
  try {
    const res = await fetch(`${API_URL}/vehiculos.php`);
    const vehiculos = await res.json();
    vehiculoData = vehiculos.find(v => v.id_vehiculo == idVehiculo);
    
    if (vehiculoData) {
      document.getElementById('header-vehiculo').textContent = vehiculoData.nombre_completo || '-';
      document.getElementById('header-placa').textContent = vehiculoData.placa || '-';
      document.getElementById('header-combustible').textContent = 
        `${vehiculoData.tipo_combustible || '-'} (${vehiculoData.tipo_gasolina || '-'})`;
      document.getElementById('header-consumo').textContent = 
        (vehiculoData.consumo_promedio || 0.165).toFixed(3) + ' L/Km';
      
      consumoPromedio = parseFloat(vehiculoData.consumo_promedio) || 0.165;
    }
  } catch (e) {
    console.error('Error cargando vehículo:', e);
  }
}

// === VER DETALLE ===
async function verDetalle(id) {
  try {
    const res = await fetch(`${API_URL}/solicitudes.php/${id}`);
    const data = await res.json();
    
    // Actualizar header con datos de la solicitud
    document.getElementById('header-solicitud').textContent = data.id_solicitud || '-';
    document.getElementById('header-fecha').textContent = 
      new Date(data.fecha_solicitud).toLocaleDateString('es-CR') || '-';
    document.getElementById('header-periodo').textContent = 
      `${data.periodo_inicio} a ${data.periodo_fin}`;
    document.getElementById('header-empleado').textContent = data.empleado || '-';
    
    // Valores de configuración
    valorLitro = parseFloat(data.valor_litro) || 677;
    factorGpK = parseFloat(data.factor_gpk) || 2.00;
    
    document.getElementById('header-valor-litro').textContent = 
      valorLitro.toLocaleString('es-CR', {minimumFractionDigits: 2}) + ' Col./L';
    document.getElementById('header-factor-gpk').textContent = 
      factorGpK.toFixed(2) + ' %';

    // Cargar información completa del vehículo
    await cargarVehiculo(data.id_vehiculo);

    // Renderizar tablas
    consumoPromedio = parseFloat(data.consumo_promedio) || 0.165;
    renderKilometraje(data.detalles_km || [], consumoPromedio);
    renderFacturas(data.facturas || []);
  } catch (e) {
    console.error('Error cargando detalle:', e);
    alert('Error al cargar la solicitud');
  }
}

// === RENDERIZAR TABLAS ===
function renderKilometraje(kmList, consumo) {
  const tbody = document.getElementById('tabla-km');
  tbody.innerHTML = '';
  let totalKm = 0, totalLitros = 0, totalCosto = 0, totalGpK = 0, totalFinal = 0;

  kmList.forEach((d, i) => {
    const kmTotal = d.km_final - d.km_inicial;
    const litros = (kmTotal * consumo).toFixed(2);
    const costo = (parseFloat(litros) * valorLitro).toFixed(2);
    const gpk = (parseFloat(costo) * factorGpK / 100).toFixed(2);
    const total = (parseFloat(costo) + parseFloat(gpk)).toFixed(2);

    totalKm += kmTotal;
    totalLitros += parseFloat(litros);
    totalCosto += parseFloat(costo);
    totalGpK += parseFloat(gpk);
    totalFinal += parseFloat(total);

    tbody.innerHTML += `
      <tr data-id="${d.id_detalle_km}">
        <td>${i+1}</td>
        <td><div class="form-check"><input class="form-check-input chk-km" type="checkbox"></div></td>
        <td>${d.fecha_visita}</td>
        <td>${d.nombre_visita}</td>
        <td class="text-end pe-3">${parseInt(d.km_inicial).toLocaleString()}</td>
        <td class="text-end pe-3">${parseInt(d.km_final).toLocaleString()}</td>
        <td class="text-end pe-3">${kmTotal.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
        <td class="text-end pe-3">${parseFloat(litros).toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
        <td class="text-end pe-3">${parseFloat(costo).toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
        <td class="text-end pe-3">${parseFloat(gpk).toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
        <td class="text-end pe-3">${parseFloat(total).toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
      </tr>`;
  });

  document.getElementById('totales-km').innerHTML = `
    <tr>
      <td colspan="6" class="text-end">TOTALES</td>
      <td class="text-end pe-3">${totalKm.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
      <td class="text-end pe-3">${totalLitros.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
      <td class="text-end pe-3">${totalCosto.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
      <td class="text-end pe-3">${totalGpK.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
      <td class="text-end pe-3">${totalFinal.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
    </tr>`;
}

function renderFacturas(facturas) {
  const tbody = document.getElementById('tabla-facturas');
  tbody.innerHTML = '';
  let totalLitros = 0, totalPagado = 0;

  facturas.forEach((f, i) => {
    totalLitros += parseFloat(f.litros);
    totalPagado += parseFloat(f.monto_pagado);

    tbody.innerHTML += `
      <tr data-id="${f.id_factura}">
        <td>${i+1}</td>
        <td><div class="form-check"><input class="form-check-input chk-factura" type="checkbox"></div></td>
        <td>${f.fecha_factura}</td>
        <td>${f.numero_factura}</td>
        <td>${f.nombre_proveedor}</td>
        <td class="text-end pe-3">${parseFloat(f.litros).toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
        <td class="text-end pe-3">${parseFloat(f.monto_pagado).toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
        <td class="text-end pe-3">${valorLitro.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
      </tr>`;
  });

  document.getElementById('totales-facturas').innerHTML = `
    <tr class="facturas-totales">
      <td colspan="5" class="text-end">TOTALES</td>
      <td class="text-end pe-3">${totalLitros.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
      <td class="text-end pe-3">${totalPagado.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
      <td></td>
    </tr>`;
}

// === AGREGAR REGISTROS ===
async function confirmarAgregarKM() {
  const nombreVisita = document.getElementById('buscar-prospecto').value.trim();
  const kmInicial = document.getElementById('modal-km-inicial').value;
  const kmFinal = document.getElementById('modal-km-final').value;
  
  if (!nombreVisita || !kmInicial || !kmFinal) {
    alert('Complete todos los campos requeridos');
    return;
  }

  const data = {
    id_solicitud: currentSolicitudId,
    id_prospecto: document.getElementById('id_prospecto').value || null,
    fecha_visita: document.getElementById('modal-fecha-visita').value,
    nombre_visita: nombreVisita,
    km_inicial: +kmInicial,
    km_final: +kmFinal
  };
  
  try {
    const res = await fetch(`${API_URL}/kilometraje.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    if (res.ok) {
      const modal = bootstrap.Modal.getInstance(document.getElementById('addVisitaModal'));
      modal.hide();
      await verDetalle(currentSolicitudId);
      // Limpiar campos
      document.getElementById('buscar-prospecto').value = '';
      document.getElementById('id_prospecto').value = '';
      document.getElementById('modal-km-inicial').value = '';
      document.getElementById('modal-km-final').value = '';
    } else {
      alert('Error al agregar visita');
    }
  } catch (e) {
    console.error('Error:', e);
    alert('Error al agregar visita');
  }
}

async function confirmarAgregarFactura() {
  const numeroFactura = document.getElementById('modal-numero-factura').value;
  const proveedor = document.getElementById('modal-proveedor').value.trim();
  const litros = document.getElementById('modal-litros').value;
  const monto = document.getElementById('modal-monto').value;
  
  if (!numeroFactura || !proveedor || !litros || !monto) {
    alert('Complete todos los campos requeridos');
    return;
  }

  const data = {
    id_solicitud: currentSolicitudId,
    numero_factura: numeroFactura,
    fecha_factura: document.getElementById('modal-fecha-factura').value,
    nombre_proveedor: proveedor,
    litros: +litros,
    monto_pagado: +monto
  };
  
  try {
    const res = await fetch(`${API_URL}/combustible.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    if (res.ok) {
      const modal = bootstrap.Modal.getInstance(document.getElementById('addCombustibleModal'));
      modal.hide();
      await verDetalle(currentSolicitudId);
      // Limpiar campos
      document.getElementById('modal-numero-factura').value = '';
      document.getElementById('modal-proveedor').value = '';
      document.getElementById('modal-litros').value = '';
      document.getElementById('modal-monto').value = '';
      document.getElementById('modal-fecha-factura').value = new Date().toISOString().split('T')[0];
    } else {
      alert('Error al agregar factura');
    }
  } catch (e) {
    console.error('Error:', e);
    alert('Error al agregar factura');
  }
}

// === ELIMINAR ===
function eliminarSeleccionados(tipo) {
  const checks = document.querySelectorAll(`.chk-${tipo}:checked`);
  if (checks.length === 0) return alert('Seleccione al menos un registro');
  if (!confirm(`¿Eliminar ${checks.length} registros?`)) return;
  
  const ids = Array.from(checks).map(c => c.closest('tr').dataset.id);
  const endpoint = tipo === 'km' ? 'kilometraje.php' : 'combustible.php';
  
  Promise.all(ids.map(id => 
    fetch(`${API_URL}/${endpoint}/${id}`, { method: 'DELETE' })
  )).then(async () => {
    await verDetalle(currentSolicitudId);
  }).catch(e => {
    console.error('Error eliminando:', e);
    alert('Error al eliminar registros');
  });
}
</script>
</body>
</html>