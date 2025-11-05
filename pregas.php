<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($protocol . '://' . $host . $path, '/') . '/';
$api_url = $base_url . 'api';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>REINTEGROS - LISTADO</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<style>
  body{background:#fff;font-family:"Segoe UI",sans-serif;font-size:0.95rem;}
  .container-fixed{width:1200px;margin:0 auto;}
  .header-title{font-size:1.35rem;font-weight:700;text-transform:uppercase;margin:1rem 0;}
  .icon-toolbar i{font-size:1.2rem;color:#6c757d;margin-left:.75rem;cursor:pointer;transition:color .15s ease;}
  .icon-toolbar .bi-plus-circle:hover{color:green;}
  .icon-toolbar .bi-plus-circle:hover::before{content:"\f4f9";}
  .table-custom{width:100%;border:1px solid #bdbdbd;border-collapse:collapse;}
  .table-custom th,.table-custom td{border:1px solid #bdbdbd!important;padding:.35rem .45rem;text-align:center;vertical-align:middle;}
  .table-custom thead th{background:#d6d6d6!important;color:#212529;font-weight:600;text-transform:uppercase;}
  .table-custom tbody tr:nth-child(odd) td{background:#fff!important;}
  .table-custom tbody tr:nth-child(even) td{background:#f3f3f3!important;}
  .table-custom tbody tr:hover td{background:#eef3f8!important;cursor:pointer;}
  .table-custom tfoot td{background:#f3f3f3!important;font-weight:600;}
  .status-icon{display:inline-block;width:22px;height:22px;line-height:22px;border-radius:50%;color:#fff;font-weight:700;font-size:.8rem;text-align:center;}
  .status-a{background:#28a745;}
  .status-r{background:#dc3545;}
  .status-p{background:#ffc107;color:#000;}
  th.sortable{cursor:pointer;position:relative;padding-right:18px;}
  th.sortable .sort-icon{position:absolute;right:5px;top:50%;transform:translateY(-50%);font-size:.7rem;color:#6c757d;}
  th.sortable.asc .sort-icon{color:#000;transform:translateY(-50%) rotate(180deg);}
  th.sortable.desc .sort-icon{color:#000;}
  .modal-header{background-color:#e4e4e4;border-bottom:1px solid #ccc;}
</style>
</head>
<body>
<div class="container-fixed">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="header-title mb-0">REINTEGRO COMBUSTIBLES</h4>
    <div class="icon-toolbar">
      <i class="bi bi-plus-circle" id="nuevaSolicitudBtn" title="Nueva solicitud"></i>
    </div>
  </div>

  <div class="filters-bar d-flex align-items-center mb-3">
    <label class="me-2 small">Mostrar</label>
    <select id="recordsPerPage" class="form-select form-select-sm me-3" style="width:90px;">
      <option value="10" selected>10</option>
      <option value="20">20</option>
      <option value="all">Todos</option>
    </select>
    <label class="me-2 small">Desde</label>
    <input type="date" id="startDate" class="form-control form-control-sm me-3" style="width:130px;">
    <label class="me-2 small">Hasta</label>
    <input type="date" id="endDate" class="form-control form-control-sm me-3" style="width:130px;">
    <button id="searchBtn" class="btn btn-primary btn-sm me-2">Buscar</button>
    <button id="excelBtn" class="btn btn-success btn-sm">Excel</button>
  </div>

  <table id="reportsTable" class="table-custom">
    <thead>
      <tr>
        <th>#</th>
        <th></th>
        <th class="sortable" data-key="fecha_solicitud">Creado <i class="bi bi-caret-down-fill sort-icon"></i></th>
        <th class="sortable" data-key="fecha_aprobacion">Procesado <i class="bi bi-caret-down-fill sort-icon"></i></th>
        <th class="sortable" data-key="id_solicitud">Consecutivo <i class="bi bi-caret-down-fill sort-icon"></i></th>
        <th class="sortable" data-key="total_solicitud">T. Solicitado <i class="bi bi-caret-down-fill sort-icon"></i></th>
        <th>T. Aprobado</th>
        <th class="sortable" data-key="estado">Status <i class="bi bi-caret-down-fill sort-icon"></i></th>
      </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
      <tr>
        <td colspan="5" class="text-end">TOTALES</td>
        <td class="text-end pe-3" id="totalSolicitado">0.00</td>
        <td class="text-end pe-3" id="totalAprobado">0.00</td>
        <td></td>
      </tr>
    </tfoot>
  </table>

  <div class="d-flex flex-column align-items-center mt-2">
    <div id="recordCount" class="small text-secondary mb-1"></div>
    <ul id="pagination" class="pagination pagination-sm mb-0"></ul>
  </div>
</div>

<!-- MODAL NUEVA SOLICITUD -->
<div class="modal fade" id="nuevaSolicitudModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-semibold">Nueva Solicitud de Reintegro</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Empleado ID</label>
          <input type="number" class="form-control form-control-sm" id="modal-id-empleado" value="1">
        </div>
        <div class="mb-3">
          <label class="form-label">Vehículo</label>
          <select class="form-select form-select-sm" id="modal-id-vehiculo">
            <option value="">Seleccione...</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Fecha Solicitud</label>
          <input type="date" class="form-control form-control-sm" id="modal-fecha-solicitud">
        </div>
        <div class="row">
          <div class="col-6">
            <label class="form-label">Período Inicio</label>
            <input type="date" class="form-control form-control-sm" id="modal-periodo-inicio">
          </div>
          <div class="col-6">
            <label class="form-label">Período Fin</label>
            <input type="date" class="form-control form-control-sm" id="modal-periodo-fin">
          </div>
        </div>
        <div id="modal-msg" class="mt-2"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success btn-sm" id="crearSolicitudBtn">Crear y Abrir</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const API_URL = <?= json_encode($api_url) ?>;
  let solicitudes = [];
  let filtered = [];
  let currentPage = 1;
  let perPage = 10;
  let modalNuevaSolicitud;

  // === INICIALIZACIÓN ===
  window.addEventListener('load', () => {
    modalNuevaSolicitud = new bootstrap.Modal('#nuevaSolicitudModal');
    cargarSolicitudes();
    cargarVehiculos();
    setDefaultDates();
  });

  // === CARGAR VEHÍCULOS ===
  async function cargarVehiculos() {
    try {
      const res = await fetch(`${API_URL}/vehiculos.php`);
      const data = await res.json();
      const select = document.getElementById('modal-id-vehiculo');
      select.innerHTML = '<option value="">Seleccione...</option>';
      data.forEach(v => {
        select.innerHTML += `<option value="${v.id_vehiculo}">${v.nombre_completo} - ${v.placa}</option>`;
      });
    } catch (e) {
      console.error('Error cargando vehículos');
    }
  }

  // === CARGAR SOLICITUDES ===
  async function cargarSolicitudes() {
    try {
      const res = await fetch(`${API_URL}/solicitudes.php`);
      if (!res.ok) throw new Error('Error en la respuesta del servidor');
      
      const data = await res.json();
      solicitudes = data.map(s => ({
        id: s.id_solicitud,
        fecha_solicitud: s.fecha_solicitud,
        fecha_aprobacion: s.fecha_aprobacion || '-',
        id_solicitud: s.id_solicitud,
        total_solicitud: parseFloat(s.total_solicitud || 0),
        total_aprobado: s.estado === 'aprobada' || s.estado === 'pagada' ? parseFloat(s.total_solicitud || 0) : 0,
        estado: s.estado === 'aprobada' || s.estado === 'pagada' ? 'A' : s.estado === 'rechazada' ? 'R' : 'P'
      }));
      filtered = [...solicitudes];
      render();
    } catch (e) {
      console.error('Error cargando solicitudes:', e);
    }
  }

  // === FECHAS POR DEFECTO ===
  function setDefaultDates() {
    const hoy = new Date().toISOString().split('T')[0];
    const mes = new Date();
    mes.setMonth(mes.getMonth() - 1);
    document.getElementById('startDate').value = mes.toISOString().split('T')[0];
    document.getElementById('endDate').value = hoy;
    
    // Modal
    ['modal-fecha-solicitud', 'modal-periodo-inicio', 'modal-periodo-fin'].forEach(id => {
      document.getElementById(id).value = hoy;
    });
  }

  // === RENDER TABLA ===
  function render() {
    const tbody = document.querySelector('#reportsTable tbody');
    tbody.innerHTML = '';
    const start = (currentPage - 1) * (perPage === 'all' ? filtered.length : perPage);
    const end = perPage === 'all' ? filtered.length : start + perPage;
    const visible = filtered.slice(start, end);

    let totS = 0, totA = 0;
    visible.forEach((r, i) => {
      totS += r.total_solicitud;
      totA += r.total_aprobado;

      const icon = r.estado === 'A' ? '<span class="status-icon status-a">A</span>' :
                   r.estado === 'R' ? '<span class="status-icon status-r">R</span>' :
                                      '<span class="status-icon status-p">P</span>';

      const row = tbody.insertRow();
      row.dataset.id = r.id;
      row.onclick = (e) => {
        if (e.target.type !== 'checkbox') abrirSolicitud(r.id);
      };
      
      row.innerHTML = `
        <td>${start + i + 1}</td>
        <td><div class="form-check"><input class="form-check-input" type="checkbox" onclick="event.stopPropagation()"></div></td>
        <td>${r.fecha_solicitud}</td>
        <td>${r.fecha_aprobacion}</td>
        <td>${r.id_solicitud}</td>
        <td class="text-end pe-3">${r.total_solicitud.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
        <td class="text-end pe-3">${r.estado === 'P' ? '-' : r.total_aprobado.toLocaleString('es-CR', {minimumFractionDigits: 2})}</td>
        <td>${icon}</td>`;
    });

    document.getElementById('totalSolicitado').textContent = totS.toLocaleString('es-CR', {minimumFractionDigits: 2});
    document.getElementById('totalAprobado').textContent = totA.toLocaleString('es-CR', {minimumFractionDigits: 2});
    renderPagination();
  }

  // === PAGINACIÓN ===
  function renderPagination() {
    const pag = document.getElementById('pagination');
    const count = document.getElementById('recordCount');
    pag.innerHTML = '';
    const total = filtered.length;
    const pages = perPage === 'all' ? 1 : Math.ceil(total / perPage);
    const showing = perPage === 'all' ? total : Math.min(currentPage * perPage, total);
    count.textContent = `Mostrando ${showing} de ${total}`;

    if (pages <= 1) return;
    for (let i = 1; i <= pages; i++) {
      pag.innerHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#">${i}</a></li>`;
    }
    pag.querySelectorAll('a').forEach((a, idx) => {
      a.onclick = e => { e.preventDefault(); currentPage = idx + 1; render(); };
    });
  }

  // === FILTROS ===
  document.getElementById('searchBtn').onclick = () => {
    const s = document.getElementById('startDate').value;
    const e = document.getElementById('endDate').value;
    filtered = solicitudes.filter(r => {
      const d = r.fecha_solicitud;
      return (!s || d >= s) && (!e || d <= e);
    });
    currentPage = 1;
    render();
  };

  document.getElementById('recordsPerPage').onchange = e => {
    perPage = e.target.value === 'all' ? 'all' : +e.target.value;
    currentPage = 1;
    render();
  };

  // === ORDENAR ===
  document.querySelectorAll('th.sortable').forEach(th => {
    th.onclick = () => {
      const key = th.dataset.key;
      const asc = !th.classList.contains('asc');
      document.querySelectorAll('th.sortable').forEach(t => t.className = 'sortable');
      th.className = 'sortable ' + (asc ? 'asc' : 'desc');

      filtered.sort((a, b) => {
        let A = a[key], B = b[key];
        if (key === 'total_solicitud') return (A - B) * (asc ? 1 : -1);
        if (key === 'fecha_solicitud' || key === 'fecha_aprobacion') {
          A = A === '-' ? '' : new Date(A);
          B = B === '-' ? '' : new Date(B);
        }
        return (A > B ? 1 : -1) * (asc ? 1 : -1);
      });
      render();
    };
  });

  // === EXCEL ===
  document.getElementById('excelBtn').onclick = () => {
    const data = filtered.map(r => ({
      Creado: r.fecha_solicitud,
      Procesado: r.fecha_aprobacion,
      Consecutivo: r.id_solicitud,
      'T. Solicitado': r.total_solicitud.toFixed(2),
      'T. Aprobado': r.estado === 'P' ? '0.00' : r.total_aprobado.toFixed(2),
      Status: r.estado
    }));
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Reintegros");
    XLSX.writeFile(wb, "reintegros_" + new Date().toISOString().slice(0,10) + ".xlsx");
  };

  // === ABRIR MODAL NUEVA SOLICITUD ===
  document.getElementById('nuevaSolicitudBtn').onclick = () => {
    document.getElementById('modal-msg').innerHTML = '';
    modalNuevaSolicitud.show();
  };

  // === CREAR SOLICITUD ===
  document.getElementById('crearSolicitudBtn').onclick = async () => {
    const data = {
      id_empleado: +document.getElementById('modal-id-empleado').value,
      id_vehiculo: +document.getElementById('modal-id-vehiculo').value,
      fecha_solicitud: document.getElementById('modal-fecha-solicitud').value,
      periodo_inicio: document.getElementById('modal-periodo-inicio').value,
      periodo_fin: document.getElementById('modal-periodo-fin').value
    };

    if (!data.id_vehiculo) {
      document.getElementById('modal-msg').innerHTML = '<div class="alert alert-danger py-2 mb-0">Seleccione un vehículo</div>';
      return;
    }

    try {
      const res = await fetch(`${API_URL}/solicitudes.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      
      const result = await res.json();
      
      if (res.ok && result.id_solicitud) {
        modalNuevaSolicitud.hide();
        window.location.href = `index.php?id=${result.id_solicitud}`;
      } else {
        document.getElementById('modal-msg').innerHTML = `<div class="alert alert-danger py-2 mb-0">${result.error || 'Error al crear'}</div>`;
      }
    } catch (e) {
      document.getElementById('modal-msg').innerHTML = '<div class="alert alert-danger py-2 mb-0">Error de conexión</div>';
    }
  };

  // === ABRIR SOLICITUD EXISTENTE ===
  function abrirSolicitud(id) {
    window.location.href = `index.php?id=${id}`;
  }
</script>
</body>
</html>