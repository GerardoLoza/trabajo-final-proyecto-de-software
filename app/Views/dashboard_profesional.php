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
        .entity-section {
            margin-bottom: 40px;
            scroll-margin-top: 20px;
        }

        /* Estilos para los íconos de las tarjetas de estadísticas */
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
        }

        /* Asegurar alineación en tablas */
        table td {
            vertical-align: middle;
        }

        /* Timeline Horizontal para Planes Estándar */
        .timeline-wrapper {
            overflow-x: auto;
            padding: 20px 0;
            white-space: nowrap;
            scrollbar-width: thin;
            scrollbar-color: #000033 #f0f0f0;
        }

        .timeline-list {
            display: inline-flex;
            padding: 0 20px;
            position: relative;
        }

        /* Línea conectora */
        .timeline-list::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 40px;
            right: 40px;
            height: 3px;
            background: #e5e7eb;
            z-index: 0;
        }

        .timeline-item {
            width: 220px;
            margin-right: 30px;
            white-space: normal;
            z-index: 1;
            vertical-align: top;
            position: relative;
        }

        .timeline-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #000033;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.8em;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #e5e7eb;
        }

        .timeline-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            font-size: 0.9em;
        }

        .day-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75em;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: inline-block;
        }

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

            <div
                style="padding: 15px 20px 5px; color: #aaa; font-size: 0.75em; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                Configuración</div>

            <button class="nav-btn" onclick="scrollToSection('medicamentos')">
                <i class="fas fa-pills" style="width:20px;"></i> Medicamentos
            </button>
            <button class="nav-btn" onclick="scrollToSection('diagnosticos')">
                <i class="fas fa-stethoscope" style="width:20px;"></i> Diagnósticos
            </button>
            <button class="nav-btn" onclick="scrollToSection('tipos-tarea')">
                <i class="fas fa-tasks" style="width:20px;"></i> Tipos de Tareas
            </button>

            <button onclick="window.location.href='<?= base_url('logout') ?>'" class="nav-btn"
                style="margin-top: auto; background-color: #dc2626;">
                <i class="fas fa-sign-out-alt" style="width:20px;"></i> Cerrar Sesión
            </button>
            <button class="nav-btn" onclick="scrollToSection('planes-estandar')">
                <i class="fas fa-book-medical" style="width:20px;"></i> Planes Estándar
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

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    
                    <div class="full-width" style="width:100%; display:none;" id="kpis-filtrado">
                        <h4 style="margin:0 0 10px 0; color:#475569; border-bottom:1px solid #eee; padding-bottom:5px;">
                            <i class="fas fa-user-tag"></i> Paciente Seleccionado
                        </h4>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#eef2ff; color:#4338ca;"><i class="fas fa-user"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value" id="kf-porcentaje">0%</div>
                                    <div class="stat-label">Cumplimiento</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#ecfeff; color:#0891b2;"><i class="fas fa-check"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value" id="kf-completadas">0</div>
                                    <div class="stat-label">Completadas</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#fff7ed; color:#f97316;"><i class="fas fa-hourglass"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value" id="kf-pendientes">0</div>
                                    <div class="stat-label">Pendientes</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#fef3c7; color:#b45309;"><i class="fas fa-calendar-week"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value" id="kf-tps">0</div>
                                    <div class="stat-label">Tareas/sem</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-fire"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value" id="kf-racha">0</div>
                                    <div class="stat-label">Racha Días</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="full-width" style="width:100%;" id="kpis-generales">
                        <h4 style="margin:0 0 10px 0; color:#475569; border-bottom:1px solid #eee; padding-bottom:5px;">
                            <i class="fas fa-globe"></i> Métricas Globales
                        </h4>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#ecfeff; color:#0891b2;"><i class="fas fa-percent"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value" id="kg-porcentaje"><?= esc($kpis_general['porcentaje_completado'] ?? 0) ?>%</div>
                                    <div class="stat-label">Cumplimiento Global</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#f0fdf4; color:#10b981;"><i class="fas fa-check-circle"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value" id="kg-completadas"><?= esc($kpis_general['tareas_completadas'] ?? 0) ?></div>
                                    <div class="stat-label">Tareas Completadas</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#fff7ed; color:#f97316;"><i class="fas fa-hourglass-half"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value" id="kg-pendientes"><?= esc($kpis_general['tareas_pendientes'] ?? 0) ?></div>
                                    <div class="stat-label">Tareas Pendientes</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background:#f3e8ff; color:#7c3aed;"><i class="fas fa-fire"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value" id="kg-racha"><?= esc($kpis_general['racha_dias'] ?? 0) ?></div>
                                    <div class="stat-label">Racha Máxima</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 10px;">
                        <div style="margin-bottom: 20px;">
                            <label style="font-weight:600; display:block; margin-bottom:8px; color:#334155;">
                                <i class="fas fa-filter"></i> Filtrar estadísticas por paciente:
                            </label>
                            <select id="filter-paciente" class="input-styled" style="max-width: 400px;">
                                <option value="">-- Ver Globales --</option>
                                <?php if (!empty($listaPacientes)): foreach($listaPacientes as $pac): ?>
                                    <?php $pid = is_object($pac) ? $pac->id_usuario : $pac['id_usuario']; ?>
                                    <option value="<?= esc($pid) ?>">
                                        <?= esc($pac->nombre . ' ' . $pac->apellido) ?>
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>

                        <div style="display:flex; gap:20px; flex-wrap:wrap;">
                            <div style="flex: 2; min-width: 300px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                <h5 style="margin:0 0 15px 0; color:#64748b;">Evolución (Últimos 28 días)</h5>
                                <div class="chart-container" style="height: 250px;">
                                    <canvas id="chart-daily"></canvas>
                                </div>
                            </div>
                            
                            <div style="flex: 1; min-width: 250px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                <h5 style="margin:0 0 15px 0; color:#64748b;">Por Tipo de Tarea</h5>
                                <div class="chart-container" style="height: 250px;">
                                    <canvas id="chart-type"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <div class="stat-icon" style="background:#e0f2fe; color:#0284c7;">
                            <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= esc($totalPacientes) ?></div>
                        <div class="stat-label">Pacientes Asignados</div>
                    </div>
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
                        <?php if (!empty($listaPlanes) && is_array($listaPlanes)): ?>
                            <?php foreach ($listaPlanes as $plan): ?>
                                <tr data-id="<?= esc($plan->id) ?>" data-nombre="<?= esc($plan->nombre) ?>"
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
                                        <span id="badge-estado-<?= $plan->id ?>"
                                            style="background:<?= $colorBg ?>; color:<?= $colorTxt ?>; padding:2px 8px; border-radius:12px; font-size:0.75em; font-weight:700; text-transform:uppercase;">
                                            <?= esc($plan->estado) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn-secondary btn-icon"
                                                onclick="openTasksModal(<?= esc($plan->id) ?>)" title="Gestionar Tareas">
                                                <i class="fas fa-list-check"></i>
                                            </button>
                                            <button class="btn-edit btn-icon"
                                                onclick="openModal('planes', 'edit', this.closest('tr'))" title="Editar Plan">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="btn-delete btn-icon"
                                                onclick="deleteRecord('planes', <?= esc($plan->id) ?>)" title="Eliminar Plan">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <button class="btn-view btn-icon" onclick="openProgressModal(<?= esc($plan->id) ?>)"
                                                title="Ver Progreso">
                                                <i class="fas fa-chart-pie"></i>
                                            </button>
                                            <button class="btn-edit btn-icon" onclick="togglePlanStatus(<?= esc($plan->id) ?>)"
                                                title="Cambiar Estado"
                                                style="background-color: #4b5563; border-color: #4b5563;">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">No hay planes registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="planes-estandar" class="entity-section">
            <div class="content-card">
                <div class="header-section">
                    <h2>Planes Estandarizados (Plantillas)</h2>
                    <button class="btn-primary" onclick="openCreateStandardPlanModal()">
                        <i class="fas fa-plus"></i> Nueva Plantilla
                    </button>
                </div>

                <table id="planes-estandar-table">
                    <thead>
                        <tr>
                            <th>Diagnóstico (Patología)</th>
                            <th>Nombre del Plan</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($listaPlanesEstandar)): ?>
                            <?php foreach ($listaPlanesEstandar as $pe): ?>
                                <tr>
                                    <td>
                                        <span
                                            style="background:#e0f2fe; color:#0369a1; padding:4px 8px; border-radius:12px; font-weight:600; font-size:0.85em;">
                                            <?= esc($pe->nombre_diagnostico) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= esc($pe->nombre) ?></strong></td>
                                    <td><small style="color:#64748b"><?= esc($pe->descripcion) ?></small></td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn-view btn-icon"
                                                onclick="openStandardPlanModal(<?= esc($pe->id) ?>, '<?= esc($pe->nombre) ?>')"
                                                title="Ver Esquema Visual">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <button class="btn-primary btn-icon"
                                                onclick="openAssignModal(<?= esc($pe->id) ?>, '<?= esc($pe->nombre) ?>')"
                                                title="Asignar a Paciente" style="background-color: #059669; border:none;">
                                                <i class="fas fa-user-check"></i>
                                            </button>

                                            <button class="btn-secondary btn-icon"
                                                onclick="openStdTasksManager(<?= esc($pe->id) ?>, '<?= esc($pe->nombre) ?>')"
                                                title="Gestionar Tareas">
                                                <i class="fas fa-list-check"></i>
                                            </button>

                                            <button class="btn-delete btn-icon"
                                                onclick="deleteRecord('planes-estandar', <?= esc($pe->id) ?>)"
                                                title="Eliminar Plantilla">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">No hay plantillas definidas.</td>
                            </tr>
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
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($listaPacientes)): ?>
                            <?php foreach ($listaPacientes as $paciente): ?>
                                <tr>
                                    <td><?= esc($paciente->nombre . ' ' . $paciente->apellido) ?></td>
                                    <td><?= esc($paciente->email) ?></td>
                                    <td><span
                                            style="background:#f1f5f9; padding:4px 8px; border-radius:12px; font-size:0.85em; font-weight:600; color:#475569;"><?= esc($paciente->nombre_rol) ?></span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn-edit btn-icon"
                                                onclick="alert('Funcionalidad de perfil pendiente')" title="Ver Perfil">
                                                <i class="fas fa-user"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">No tienes pacientes asignados.</td>
                            </tr>
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
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaMedicamentos as $m): ?>
                            <tr data-id="<?= esc($m->nombre) ?>" data-nombre="<?= esc($m->nombre) ?>">
                                <td><?= esc($m->nombre) ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn-delete btn-icon"
                                            onclick="deleteRecord('medicamentos', '<?= esc($m->nombre) ?>')"
                                            title="Eliminar">
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
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaDiagnosticos as $d): ?>
                            <tr data-id="<?= esc($d->nombre) ?>" data-nombre="<?= esc($d->nombre) ?>"
                                data-descripcion="<?= esc($d->descripcion) ?>">
                                <td><strong><?= esc($d->nombre) ?></strong></td>
                                <td><?= esc($d->descripcion) ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn-edit btn-icon"
                                            onclick="openDynamicModal('diagnosticos', 'edit', this.closest('tr'))"
                                            title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="btn-delete btn-icon"
                                            onclick="deleteRecord('diagnosticos', '<?= esc($d->nombre) ?>')"
                                            title="Eliminar">
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
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaTiposTarea as $t): ?>
                            <tr data-id="<?= esc($t->id_tipo_tarea) ?>" data-nombre="<?= esc($t->nombre) ?>">
                                <td><?= esc($t->nombre) ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn-edit btn-icon"
                                            onclick="openDynamicModal('tipos-tarea', 'edit', this.closest('tr'))"
                                            title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="btn-delete btn-icon"
                                            onclick="deleteRecord('tipos-tarea', <?= esc($t->id_tipo_tarea) ?>)"
                                            title="Eliminar">
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
                        <div
                            style="display:flex; justify-content:space-between; margin-bottom:5px; font-weight:600; color:#444;">
                            <span>Porcentaje completado</span>
                            <span id="progress-percent-text">0%</span>
                        </div>
                        <div
                            style="background-color: #e5e7eb; border-radius: 10px; height: 24px; width: 100%; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                            <div id="progress-bar-fill"
                                style="background-color: #10b981; height: 100%; width: 0%; text-align: center; line-height: 24px; color: white; font-size: 0.85em; font-weight: bold; transition: width 0.6s ease-in-out;">
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

        <div id="create-std-plan-modal" class="modal">
            <div class="modal-content"
                style="max-width: 900px; border-radius: 12px; display: flex; flex-direction: column; max-height: 90vh;">

                <div class="modal-header" style="flex-shrink: 0;">
                    <h3 style="margin:0; color:#1e293b;">Nueva Plantilla de Tratamiento</h3>
                    <button class="close-btn" onclick="closeModal('create-std-plan-modal')">&times;</button>
                </div>

                <div class="modal-body" style="overflow-y: auto; padding: 25px; flex-grow: 1;">

                    <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
                        <h4 style="margin-top:0; color:#64748b; font-size:0.9rem; text-transform:uppercase;">1. Datos
                            Generales</h4>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                            <div>
                                <label style="font-weight:600; display:block; margin-bottom:5px;">Nombre del Plan
                                    *</label>
                                <input type="text" id="csp-nombre" class="input-styled"
                                    placeholder="Ej: Protocolo Diabetes Inicial">
                            </div>
                            <div>
                                <label style="font-weight:600; display:block; margin-bottom:5px;">Diagnóstico Asociado
                                    *</label>
                                <select id="csp-diagnostico" class="input-styled">
                                    <option value="">Seleccionar Diagnóstico...</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label style="font-weight:600; display:block; margin-bottom:5px;">Descripción</label>
                            <textarea id="csp-descripcion" class="input-styled" rows="2"
                                placeholder="Breve descripción del objetivo de este plan..."></textarea>
                        </div>
                    </div>

                    <div>
                        <h4 style="margin-top:0; color:#64748b; font-size:0.9rem; text-transform:uppercase;">2. Tareas
                            del Protocolo</h4>

                        <div
                            style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <div
                                style="display:grid; grid-template-columns: 0.5fr 2fr 1fr 1fr auto; gap: 10px; align-items: end;">
                                <div>
                                    <label style="font-size:0.8em; font-weight:600;">Día</label>
                                    <input type="number" id="temp-dia" value="1" min="1" class="input-styled"
                                        style="padding:8px;">
                                </div>
                                <div>
                                    <label style="font-size:0.8em; font-weight:600;">Descripción Tarea</label>
                                    <input type="text" id="temp-desc" class="input-styled" style="padding:8px;"
                                        placeholder="Actividad...">
                                </div>
                                <div>
                                    <label style="font-size:0.8em; font-weight:600;">Tipo</label>
                                    <select id="temp-tipo" class="input-styled" style="padding:8px;"></select>
                                </div>
                                <div>
                                    <label style="font-size:0.8em; font-weight:600;">Medicamento</label>
                                    <select id="temp-med" class="input-styled" style="padding:8px;">
                                        <option value="">- N/A -</option>
                                    </select>
                                </div>
                                <div>
                                    <button type="button" class="btn-secondary" onclick="addTempTask()"
                                        style="background:#000033; color:white; border:none;">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <table style="width:100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f1f5f9; text-align: left; font-size: 0.9em; color: #475569;">
                                    <th style="padding: 10px;">Día Relativo</th>
                                    <th style="padding: 10px;">Descripción</th>
                                    <th style="padding: 10px;">Tipo</th>
                                    <th style="padding: 10px;">Medicamento</th>
                                    <th style="padding: 10px; text-align: right;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="csp-tasks-list">
                                <tr id="csp-empty-row">
                                    <td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">No hay
                                        tareas agregadas aún.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer"
                    style="padding: 15px 25px; border-top: 1px solid #e2e8f0; display:flex; justify-content: flex-end; gap: 10px;">
                    <button class="btn-cancel" onclick="closeModal('create-std-plan-modal')">Cancelar</button>
                    <button class="btn-save" onclick="submitStandardPlan()">Crear Plantilla Completa</button>
                </div>
            </div>
        </div>

        <div id="std-tasks-modal" class="modal">
            <div class="modal-content" style="max-width: 800px;">
                <div class="modal-header">
                    <h3 id="stm-title">Editar Tareas de Plantilla</h3>
                    <button class="close-btn" onclick="closeModal('std-tasks-modal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div
                        style="background:#f8fafc; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #e2e8f0;">
                        <h4 style="margin-top:0; font-size:0.95rem; color:#475569;">+ Agregar Nueva Tarea</h4>
                        <form id="std-task-form" onsubmit="addStdTask(event)"
                            style="display:grid; grid-template-columns: 1fr 2fr 1fr; gap:10px; align-items:end;">
                            <input type="hidden" id="stm-plan-id" name="id_plan_estandar">

                            <div>
                                <label style="font-size:0.8em; font-weight:600;">Día Relativo</label>
                                <input type="number" name="dia_relativo" min="1" value="1" required class="input-styled"
                                    placeholder="Día" style="padding:8px;">
                            </div>

                            <div>
                                <label style="font-size:0.8em; font-weight:600;">Descripción</label>
                                <input type="text" name="descripcion" required class="input-styled"
                                    placeholder="Ej: Tomar pastilla..." style="padding:8px;">
                            </div>

                            <div>
                                <label style="font-size:0.8em; font-weight:600;">Tipo</label>
                                <select name="id_tipo_tarea" id="stm-tipo" required class="input-styled"
                                    style="padding:8px;"></select>
                            </div>

                            <div style="grid-column: 1 / -1;">
                                <label style="font-size:0.8em; font-weight:600;">Medicamento (Opcional)</label>
                                <select name="nombre_medicamento" id="stm-med" class="input-styled"
                                    style="padding:8px;">
                                    <option value="">-- Ninguno --</option>
                                </select>
                            </div>

                            <div style="grid-column: 1 / -1; text-align:right; margin-top:5px;">
                                <button type="submit" class="btn-save"
                                    style="padding: 6px 12px; font-size: 0.9em;">Agregar Tarea</button>
                            </div>
                        </form>
                    </div>

                    <table style="width:100%; border-collapse:collapse; font-size:0.9em;">
                        <thead>
                            <tr style="background:#f1f5f9; text-align:left;">
                                <th style="padding:8px;">Día</th>
                                <th style="padding:8px;">Descripción</th>
                                <th style="padding:8px;">Tipo</th>
                                <th style="padding:8px;">Medicamento</th>
                                <th style="padding:8px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="stm-table-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="assign-plan-modal" class="modal">
            <div class="modal-content" style="max-width: 500px; border-radius: 12px;">
                <div class="modal-header">
                    <h3 id="apm-title">Asignar Plan</h3>
                    <button class="close-btn" onclick="closeModal('assign-plan-modal')">&times;</button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <p style="color:#64748b; margin-bottom:20px;">
                        Se creará un nuevo plan vigente basado en esta plantilla.
                    </p>

                    <form onsubmit="submitAssignment(event)">
                        <input type="hidden" id="apm-plan-id">

                        <div class="form-group">
                            <label style="font-weight:600; color:#333;">Paciente *</label>
                            <select id="apm-paciente" required class="input-styled" style="width:100%;">
                                <option value="">Seleccionar Paciente...</option>
                            </select>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label style="font-weight:600; color:#333;">Fecha Inicio *</label>
                                <input type="date" id="apm-inicio" required class="input-styled" style="width:100%;">
                            </div>
                            <div class="form-group">
                                <label style="font-weight:600; color:#333;">Fecha Fin (Est.)</label>
                                <input type="date" id="apm-fin" class="input-styled" style="width:100%;">
                            </div>
                        </div>

                        <div style="text-align:right; margin-top:20px; border-top:1px solid #eee; padding-top:15px;">
                            <button type="button" class="btn-cancel"
                                onclick="closeModal('assign-plan-modal')">Cancelar</button>
                            <button type="submit" class="btn-save">Confirmar Asignación</button>
                        </div>
                    </form>
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
        'listaTiposTarea' => $listaTiposTarea ?? [],
        'listaMedicamentos' => $listaMedicamentos ?? []
    ]) ?>

    <?= view('planes/tasks_modal') ?>

    <script>
    window.serverData = {
        // Datos básicos
        pacientes: <?= json_encode($todosLosPacientes ?? []) ?>,
        diagnosticos: <?= json_encode($listaDiagnosticos ?? []) ?>,
        tipos: <?= json_encode($listaTiposTarea ?? []) ?>,
        medicamentos: <?= json_encode($listaMedicamentos ?? []) ?>,
        role: <?= json_encode(session()->get('nombre_rol') ?? '') ?>,
        
        // Datos para Gráficos y KPIs (si existen)
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
        let tempStandardTasks = []; // Array temporal

        function openCreateStandardPlanModal() {
            // 1. Limpiar todo
            tempStandardTasks = [];
            document.getElementById('csp-nombre').value = '';
            document.getElementById('csp-descripcion').value = '';
            document.getElementById('temp-desc').value = '';
            document.getElementById('temp-dia').value = '1';
            renderTempTasks(); // Limpia la tabla visual

            // 2. Llenar Selects (Diagnósticos, Tipos, Meds) desde window.serverData
            const diagSelect = document.getElementById('csp-diagnostico');
            const tipoSelect = document.getElementById('temp-tipo');
            const medSelect = document.getElementById('temp-med');

            // Diagnósticos
            diagSelect.innerHTML = '<option value="">Seleccionar Diagnóstico...</option>';
            window.serverData.diagnosticos.forEach(d => {
                diagSelect.innerHTML += `<option value="${d.nombre}">${d.nombre}</option>`;
            });

            // Tipos Tarea
            tipoSelect.innerHTML = '';
            window.serverData.tipos.forEach(t => {
                tipoSelect.innerHTML += `<option value="${t.id_tipo_tarea}">${t.nombre}</option>`;
            });

            // Medicamentos
            medSelect.innerHTML = '<option value="">- N/A -</option>';
            window.serverData.medicamentos.forEach(m => {
                medSelect.innerHTML += `<option value="${m.nombre}">${m.nombre}</option>`;
            });

            // 3. Mostrar Modal
            document.getElementById('create-std-plan-modal').classList.add('active');
        }

        function addTempTask() {
            const dia = document.getElementById('temp-dia').value;
            const desc = document.getElementById('temp-desc').value;
            const tipoId = document.getElementById('temp-tipo').value;
            const med = document.getElementById('temp-med').value;

            // Obtener texto del tipo para mostrar en tabla
            const tipoSelect = document.getElementById('temp-tipo');
            const tipoTexto = tipoSelect.options[tipoSelect.selectedIndex].text;

            if (!desc || !dia) {
                alert("Completa la descripción y el día.");
                return;
            }

            // Agregar al array
            tempStandardTasks.push({
                dia_relativo: dia,
                descripcion: desc,
                id_tipo_tarea: tipoId,
                nombre_tipo: tipoTexto, // Solo para visual
                nombre_medicamento: med || null
            });

            // Limpiar inputs tarea
            document.getElementById('temp-desc').value = '';
            document.getElementById('temp-med').value = '';

            renderTempTasks();
        }

        function removeTempTask(index) {
            tempStandardTasks.splice(index, 1);
            renderTempTasks();
        }

        function renderTempTasks() {
            const tbody = document.getElementById('csp-tasks-list');
            tbody.innerHTML = '';

            if (tempStandardTasks.length === 0) {
                tbody.innerHTML = '<tr id="csp-empty-row"><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">No hay tareas agregadas aún.</td></tr>';
                return;
            }

            // Ordenar por día para que se vea lógico
            tempStandardTasks.sort((a, b) => a.dia_relativo - b.dia_relativo);

            tempStandardTasks.forEach((t, index) => {
                const medLabel = t.nombre_medicamento ? `<span style="color:#000033;">💊 ${t.nombre_medicamento}</span>` : '-';

                tbody.innerHTML += `
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px;"><strong>Día ${t.dia_relativo}</strong></td>
                <td style="padding: 10px;">${t.descripcion}</td>
                <td style="padding: 10px;"><span style="background:#e0f2fe; padding:2px 6px; border-radius:4px; font-size:0.85em;">${t.nombre_tipo}</span></td>
                <td style="padding: 10px;">${medLabel}</td>
                <td style="padding: 10px; text-align: right;">
                    <button class="btn-delete btn-icon" onclick="removeTempTask(${index})" style="width:28px; height:28px;">
                        <i class="fas fa-trash" style="font-size:12px;"></i>
                    </button>
                </td>
            </tr>
        `;
            });
        }

        function submitStandardPlan() {
            const nombre = document.getElementById('csp-nombre').value;
            const diag = document.getElementById('csp-diagnostico').value;
            const desc = document.getElementById('csp-descripcion').value;

            if (!nombre || !diag) {
                alert("El nombre del plan y el diagnóstico son obligatorios.");
                return;
            }

            const payload = {
                nombre: nombre,
                nombre_diagnostico: diag,
                descripcion: desc,
                tareas: tempStandardTasks // Enviamos el array completo
            };

            const baseMeta = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '');
            const token = document.querySelector('meta[name="csrf-token"]').content;

            fetch(`${baseMeta}/profesional/planes-estandar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', // Importante: JSON
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert('Error: ' + res.message);
                    }
                })
                .catch(err => console.error(err));
        }

        /* --- LÓGICA DE ASIGNACIÓN DE PLANTILLA --- */

        function openAssignModal(planId, planName) {
            const modal = document.getElementById('assign-plan-modal');
            document.getElementById('apm-title').innerText = `Asignar: ${planName}`;
            document.getElementById('apm-plan-id').value = planId;

            // Llenar select de pacientes
            const select = document.getElementById('apm-paciente');
            select.innerHTML = '<option value="">Seleccionar Paciente...</option>';

            if (window.serverData && window.serverData.pacientes) {
                window.serverData.pacientes.forEach(p => {
                    select.innerHTML += `<option value="${p.id_usuario}">${p.nombre} ${p.apellido} (${p.email})</option>`;
                });
            }

            // Setear fecha inicio a hoy por defecto
            document.getElementById('apm-inicio').valueAsDate = new Date();

            modal.classList.add('active');
        }

        function submitAssignment(e) {
            e.preventDefault();

            const planId = document.getElementById('apm-plan-id').value;
            const pacienteId = document.getElementById('apm-paciente').value;
            const inicio = document.getElementById('apm-inicio').value;
            const fin = document.getElementById('apm-fin').value;

            if (!pacienteId || !inicio) {
                alert("Paciente y Fecha de Inicio son obligatorios.");
                return;
            }

            const payload = {
                id_plan_estandar: planId,
                id_paciente: pacienteId,
                fecha_inicio: inicio,
                fecha_fin: fin
            };

            const baseMeta = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '');
            const token = document.querySelector('meta[name="csrf-token"]').content;

            // Mostrar feedback de carga
            const btnSubmit = e.target.querySelector('button[type="submit"]');
            const originalText = btnSubmit.innerText;
            btnSubmit.innerText = "Asignando...";
            btnSubmit.disabled = true;

            fetch(`${baseMeta}/profesional/planes-estandar/asignar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert(res.message);
                        location.reload(); // Recargar para ver el nuevo plan en la lista de gestión
                    } else {
                        alert('Error: ' + res.message);
                        btnSubmit.innerText = originalText;
                        btnSubmit.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error de conexión.');
                    btnSubmit.innerText = originalText;
                    btnSubmit.disabled = false;
                });
        }
    </script>
    <script>
        // Configuración de campos para el modal dinámico
        // Configuración de campos para el modal dinámico
        const formConfigs = {
            'medicamentos': [{ name: 'nombre', label: 'Nombre del Medicamento', type: 'text', required: true }],
            'tipos-tarea': [{ name: 'nombre', label: 'Nombre del Tipo', type: 'text', required: true }],
            'diagnosticos': [
                { name: 'nombre', label: 'Nombre Diagnóstico', type: 'text', required: true },
                { name: 'descripcion', label: 'Descripción', type: 'textarea', required: false }
            ], // <--- AQUÍ FALTABA LA COMA
            'planes-estandar': [
                { name: 'nombre', label: 'Nombre de la Plantilla', type: 'text', required: true },
                { name: 'descripcion', label: 'Descripción (Opcional)', type: 'textarea', required: false },
                // El 'source: diagnosticos' tomará los datos de window.serverData.diagnosticos
                { name: 'nombre_diagnostico', label: 'Patología Asociada', type: 'select', source: 'diagnosticos', required: true }
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
            if (config) {
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
                    if (field.required) input.required = true;

                    // Estilo base para inputs dinámicos
                    input.style.width = '100%'; input.style.padding = '10px';
                    input.style.border = '1px solid #cbd5e1'; input.style.borderRadius = '6px';

                    if (mode === 'edit' && trElement) {
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
    
        function openStandardPlanModal(id, nombre) {
            const modal = document.getElementById('standard-plan-modal');
            const title = document.getElementById('sp-modal-title');
            const container = document.getElementById('sp-timeline-container');
            const baseUrl = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, "");

            title.textContent = `Plantilla: ${nombre}`;
            container.innerHTML = '<div style="text-align:center; padding:30px; color:#888;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
            modal.classList.add('active');

            fetch(`${baseUrl}/profesional/planes-estandar/${id}/tareas`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(res => {
                    if (!res.success || !res.data || res.data.length === 0) {
                        container.innerHTML = '<div style="text-align:center; padding:30px; color:#64748b;">Esta plantilla no tiene tareas definidas.</div>';
                        return;
                    }

                    let html = '<div class="timeline-list">';
                    res.data.forEach((t, index) => {
                        const num = index + 1;
                        const medInfo = t.nombre_medicamento ? `<div style="margin-top:5px; font-size:0.85em; color:#000033;">💊 ${t.nombre_medicamento}</div>` : '';

                        html += `
            <div class="timeline-item">
                <div class="timeline-dot">${num}</div>
                <div class="timeline-card">
                    <span class="day-badge">Día ${t.dia_relativo}</span>
                    <div style="font-weight:600; color:#333; margin-bottom:4px;">${t.descripcion}</div>
                    ${medInfo}
                </div>
            </div>`;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    container.innerHTML = '<div style="text-align:center; color:red;">Error al cargar datos.</div>';
                });
        }

        /* --- GESTIÓN DE TAREAS EN PLANTILLA --- */

        function openStdTasksManager(planId, planName) {
            const modal = document.getElementById('std-tasks-modal');
            document.getElementById('stm-title').innerText = `Editar: ${planName}`;
            document.getElementById('stm-plan-id').value = planId;

            // Llenar selects del form (usando window.serverData)
            const tipoSelect = document.getElementById('stm-tipo');
            const medSelect = document.getElementById('stm-med');

            // Llenar Tipos
            tipoSelect.innerHTML = '';
            window.serverData.tipos.forEach(t => {
                tipoSelect.innerHTML += `<option value="${t.id_tipo_tarea}">${t.nombre}</option>`;
            });

            // Llenar Medicamentos
            medSelect.innerHTML = '<option value="">-- Ninguno --</option>';
            window.serverData.medicamentos.forEach(m => {
                medSelect.innerHTML += `<option value="${m.nombre}">${m.nombre}</option>`;
            });

            loadStdTasks(planId);
            modal.classList.add('active');
        }

        function loadStdTasks(planId) {
            const tbody = document.getElementById('stm-table-body');
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">Cargando...</td></tr>';

            const baseMeta = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '');

            fetch(`${baseMeta}/profesional/planes-estandar/${planId}/tareas`)
                .then(r => r.json())
                .then(res => {
                    tbody.innerHTML = '';
                    if (!res.data || res.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#888;">Sin tareas definidas.</td></tr>';
                        return;
                    }

                    res.data.forEach(t => {
                        const med = t.nombre_medicamento || '-';
                        tbody.innerHTML += `
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:8px;"><strong>Día ${t.dia_relativo}</strong></td>
                        <td style="padding:8px;">${t.descripcion}</td>
                        <td style="padding:8px;"><span style="background:#e0f2fe; padding:2px 6px; border-radius:4px; font-size:0.85em;">${t.nombre_tipo || 'Tarea'}</span></td>
                        <td style="padding:8px; color:#666;">${med}</td>
                        <td style="padding:8px;">
                            <button class="btn-delete btn-icon" onclick="deleteStdTask(${t.id}, ${planId})" style="width:24px; height:24px; font-size:12px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                    });
                });
        }

        function addStdTask(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const baseMeta = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '');
            const token = document.querySelector('meta[name="csrf-token"]').content;

            // Ruta de creación de tarea
            fetch(`${baseMeta}/profesional/tareas-estandar`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                },
                body: formData
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        form.reset();
                        // Restaurar ID plan pq el reset lo borra
                        document.getElementById('stm-plan-id').value = formData.get('id_plan_estandar');
                        loadStdTasks(formData.get('id_plan_estandar')); // Recargar tabla
                    } else {
                        alert('Error al agregar tarea.');
                    }
                });
        }

        function deleteStdTask(idTask, idPlan) {
            if (!confirm('¿Eliminar tarea de la plantilla?')) return;
            const baseMeta = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '');
            const token = document.querySelector('meta[name="csrf-token"]').content;

            fetch(`${baseMeta}/profesional/tareas-estandar/${idTask}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                }
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) loadStdTasks(idPlan);
                    else alert('Error al eliminar.');
                });
        }




    </script>
</body>
</html> 