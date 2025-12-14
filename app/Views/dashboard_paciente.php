<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Salud - HealthTracker</title>
    <link rel="stylesheet" href="<?= base_url('styles.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard_paciente.css') ?>">
    
    <style>
        /* Estilos específicos para el Dashboard Paciente */
        .welcome-banner {
            background: linear-gradient(135deg, #000033 0%, #1e3a8a 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .welcome-title { font-size: 1.8rem; font-weight: 700; margin-bottom: 10px; }
        .welcome-subtitle { font-size: 1.1rem; opacity: 0.9; font-weight: 300; }

        /* Badges de estado */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        .badge-vigente { background-color: #d1fae5; color: #065f46; }
        .badge-finalizado { background-color: #f3f4f6; color: #374151; }

        /* Estilos para el modal de tareas (Lista limpia) */
        .task-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #cbd5e1; /* Default gris */
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            transition: transform 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .task-card:hover { transform: translateX(2px); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        
        .task-card.pending { border-left-color: #f59e0b; } /* Naranja */
        .task-card.completed { border-left-color: #10b981; opacity: 0.85; } /* Verde */

        .task-info h4 { margin: 0 0 5px 0; color: #334155; font-size: 1rem; }
        .task-meta { font-size: 0.85em; color: #64748b; }
        
        .btn-complete {
            background-color: #fff;
            border: 1px solid #10b981;
            color: #10b981;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
        }
        .btn-complete:hover { background-color: #10b981; color: white; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h1>HealthTracker</h1>
        <nav>
            <button class="nav-btn active" onclick="location.reload()">
                <i class="fas fa-home" style="margin-right: 8px;"></i> Mi Panel
            </button>
            <button class="nav-btn" onclick="alert('Próximamente')">
                <i class="fas fa-file-medical" style="margin-right: 8px;"></i> Documentos
            </button>
            <button onclick="window.location.href='<?= base_url('logout') ?>'" class="nav-btn" style="margin-top: auto; background-color: #dc2626;">
                <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i> Cerrar Sesión
            </button>
        </nav>
    </aside>

    <main class="main-content">
        
        <div class="welcome-banner">
            <div class="welcome-title">Hola, <?= esc(session()->get('nombre')) ?> 👋</div>
        </div>

        <!-- NUEVO: KPI DE ADHERENCIA GLOBAL -->
        <div class="content-card adherence-card">
            <div class="adherence-header">
                <div style="display: flex; justify-content: space-between; align-items: start; gap: 20px;">
                    <div>
                        <h3 style="margin: 0; color: #1e293b;">Resumen General</h3>
                        <p style="margin: 8px 0 0 0; font-size: 0.95rem; color: #64748b;">Visión general de tus planes, tareas y adherencia</p>
                    </div>
                    <div style="flex: 0 0 auto;">
                        <select id="planFilter" class="plan-filter-select" onchange="updateAdherenceView(this.value)">
                            <option value="">Todos los Planes</option>
                            <?php if (!empty($listaPlanes) && is_array($listaPlanes)): ?>
                                <?php foreach ($listaPlanes as $plan): ?>
                                    <?php $planId = is_object($plan) ? $plan->id : $plan['id']; ?>
                                    <option value="<?= esc($planId) ?>">
                                        <?= esc(is_object($plan) ? $plan->nombre : $plan['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>

            <?php if ($adherenciaGlobal['total'] > 0): ?>
                <div class="adherence-content" id="adherenceContentContainer">
                    <div class="adherence-main-row">
                        <!-- Círculo de Progreso -->
                        <div class="adherence-circle-container">
                            <div class="adherence-circle-wrapper">
                                <svg class="adherence-circle" viewBox="0 0 120 120">
                                    <!-- Círculo de fondo -->
                                    <circle cx="60" cy="60" r="54" fill="none" stroke="#e2e8f0" stroke-width="8"></circle>
                                    <!-- Círculo de progreso -->
                                    <circle 
                                        cx="60" cy="60" r="54" 
                                        fill="none" 
                                        stroke="url(#adherenceGradient)" 
                                        stroke-width="8"
                                        stroke-linecap="round"
                                        stroke-dasharray="<?= ($adherenciaGlobal['porcentaje'] / 100) * 339.29 ?> 339.29"
                                        style="transform: rotate(-90deg); transform-origin: 60px 60px; transition: stroke-dasharray 0.6s ease;"
                                        id="adherenceCircleProgress"
                                    ></circle>
                                    <defs>
                                        <linearGradient id="adherenceGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color: #0284c7; stop-opacity: 1;" />
                                            <stop offset="100%" style="stop-color: #06b6d4; stop-opacity: 1;" />
                                        </linearGradient>
                                    </defs>
                                    <!-- Texto central -->
                                    <text x="60" y="55" text-anchor="middle" font-size="27" font-weight="700" fill="#0284c7" id="adherencePercentText">
                                        <?= esc($adherenciaGlobal['porcentaje'] ?? 0) ?>%
                                    </text>
                                    <text x="60" y="73" text-anchor="middle" font-size="12" fill="#64748b" font-weight="500" id="adherenceLabelText">
                                        Adherencia
                                    </text>
                                </svg>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="adherence-stats">
                            <div class="stat-item">
                                <div class="stat-icon-small" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-detail">
                                    <div class="stat-label">Completadas</div>
                                    <div class="stat-value" style="color: #10b981;" id="completedTasksCount">
                                        <?= esc($adherenciaGlobal['completadas'] ?? 0) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="stat-divider"></div>

                            <div class="stat-item">
                                <div class="stat-icon-small" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white;">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="stat-detail">
                                    <div class="stat-label">Total de Tareas</div>
                                    <div class="stat-value" style="color: #0284c7;" id="totalTasksCount">
                                        <?= esc($adherenciaGlobal['total'] ?? 0) ?>
                                    </div>
                                </div>
                            </div>

                            

                            <div class="stat-item">
                                <div class="stat-icon-small" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); color: white;">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div class="stat-detail">
                                    <div class="stat-label">Tareas Pendientes</div>
                                    <div class="stat-value" style="color: #ea580c;" id="pendingTasksCount">
                                        <?= esc($totalPendientes ?? 0) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar - Full Width -->
                    <div class="adherence-progress-wrapper">
                        <div class="progress-info">
                            <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">Progreso General</span>
                            <span style="font-size: 0.8rem; color: #94a3b8;" id="progressText">
                                <?= esc($adherenciaGlobal['completadas']) ?> de <?= esc($adherenciaGlobal['total']) ?> tareas
                            </span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-background">
                                <div class="progress-bar-fill" style="width: <?= min(esc($adherenciaGlobal['porcentaje']), 100) ?>%;" id="progressBarFill">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-adherence">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="empty-content">
                        <h4>Sin tareas asignadas</h4>
                        <p>Tu profesional de salud aún no ha asignado tareas a tus planes. Cuando las asigne, podrás monitorear tu adherencia aquí.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN ORIGINAL: TABLA DE PLANES -->
        <div class="content-card" style="margin-top: 40px;">
            <div class="header-section">
                <h3 style="margin:0; font-size: 1.5rem; color: #1e293b;">Mis Planes de Salud</h3>
                <button class="btn-primary" onclick="location.reload()" style="padding: 8px 15px; font-size: 0.9rem;">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>

            <table id="mis-planes-table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Diagnóstico</th>
                        <th>Profesional</th>
                        <th>Vigencia</th>
                        <th>Estado</th>
                        <th>Adherencia</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($listaPlanes) && is_array($listaPlanes)): ?>
                        <?php foreach ($listaPlanes as $plan): ?>
                            <?php 
                                $esVigente = ($plan->estado === 'Vigente');
                                $badgeClass = $esVigente ? 'badge-vigente' : 'badge-finalizado';
                                $profesional = isset($plan->nombre_profesional) ? $plan->nombre_profesional . ' ' . $plan->apellido_profesional : 'ID: ' . $plan->id_profesional;
                                
                                // Obtener adherencia del plan
                                $planId = is_object($plan) ? $plan->id : $plan['id'];
                                $adh = $adherenciaPorPlan[$planId] ?? ['porcentaje' => 0, 'completadas' => 0, 'total' => 0];
                            ?>
                            <tr>
                                <td>
                                    <strong><?= esc($plan->nombre) ?></strong><br>
                                    <small style="color:#64748b"><?= esc($plan->descripcion) ?></small>
                                </td>
                                <td><?= esc($plan->nombre_diagnostico) ?></td>
                                <td>Dr/a. <?= esc($profesional) ?></td>
                                <td>
                                    <small>Desde: <?= esc($plan->fecha_inicio) ?></small><br>
                                    <small>Hasta: <?= esc($plan->fecha_fin) ?></small>
                                </td>
                                <td><span class="badge <?= $badgeClass ?>"><?= esc($plan->estado) ?></span></td>
                                <td>
                                    <div style="font-weight: 600; color: #0284c7; margin-bottom: 4px;">
                                        <?= esc($adh['porcentaje']) ?>%
                                    </div>
                                    <div style="background: #f1f5f9; border-radius: 4px; height: 8px; overflow: hidden; width: 100%;">
                                        <div style="background: #10b981; height: 100%; width: <?= esc($adh['porcentaje']) ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn-primary" style="padding: 6px 12px; font-size: 0.85em;" onclick="openPatientTasksModal(<?= esc($planId) ?>, '<?= esc($plan->nombre) ?>')">
                                        Ver Tareas
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">No tienes planes asignados actualmente.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="patient-tasks-modal" class="modal">
        <div class="modal-content" style="max-width: 700px; border-radius: 12px; max-height: 90vh; display: flex; flex-direction: column;">
            
            <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                <div>
                    <h3 id="pt-modal-title" style="margin:0; color:#1e293b;">Tareas del Plan</h3>
                    <p style="margin:5px 0 0 0; font-size:0.9em; color:#64748b;">Revisa tu progreso y completa tus actividades.</p>
                </div>
                <button class="close-btn" onclick="closeModal('patient-tasks-modal')">&times;</button>
            </div>

            <!-- NUEVO: Barra de adherencia del plan dentro del modal -->
            <div style="padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: none;" id="pt-adherence-section">
                <div style="font-size: 0.85rem; color: #64748b; font-weight: 600; margin-bottom: 8px;">
                    Adherencia de este plan
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="flex: 1;">
                        <div style="background: #e0f2fe; border-radius: 10px; height: 20px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                            <div id="pt-adherence-bar" style="background: linear-gradient(90deg, #0284c7 0%, #06b6d4 100%); height: 100%; width: 0%; transition: width 0.3s ease;">
                            </div>
                        </div>
                    </div>
                    <div style="font-weight: 700; color: #0284c7; min-width: 45px; text-align: right;">
                        <span id="pt-adherence-percent">0</span>%
                    </div>
                </div>
                <div style="font-size: 0.75rem; color: #64748b; margin-top: 6px;">
                    <span id="pt-adherence-text">0 de 0 tareas completadas</span>
                </div>
            </div>

            <div class="modal-body" style="overflow-y: auto; padding: 20px; background-color: #f8fafc; flex-grow: 1;">
                <div id="pt-tasks-container">
                    <div style="text-align:center; padding: 20px; color: #64748b;">Cargando actividades...</div>
                </div>
            </div>

            <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding-top: 15px; text-align: right;">
                <button class="btn-cancel" onclick="closeModal('patient-tasks-modal')">Cerrar</button>
            </div>
        </div>
    </div>

    <div id="complete-task-modal" class="modal" style="z-index: 1200;">
        <div class="modal-content" style="max-width: 450px; border-radius: 12px;">
            <div class="modal-header">
                <h3>Registrar Progreso</h3>
                <button class="close-btn" onclick="closeModal('complete-task-modal')">&times;</button>
            </div>
            <form id="complete-task-form" action="" method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label style="font-weight: 600; color: #334155;">Fecha de Realización</label>
                    <input type="datetime-local" name="fecha_realizacion" required 
                           value="<?= date('Y-m-d\TH:i') ?>" 
                           style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; color: #334155;">Comentarios (Opcional)</label>
                    <textarea name="comentarios" rows="3" placeholder="¿Cómo te sentiste? ¿Hubo algún problema?"
                              style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
                </div>
                <div class="form-actions" style="justify-content: flex-end; display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-cancel" onclick="closeModal('complete-task-modal')">Cancelar</button>
                    <button type="submit" class="btn-save">✅ Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">

    <script>
        // Helpers básicos
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // 1. ABRIR MODAL DE LISTA DE TAREAS
        function openPatientTasksModal(planId, planName) {
            const modal = document.getElementById('patient-tasks-modal');
            const container = document.getElementById('pt-tasks-container');
            const title = document.getElementById('pt-modal-title');
            const adherenceSection = document.getElementById('pt-adherence-section');
            const baseUrl = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, "");

            title.textContent = `Tareas: ${planName}`;
            container.innerHTML = '<div style="text-align:center; padding: 20px; color: #64748b;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
            modal.classList.add('active');

            fetch(`${baseUrl}/paciente/planes/${planId}/tareas`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(res => {
                if(!res.success || !res.data || res.data.length === 0) {
                    container.innerHTML = '<div class="empty-state">No hay tareas registradas en este plan.</div>';
                    adherenceSection.style.display = 'none';
                    return;
                }

                // Calcular adherencia del plan
                const tasks = res.data;
                const completadas = tasks.filter(t => t.estado === 'Completada').length;
                const total = tasks.length;
                const porcentaje = total > 0 ? Math.round((completadas / total) * 100) : 0;

                // Mostrar barra de adherencia
                document.getElementById('pt-adherence-bar').style.width = `${Math.min(porcentaje, 100)}%`;
                document.getElementById('pt-adherence-percent').textContent = Math.min(porcentaje, 100);
                document.getElementById('pt-adherence-text').textContent = `${completadas} de ${total} tareas completadas`;
                adherenceSection.style.display = 'block';

                // Ordenar: Pendientes primero, luego fecha
                const sortedTasks = tasks.sort((a, b) => {
                    if (a.estado === 'Pendiente' && b.estado !== 'Pendiente') return -1;
                    if (a.estado !== 'Pendiente' && b.estado === 'Pendiente') return 1;
                    return new Date(a.fecha_programada) - new Date(b.fecha_programada);
                });

                let html = '';
                sortedTasks.forEach(t => {
                    const isPending = t.estado === 'Pendiente';
                    const statusClass = isPending ? 'pending' : 'completed';
                    const icon = isPending ? '<i class="far fa-clock"></i>' : '<i class="fas fa-check-circle"></i>';
                    const date = t.fecha_programada ? t.fecha_programada.replace('T', ' ') : 'Sin fecha';
                    
                    let actionBtn = '';
                    if(isPending) {
                        actionBtn = `<button class="btn-complete" onclick="openCompleteModal(${t.id_tarea})">Completar</button>`;
                    } else {
                        actionBtn = `<span style="color:#10b981; font-weight:600; font-size:0.9em;">¡Completada!</span>`;
                    }

                    const medInfo = t.nombre_medicamento ? `<br><span style="color:#4f46e5; font-size:0.9em;"><i class="fas fa-pills"></i> ${t.nombre_medicamento}</span>` : '';

                    html += `
                    <div class="task-card ${statusClass}">
                        <div class="task-info">
                            <div class="task-meta" style="margin-bottom:4px;">
                                ${icon} ${date}
                            </div>
                            <h4>${t.descripcion}</h4>
                            ${medInfo}
                            ${t.comentarios_paciente ? `<div style="margin-top:5px; font-size:0.85em; color:#64748b; font-style:italic;">" ${t.comentarios_paciente} "</div>` : ''}
                        </div>
                        <div style="margin-left: 15px;">
                            ${actionBtn}
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = '<div style="text-align:center; color:red;">No se pudieron cargar las tareas. Intenta recargar.</div>';
            });
        }

        // 2. ABRIR MODAL DE COMPLETAR
        function openCompleteModal(taskId) {
            const modal = document.getElementById('complete-task-modal');
            const form = document.getElementById('complete-task-form');
            const baseUrl = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, "");
            
            // Configurar action del form
            form.action = `${baseUrl}/paciente/tareas/${taskId}/completar`;
            
            modal.classList.add('active');
        }

        // 3. ACTUALIZAR VISTA DE ADHERENCIA SEGÚN FILTRO DE PLAN
        function updateAdherenceView(planId) {
            const baseUrl = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, "");
            
            // Si no hay plan seleccionado, mostrar adherencia global
            if (!planId || planId === '') {
                const percentText = document.getElementById('adherencePercentText');
                const completedCount = document.getElementById('completedTasksCount');
                const totalCount = document.getElementById('totalTasksCount');
                const progressText = document.getElementById('progressText');
                const progressBarFill = document.getElementById('progressBarFill');
                const circleProgress = document.getElementById('adherenceCircleProgress');

                percentText.textContent = '<?= esc($adherenciaGlobal['porcentaje'] ?? 0) ?>%';
                completedCount.textContent = '<?= esc($adherenciaGlobal['completadas'] ?? 0) ?>';
                totalCount.textContent = '<?= esc($adherenciaGlobal['total'] ?? 0) ?>';
                progressText.textContent = '<?= esc($adherenciaGlobal['completadas']) ?> de <?= esc($adherenciaGlobal['total']) ?> tareas';
                // actualizar contadores resumen (pendientes)
                const pendingCountEl = document.getElementById('pendingTasksCount');
                if (pendingCountEl) pendingCountEl.textContent = '<?= esc($totalPendientes ?? 0) ?>';
                
                const globalPercent = Math.min(<?= esc($adherenciaGlobal['porcentaje']) ?>, 100);
                progressBarFill.style.width = globalPercent + '%';
                circleProgress.setAttribute('stroke-dasharray', (globalPercent / 100 * 339.29) + ' 339.29');
                
                return;
            }

            // Fetch datos del plan específico
            fetch(`${baseUrl}/paciente/adherencia-plan/${planId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    const data = res.data;
                    const percentText = document.getElementById('adherencePercentText');
                    const completedCount = document.getElementById('completedTasksCount');
                    const totalCount = document.getElementById('totalTasksCount');
                    const progressText = document.getElementById('progressText');
                    const progressBarFill = document.getElementById('progressBarFill');
                    const circleProgress = document.getElementById('adherenceCircleProgress');

                    const percent = Math.min(data.porcentaje, 100);

                    percentText.textContent = data.porcentaje + '%';
                    completedCount.textContent = data.completadas;
                    totalCount.textContent = data.total;
                    progressText.textContent = data.completadas + ' de ' + data.total + ' tareas';
                    progressBarFill.style.width = percent + '%';
                    circleProgress.setAttribute('stroke-dasharray', (percent / 100 * 339.29) + ' 339.29');
                    // actualizar contadores resumen para plan especifico (pendientes)
                    const pendingCountEl = document.getElementById('pendingTasksCount');
                    if (pendingCountEl) pendingCountEl.textContent = (data.total - data.completadas >= 0) ? (data.total - data.completadas) : 0;
                } else {
                    console.error('Error fetching plan adherence:', res);
                }
            })
            .catch(err => {
                console.error('Error:', err);
            });
        }
    </script>
</body>
</html>