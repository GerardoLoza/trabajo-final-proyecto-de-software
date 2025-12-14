<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Médico - HealthTracker</title>
    <link rel="stylesheet" href="<?= base_url('styles.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Ajustes específicos para secciones */
        .entity-section { margin-bottom: 40px; scroll-margin-top: 20px; }
        
        /* Estilos para los íconos de las tarjetas de estadísticas */
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
        }

        /* Asegurar alineación en tablas */
        table td { vertical-align: middle; }

        /* Contenedores para gráficos: altura fija para evitar "derretimiento" al redimensionar */
        .chart-container { height: 250px; position: relative; }
        .chart-container canvas { width: 100% !important; height: 100% !important; display: block; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="<?= base_url('views/images/healthtrackerv1.png') ?>" alt="Logo" class="logo-img">
        </div>
        <h1>HealthTracker</h1>
        <nav>
            <button class="nav-btn" onclick="scrollToSection('resumen')">
                <i class="fas fa-chart-line" style="width:20px;"></i> Resumen
            </button>
            <button class="nav-btn" onclick="scrollToSection('planes')">
                <i class="fas fa-clipboard-list" style="width:20px;"></i> Gestión de Planes
            </button>
            <button class="nav-btn" onclick="scrollToSection('pacientes')">
                <i class="fas fa-user-injured" style="width:20px;"></i> Mis Pacientes
            </button>
            
            <div style="padding: 15px 20px 5px; color: #aaa; font-size: 0.75em; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Configuración</div>
            
            <button class="nav-btn" onclick="scrollToSection('medicamentos')">
                <i class="fas fa-pills" style="width:20px;"></i> Medicamentos
            </button>
            <button class="nav-btn" onclick="scrollToSection('diagnosticos')">
                <i class="fas fa-stethoscope" style="width:20px;"></i> Diagnósticos
            </button>
            <button class="nav-btn" onclick="scrollToSection('tipos-tarea')">
                <i class="fas fa-tasks" style="width:20px;"></i> Tipos de Tareas
            </button>
            
            <button onclick="window.location.href='<?= base_url('logout') ?>'" class="nav-btn" style="margin-top: auto; background-color: #dc2626;">
                <i class="fas fa-sign-out-alt" style="width:20px;"></i> Cerrar Sesión
            </button>
        </nav>
    </aside>

    <main class="main-content">
        
        <div id="resumen" class="entity-section">
            <div class="content-card">
                <div class="header-section">
                    <h2>Resumen General</h2>
                    <button class="btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>

                <div class="stats-grid">
                    <!-- Selector de paciente movido debajo de KPIs generales (se mostrará junto a los gráficos) -->

                        <!-- KPIs Filtrados por paciente (si existe selección) -->
                        <?php if (!empty($kpis_filtrado)): $kf = $kpis_filtrado; ?>
                            <div class="full-width" style="width:100%; margin-top:12px;" id="kpis-filtrado">
                                <h4 style="margin:0 0 8px 0;">KPIs para el paciente seleccionado</h4>
                                <div class="kpi-cards">
                                    <div class="stat-card">
                                        <div class="stat-icon" style="background:#eef2ff; color:#4338ca;"><i class="fas fa-user"></i></div>
                                        <div class="stat-info">
                                            <div class="stat-value" id="kf-porcentaje"><?= esc($kf['porcentaje_completado'] ?? 0) ?>%</div>
                                            <div class="stat-label">Cumplimiento</div>
                                        </div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-icon" style="background:#ecfeff; color:#0891b2;"><i class="fas fa-check"></i></div>
                                        <div class="stat-info">
                                            <div class="stat-value" id="kf-completadas"><?= esc($kf['tareas_completadas'] ?? 0) ?></div>
                                            <div class="stat-label">Completadas</div>
                                        </div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-icon" style="background:#fff7ed; color:#f97316;"><i class="fas fa-hourglass"></i></div>
                                        <div class="stat-info">
                                            <div class="stat-value" id="kf-pendientes"><?= esc($kf['tareas_pendientes'] ?? 0) ?></div>
                                            <div class="stat-label">Pendientes</div>
                                        </div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-icon" style="background:#fef3c7; color:#b45309;"><i class="fas fa-calendar-week"></i></div>
                                        <div class="stat-info">
                                            <div class="stat-value" id="kf-tps"><?= esc($kf['tareas_por_semana'] ?? 0) ?></div>
                                            <div class="stat-label">Tareas / semana</div>
                                        </div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-icon" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-fire"></i></div>
                                        <div class="stat-info">
                                            <div class="stat-value" id="kf-racha"><?= esc($kf['racha_dias'] ?? 0) ?></div>
                                            <div class="stat-label">Racha (días)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (isset($selected_paciente) && !$kpis_filtrado): ?>
                            <div style="width:100%; margin-top:12px; color:#666;">El paciente seleccionado no tiene planes/tareas para calcular métricas.</div>
                        <?php endif; ?>

                        <!-- KPIs Generales (se muestran debajo del filtro por paciente) -->
                        <?php $kg = $kpis_general ?? []; ?>
             <div class="full-width" style="width:100%; margin-top:12px;" id="kpis-generales">
                 <h4 style="margin:0 0 8px 0;">KPIs Generales (todos los pacientes)</h4>
                 <div class="kpi-cards">
                     <div class="stat-card">
                         <div class="stat-icon" style="background:#ecfeff; color:#0891b2;"><i class="fas fa-percent"></i></div>
                        <div class="stat-info"><div class="stat-value" id="kg-porcentaje"><?= esc($kg['porcentaje_completado'] ?? 0) ?>%</div><div class="stat-label">Cumplimiento Global</div></div>
                     </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#f0fdf4; color:#10b981;"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info"><div class="stat-value" id="kg-completadas"><?= esc($kg['tareas_completadas'] ?? 0) ?></div><div class="stat-label">Tareas Completadas</div></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fff7ed; color:#f97316;"><i class="fas fa-hourglass-half"></i></div>
                        <div class="stat-info"><div class="stat-value" id="kg-pendientes"><?= esc($kg['tareas_pendientes'] ?? 0) ?></div><div class="stat-label">Tareas Pendientes</div></div>
                    </div>
                    <!-- tarjeta 'Tareas / semana' eliminada por solicitud -->
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-fire"></i></div>
                        <div class="stat-info"><div class="stat-value" id="kg-racha"><?= esc($kg['racha_dias'] ?? 0) ?></div><div class="stat-label">Racha (días seg.)</div></div>
                    </div>
                 </div>
             </div>
                </div>
                
                <div style="margin-top:20px; display:flex; flex-direction:column; gap:18px;">
                    <!-- Filtro ahora en su propia fila encima de los gráficos -->
                    <div style="width:100%;">
                        <div style="flex:0 1 260px; max-width:720px; background:#fff; padding:14px; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
                            <label style="font-weight:600; display:block; margin-bottom:8px; color:#334155;">Filtrar estadísticas por paciente</label>
                            <select id="filter-paciente" style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #ccc;">
                                <option value="">Todas las estadísticas (Global)</option>
                                <?php if (!empty($listaPacientes)): foreach($listaPacientes as $pac): ?>
                                    <?php $pid = is_object($pac) ? $pac->id_usuario : $pac['id_usuario']; ?>
                                    <option value="<?= esc($pid) ?>" <?= (isset($selected_paciente) && $selected_paciente == $pid) ? 'selected' : '' ?>>
                                        <?= esc($pac->nombre . ' ' . $pac->apellido . ' (' . $pac->email . ')') ?>
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Contenedor de fila para alinear los gráficos horizontalmente -->
                    <div style="display:flex; gap:18px; flex-wrap:wrap; align-items:flex-start;">
                        <div style="flex:1 1 520px; min-width:320px; background:#fff; padding:14px; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
                            <h3 style="margin:0 0 10px; font-size:1rem; color:#334155;">Tareas completadas (últimos 28 días)</h3>
                            <div class="chart-container"><canvas id="chart-daily"></canvas></div>
                        </div>

                        <div style="flex:0 1 320px; min-width:260px; background:#fff; padding:14px; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
                            <h3 style="margin:0 0 10px; font-size:1rem; color:#334155;">Distribución por tipo</h3>
                            <div class="chart-container"><canvas id="chart-type"></canvas></div>
                        </div>
                    </div>
                    <!-- Gráfico semanal eliminado según solicitud -->
                </div>
            </div>
        </div>

        <div id="planes" class="entity-section">
            <div class="content-card">
                <div class="header-section">
                    <h2>Gestión de Planes</h2>
                    <button class="btn-primary" onclick="openModal('planes', 'create')">
                        <i class="fas fa-plus"></i> Nuevo Plan
                    </button>
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Buscar planes..." onkeyup="filterTable('planes')">
                </div>
                <table id="planes-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Plan</th>
                            <th>Paciente</th>
                            <th>Diagnóstico</th>
                            <th>Vigencia</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($listaPlanes) && is_array($listaPlanes)): ?>
                            <?php foreach ($listaPlanes as $plan): ?>
                                <tr data-id="<?= esc($plan->id) ?>"
                                    data-nombre="<?= esc($plan->nombre) ?>"
                                    data-descripcion="<?= esc($plan->descripcion) ?>"
                                    data-id_paciente="<?= esc($plan->id_paciente) ?>"
                                    data-nombre_diagnostico="<?= esc($plan->nombre_diagnostico) ?>"
                                    data-fecha_inicio="<?= esc($plan->fecha_inicio) ?>"
                                    data-fecha_fin="<?= esc($plan->fecha_fin) ?>">
                                    
                                    <td>#<?= esc($plan->id) ?></td>
                                    <td>
                                        <strong><?= esc($plan->nombre) ?></strong><br>
                                        <small style="color:#64748b"><?= esc($plan->descripcion) ?></small>
                                    </td>
                                    <td>ID: <?= esc($plan->id_paciente) ?></td>
                                    <td><?= esc($plan->nombre_diagnostico) ?></td>
                                    <td>
                                        <small>In: <?= esc($plan->fecha_inicio) ?></small><br>
                                        <small>Fin: <?= esc($plan->fecha_fin) ?></small>
                                        <?php 
                                            $colorBg = ($plan->estado === 'Vigente') ? '#d1fae5' : '#e5e7eb';
                                            $colorTxt = ($plan->estado === 'Vigente') ? '#065f46' : '#374151';
                                        ?>
                                        <br>
                                        <span id="badge-estado-<?= $plan->id ?>" style="background:<?= $colorBg ?>; color:<?= $colorTxt ?>; padding:2px 8px; border-radius:12px; font-size:0.75em; font-weight:700; text-transform:uppercase;">
                                            <?= esc($plan->estado) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn-secondary btn-icon" onclick="openTasksModal(<?= esc($plan->id) ?>)" title="Gestionar Tareas">
                                                <i class="fas fa-list-check"></i>
                                            </button>
                                            <button class="btn-edit btn-icon" onclick="openModal('planes', 'edit', this.closest('tr'))" title="Editar Plan">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="btn-delete btn-icon" onclick="deleteRecord('planes', <?= esc($plan->id) ?>)" title="Eliminar Plan">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <button class="btn-view btn-icon" onclick="openProgressModal(<?= esc($plan->id) ?>)" title="Ver Progreso">
                                                <i class="fas fa-chart-pie"></i>
                                            </button>
                                            <button class="btn-edit btn-icon" onclick="togglePlanStatus(<?= esc($plan->id) ?>)" title="Cambiar Estado" style="background-color: #4b5563; border-color: #4b5563;">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="empty-state">No hay planes registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="pacientes" class="entity-section">
             <div class="content-card">
                <div class="header-section">
                    <h2>Mis Pacientes</h2>
                </div>
                <table id="pacientes-table">
                    <thead>
                        <tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($listaPacientes)): ?>
                            <?php foreach ($listaPacientes as $paciente): ?>
                                <tr>
                                    <td><?= esc($paciente->nombre . ' ' . $paciente->apellido) ?></td>
                                    <td><?= esc($paciente->email) ?></td>
                                    <td><span style="background:#f1f5f9; padding:4px 8px; border-radius:12px; font-size:0.85em; font-weight:600; color:#475569;"><?= esc($paciente->nombre_rol) ?></span></td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn-edit btn-icon" onclick="alert('Funcionalidad de perfil pendiente')" title="Ver Perfil">
                                                <i class="fas fa-user"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr><td colspan="4" class="empty-state">No tienes pacientes asignados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="medicamentos" class="entity-section">
            <div class="content-card">
                <div class="header-section">
                    <h2>Medicamentos</h2>
                    <button class="btn-primary" onclick="openDynamicModal('medicamentos', 'create')">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>
                <table id="medicamentos-table">
                    <thead><tr><th>Nombre</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($listaMedicamentos as $m): ?>
                        <tr data-id="<?= esc($m->nombre) ?>" data-nombre="<?= esc($m->nombre) ?>">
                            <td><?= esc($m->nombre) ?></td>
                            <td>
                                <div class="actions">
                                    <button class="btn-delete btn-icon" onclick="deleteRecord('medicamentos', '<?= esc($m->nombre) ?>')" title="Eliminar"> 
                                        <i class="fas fa-trash"></i>    
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="diagnosticos" class="entity-section">
            <div class="content-card">
            <div class="header-section">
                <h2>Diagnósticos</h2>
                <button class="btn-primary" onclick="openDynamicModal('diagnosticos', 'create')">
                    <i class="fas fa-plus"></i> Nuevo
                </button>
            </div>
            <table id="diagnosticos-table">
                <thead><tr><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($listaDiagnosticos as $d): ?>
                        <tr data-id="<?= esc($d->nombre) ?>" 
                            data-nombre="<?= esc($d->nombre) ?>" 
                            data-descripcion="<?= esc($d->descripcion) ?>">
                            <td><strong><?= esc($d->nombre) ?></strong></td>
                            <td><?= esc($d->descripcion) ?></td>
                            <td>
                                <div class="actions">
                                    <button class="btn-edit btn-icon" onclick="openDynamicModal('diagnosticos', 'edit', this.closest('tr'))" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn-delete btn-icon" onclick="deleteRecord('diagnosticos', '<?= esc($d->nombre) ?>')" title="Eliminar"> 
                                        <i class="fas fa-trash"></i>    
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tipos-tarea" class="entity-section">
            <div class="content-card">
                <div class="header-section">
                    <h2>Tipos de Tarea</h2>
                    <button class="btn-primary" onclick="openDynamicModal('tipos-tarea', 'create')">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>
                <table id="tipos-tarea-table">
                    <thead><tr><th>Nombre</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($listaTiposTarea as $t): ?>
                        <tr data-id="<?= esc($t->id_tipo_tarea) ?>" data-nombre="<?= esc($t->nombre) ?>">
                            <td><?= esc($t->nombre) ?></td>
                            <td>
                                <div class="actions">
                                    <button class="btn-edit btn-icon" onclick="openDynamicModal('tipos-tarea', 'edit', this.closest('tr'))" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn-delete btn-icon" onclick="deleteRecord('tipos-tarea', <?= esc($t->id_tipo_tarea) ?>)" title="Eliminar"> 
                                        <i class="fas fa-trash"></i>    
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="progress-modal" class="modal">
            <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Progreso del Plan</h3>
                <button class="close-btn" onclick="closeModal('progress-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 25px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-weight:600; color:#444;">
                        <span>Porcentaje completado</span>
                        <span id="progress-percent-text">0%</span>
                    </div>
                    <div style="background-color: #e5e7eb; border-radius: 10px; height: 24px; width: 100%; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                        <div id="progress-bar-fill" style="background-color: #10b981; height: 100%; width: 0%; text-align: center; line-height: 24px; color: white; font-size: 0.85em; font-weight: bold; transition: width 0.6s ease-in-out;">
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid #eee; padding-top: 15px;">
                    <h4 style="margin-bottom: 15px; color: #333;">Detalle de Tareas</h4>
                    <div id="progress-tasks-list" style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
                        <div style="text-align:center; color:#888; padding:20px;">Cargando datos...</div>
                    </div>
                </div> 
            </div>
            <div class="modal-footer" style="text-align: right; margin-top: 20px;">
                <button class="btn-cancel" onclick="closeModal('progress-modal')">Cerrar</button>
            </div>
        </div>
    </div>
    
    <div id="dynamic-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="dynamic-modal-title">Gestión</h3>
                <button class="close-btn" onclick="closeModal('dynamic-modal')">&times;</button>
            </div>
            <form id="dynamic-form" method="POST">
                <div id="dynamic-fields"></div>
            
                <input type="hidden" name="id" id="dynamic-form-id">
                <input type="hidden" name="_method" id="dynamic-form-method" value="POST">

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal('dynamic-modal')">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</main>

    <?= view('planes/modal_form', [
        'todosLosPacientes' => $todosLosPacientes ?? [],
        'listaDiagnosticos' => $listaDiagnosticos ?? [],
        'listaTiposTarea'   => $listaTiposTarea ?? [],
        'listaMedicamentos' => $listaMedicamentos ?? []
    ]) ?>

    <?= view('planes/tasks_modal') ?>

    <script>
        window.serverData = {
            pacientes: <?= json_encode($todosLosPacientes ?? []) ?>,
            diagnosticos: <?= json_encode($listaDiagnosticos ?? []) ?>,
            tipos: <?= json_encode($listaTiposTarea ?? []) ?>,
            role: <?= json_encode(session()->get('nombre_rol') ?? '') ?>,
            kpis: <?= json_encode($kpis_general ?? []) ?>,
            charts: <?= json_encode($charts ?? []) ?>
        };

        // Exponer variables locales que usa el IIFE de inicialización de charts
        const charts = window.serverData.charts || {};
        const kpis = window.serverData.kpis || {};
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    (function initCharts(){
        // Asegurarse de tener el namespace global para actualizaciones
        window.profCharts = window.profCharts || {};

        // Daily chart
        const dayEl = document.getElementById('chart-daily');
        if (dayEl && charts.daily) {
            const ctx = dayEl.getContext('2d');
            window.profCharts.daily = new Chart(ctx, {
                type: 'line',
                data: { labels: charts.daily.labels, datasets: [{ label: 'Completadas', data: charts.daily.data, borderColor: '#2563eb', backgroundColor: 'rgba(96,165,250,0.2)', fill: true }] },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
            });
        }

        // By Type chart -> usa el ID del canvas presente en la vista: chart-type
        const typeEl = document.getElementById('chart-type');
        if (typeEl && charts.byType) {
            const ctx = typeEl.getContext('2d');
            window.profCharts.byType = new Chart(ctx, {
                type: 'doughnut',
                data: { labels: charts.byType.labels, datasets: [{ data: charts.byType.data, backgroundColor: ['#60A5FA','#34D399','#FBBF24','#F472B6','#A78BFA'] }] },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

            // Weekly chart removed intentionally
    })();
    </script>
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <script src="<?= base_url('script.js') ?>"></script>

    <script>
        function scrollToSection(id) {
            const element = document.getElementById(id);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.remove('active');
        }
    </script>
    <script>
    // Configuración de campos para el modal dinámico
    const formConfigs = {
        'medicamentos': [ { name: 'nombre', label: 'Nombre del Medicamento', type: 'text', required: true } ],
        'tipos-tarea': [ { name: 'nombre', label: 'Nombre del Tipo', type: 'text', required: true } ],
        'diagnosticos': [
            { name: 'nombre', label: 'Nombre Diagnóstico', type: 'text', required: true },
            { name: 'descripcion', label: 'Descripción', type: 'textarea', required: false }
        ]
    };

    function openDynamicModal(entity, mode, trElement = null) {
        const modal = document.getElementById('dynamic-modal');
        const form = document.getElementById('dynamic-form');
        const container = document.getElementById('dynamic-fields');
        const title = document.getElementById('dynamic-modal-title');
        
        container.innerHTML = '';
        form.reset();
        document.getElementById('dynamic-form-method').value = 'POST';

        // Generar campos
        const config = formConfigs[entity];
        if(config) {
            config.forEach(field => {
                const div = document.createElement('div');
                div.className = 'form-group';
                div.innerHTML = `<label style="font-weight:600; color:#333;">${field.label}</label>`;
                
                let input;
                if (field.type === 'textarea') {
                    input = document.createElement('textarea');
                    input.rows = 3;
                } else {
                    input = document.createElement('input');
                    input.type = field.type;
                }
                input.name = field.name;
                if(field.required) input.required = true;
                
                // Estilo base para inputs dinámicos
                input.style.width = '100%'; input.style.padding = '10px';
                input.style.border = '1px solid #cbd5e1'; input.style.borderRadius = '6px';
                
                if(mode === 'edit' && trElement) {
                    input.value = trElement.dataset[field.name] || '';
                }
                
                div.appendChild(input);
                container.appendChild(div);
            });
        }

        const baseMeta = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '') || '';
        const roleSegment = (window.serverData && window.serverData.role) ? String(window.serverData.role).toLowerCase() : (window.location.pathname.split('/')[1] || '');
        const baseUrl = baseMeta ? `${baseMeta}/${roleSegment}` : window.location.pathname.split('/').slice(0, 2).join('/');
        let actionUrl = `${baseUrl}/${entity}`;

        if (mode === 'create') {
            title.textContent = 'Nuevo Registro';
        } else {
            title.textContent = 'Editar Registro';
            const id = trElement.dataset.id;
            actionUrl += `/${encodeURIComponent(id)}`;
            document.getElementById('dynamic-form-method').value = 'PUT';
        }

        form.action = actionUrl;
        modal.classList.add('active');
    }
</script>
    <script>
        // helpers: crear o actualizar charts dinámicamente
        function createChartsFromData(charts) {
            window.profCharts = window.profCharts || {};

            const dayEl = document.getElementById('chart-daily');
            if (dayEl && charts.daily) {
                const ctx = dayEl.getContext('2d');
                window.profCharts.daily = new Chart(ctx, {
                    type: 'line',
                    data: { labels: charts.daily.labels, datasets: [{ label: 'Completadas', data: charts.daily.data, borderColor: '#2563eb', backgroundColor: 'rgba(96,165,250,0.2)', fill: true }] },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
                });
            }

            const typeEl = document.getElementById('chart-type');
            if (typeEl && charts.byType) {
                const ctx2 = typeEl.getContext('2d');
                window.profCharts.byType = new Chart(ctx2, {
                    type: 'doughnut',
                    data: { labels: charts.byType.labels, datasets: [{ data: charts.byType.data, backgroundColor: ['#60A5FA','#34D399','#FBBF24','#F472B6','#A78BFA'] }] },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }

            // Weekly chart removed intentionally
        }

        function updateChartsFromData(charts) {
            if (!charts) return;
            if (window.profCharts.daily && charts.daily) {
                window.profCharts.daily.data.labels = charts.daily.labels;
                window.profCharts.daily.data.datasets[0].data = charts.daily.data;
                window.profCharts.daily.update();
            }
            if (window.profCharts.byType && charts.byType) {
                window.profCharts.byType.data.labels = charts.byType.labels;
                window.profCharts.byType.data.datasets[0].data = charts.byType.data;
                window.profCharts.byType.update();
            }
            // Weekly chart update removed intentionally
        }

        // Reutilizable: actualiza KPIs en DOM (prefix: kg | kf)
        function setKpiValues(prefix, data) {
            if (!data) return;
            const map = { 'porcentaje_completado':'porcentaje', 'tareas_completadas':'completadas', 'tareas_pendientes':'pendientes', 'tareas_por_semana':'tps', 'racha_dias':'racha' };
            Object.keys(map).forEach(k => {
                const id = `${prefix}-${map[k]}`;
                const el = document.getElementById(id);
                if (!el) return;
                el.textContent = (k === 'porcentaje_completado') ? ((data[k] ?? 0) + '%') : (data[k] ?? 0);
            });
        }

        document.getElementById('filter-paciente').addEventListener('change', function(e){
            const val = e.target.value;
            const baseMeta = document.querySelector('meta[name="base-url"]');
            const base = baseMeta ? baseMeta.content.replace(/\/$/,'') : '';
            const url = val ? `${base}/profesional/kpis?paciente=${val}` : `${base}/profesional/kpis`;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                .then(r => r.json())
                .then(json => {
                    console.log('kpis response:', json); // <--- útil para depuración
                    if (!json || !json.success) {
                        alert('No se pudieron cargar métricas.');
                        return;
                    }

                    // KPIs generales siempre se actualizan
                    if (json.kpis_general) setKpiValues('kg', json.kpis_general);

                    // KPIs filtrados (si vienen)
                    if (json.kpis_filtrado) {
                        // asegurarse que la sección filtrada está visible
                        const kfSection = document.getElementById('kpis-filtrado');
                        if (kfSection) kfSection.style.display = 'block';
                        setKpiValues('kf', json.kpis_filtrado);
                    } else {
                        const kfSection = document.getElementById('kpis-filtrado');
                        if (kfSection) kfSection.style.display = 'none';
                    }

                    // Charts: si aún no existen, crearlos; si existen, actualizarlos
                    if (json.charts) {
                        if (!window.profCharts || (!window.profCharts.daily && !window.profCharts.byType)) {
                            createChartsFromData(json.charts);
                        } else {
                            updateChartsFromData(json.charts);
                        }
                    }
                })
                .catch(err => {
                    console.error('Error fetching kpis:', err);
                    alert('Error de conexión al obtener métricas.');
                });
        });
    </script>
</body>
</html>