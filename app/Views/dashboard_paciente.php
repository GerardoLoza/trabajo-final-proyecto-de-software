<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Salud - HealthTracker</title>
    <link rel="stylesheet" href="<?= base_url('styles.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
            <button class="nav-btn" onclick="window.location.href='<?= base_url('paciente/documentos') ?>'">
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

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= esc($totalPlanes ?? 0) ?></div>
                    <div class="stat-label">Mis Planes</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #ffedd5; color: #ea580c;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value" style="color: #ea580c;"><?= esc($totalPendientes ?? 0) ?></div>
                    <div class="stat-label">Tareas Pendientes</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value" style="color: #16a34a;"><?= esc($totalCompletadas ?? 0) ?></div>
                    <div class="stat-label">Tareas Completadas</div>
                </div>
            </div>
        </div>

        <div class="content-card">
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($listaPlanes) && is_array($listaPlanes)): ?>
                        <?php foreach ($listaPlanes as $plan): ?>
                            <?php 
                                // Determinar estado visual
                                $esVigente = ($plan->estado === 'Vigente');
                                $badgeClass = $esVigente ? 'badge-vigente' : 'badge-finalizado';
                                // Asumimos que el modelo ya hizo join para traer nombre del medico, 
                                // sino usamos el ID o ajustamos el modelo.
                                $profesional = isset($plan->nombre_profesional) ? $plan->nombre_profesional . ' ' . $plan->apellido_profesional : 'ID: ' . $plan->id_profesional;
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
                                    <button class="btn-primary" style="padding: 6px 12px; font-size: 0.85em;" onclick="openPatientTasksModal(<?= esc($plan->id) ?>, '<?= esc($plan->nombre) ?>')">
                                        Ver Tareas
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">No tienes planes asignados actualmente.</td>
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
            const baseUrl = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, "");

            title.textContent = `Tareas: ${planName}`;
            container.innerHTML = '<div style="text-align:center; padding: 20px; color: #64748b;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
            modal.classList.add('active');

            // Usamos el endpoint público de tareas por plan (el mismo que usa el admin/profe)
            // Nota: Asegurate que la ruta 'paciente/mis-planes/(:num)/tareas' exista o usa una común
            // Como no definimos una ruta específica de paciente para ver tareas JSON,
            // vamos a asumir que podemos usar una ruta GET que cree en Routes.php o reusar logica.
            // SOLUCIÓN RÁPIDA: Si no tienes endpoint JSON para paciente, usa el de profesional ajustando permisos
            // O mejor: Crea la ruta en el grupo 'paciente' en Routes.php:
            // $routes->get('planes/(:num)/tareas', 'TareaController::porPlan/$1');
            
            fetch(`${baseUrl}/paciente/planes/${planId}/tareas`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(res => {
                if(!res.success || !res.data || res.data.length === 0) {
                    container.innerHTML = '<div class="empty-state">No hay tareas registradas en este plan.</div>';
                    return;
                }

                // Ordenar: Pendientes primero, luego fecha
                const tasks = res.data.sort((a, b) => {
                    if (a.estado === 'Pendiente' && b.estado !== 'Pendiente') return -1;
                    if (a.estado !== 'Pendiente' && b.estado === 'Pendiente') return 1;
                    return new Date(a.fecha_programada) - new Date(b.fecha_programada);
                });

                let html = '';
                tasks.forEach(t => {
                    const isPending = t.estado === 'Pendiente';
                    const statusClass = isPending ? 'pending' : 'completed';
                    const icon = isPending ? '<i class="far fa-clock"></i>' : '<i class="fas fa-check-circle"></i>';
                    const date = t.fecha_programada ? t.fecha_programada.replace('T', ' ') : 'Sin fecha';
                    
                    // Botón de acción
                    let actionBtn = '';
                    if(isPending) {
                        actionBtn = `<button class="btn-complete" onclick="openCompleteModal(${t.id_tarea})">Completar</button>`;
                    } else {
                        actionBtn = `<span style="color:#10b981; font-weight:600; font-size:0.9em;">¡Completada!</span>`;
                    }

                    // Medicamento info
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
                // Fallback mensaje amigable
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
    </script>
</body>
</html>