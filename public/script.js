/**
 * ARCHIVO DE SCRIPT PRINCIPAL DE LA APLICACIÓN
 * Contiene funciones de UI genéricas y reutilizables.
 */

// Configuración inicial de CSRF para Fetch
const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
let csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';
let currentPlanId = null;

function scrollToEntity(entity, navElement) {
    const el = document.getElementById(entity);
    if (el) el.scrollIntoView({ behavior: 'smooth' });
}

function filterTable(entity) {
    const input = event.target.value.toLowerCase();
    const table = document.querySelector(`#${entity}-table tbody`);
    if (!table) return;

    const rows = table.getElementsByTagName("tr");
    for (const row of rows) {
        if (row.classList.contains("empty-state-row")) continue;
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? "" : "none";
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove("active");
    }
}

/**
 * Abre el modal principal.
 */
function openModal(entity, mode, element = null) {
    const modal = document.getElementById("modal");
    if (!modal) return;

    const modalTitle = document.getElementById("modal-title");
    const form = document.getElementById("entity-form");
    
    // Limpiar inputs hidden previos de tareas (si existen) y la lista visual
    const inputsContainer = document.getElementById('plan-tasks-inputs');
    const listContainer = document.getElementById('plan-tasks-list');
    if (inputsContainer) inputsContainer.innerHTML = '';
    if (listContainer) listContainer.innerHTML = '';

    form.reset();
    
    // Reset manual de selects para que no se queden pegados
    const selects = form.querySelectorAll('select');
    selects.forEach(s => s.selectedIndex = 0);

    // Construir base URL usando meta base-url y rol del servidor si está disponible
    const baseMeta = document.querySelector('meta[name="base-url"]')?.content.replace(/\/$/, '') || '';
    const roleSegment = (window.serverData && window.serverData.role) ? String(window.serverData.role).toLowerCase() : (window.location.pathname.split('/')[1] || '');
    const baseUrl = baseMeta ? `${baseMeta}/${roleSegment}` : window.location.pathname.split('/').slice(0, 2).join('/');

    if (mode === 'create') {
        modalTitle.textContent = `Nuevo ${entity}`;
        form.action = `${baseUrl}/${entity}`; // POST normal
        
        // Resetear ID y Method spoofing
        const idInput = document.getElementById('form-id');
        if (idInput) idInput.value = '';
        document.getElementById('form-method').value = 'POST';

        modal.classList.add("active");

    } else if (mode === 'edit' && element) {
        const id = element.dataset.id;
        modalTitle.textContent = `Editar ${entity}`;
        form.action = `${baseUrl}/${entity}/${id}`; // La URL base
        document.getElementById('form-method').value = 'PUT'; // Method Spoofing para CI4

        // Rellenar datos
        const data = element.dataset;
        for (const key in data) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'date' && data[key]) {
                    input.value = data[key].split(' ')[0];
                } else if (input.type === 'datetime-local' && data[key]) {
                    input.value = data[key].replace(' ', 'T');
                } else {
                    input.value = data[key];
                }
            }
        }
        
        const idInput = document.getElementById('form-id');
        if (idInput) idInput.value = id;

        modal.classList.add("active");
    }
}

function deleteRecord(entity, id) {
    if (!confirm("¿Está seguro de eliminar este registro?")) return;

    // Construir base URL seguro para llamadas a recursos (no depender de la ruta actual)
    const baseMeta = document.querySelector('meta[name="base-url"]')?.content.replace(/\/$/, '') || '';
    const roleSegment = (window.serverData && window.serverData.role) ? String(window.serverData.role).toLowerCase() : (window.location.pathname.split('/')[1] || '');
    const baseUrl = baseMeta ? `${baseMeta}/${roleSegment}` : window.location.pathname.split('/').slice(0, 2).join('/');
    
    // Usamos Fetch para delete también, es más limpio
    fetch(`${baseUrl}/${entity}/${id}`, {
        method: 'POST', // CI4 resource delete suele ser via POST con _method=DELETE o DELETE directo si server lo soporta
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        body: '_method=DELETE'
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            alert('Eliminado correctamente');
            location.reload();
        } else {
            alert('Error al eliminar: ' + (data.message || 'Desconocido'));
        }
    })
    .catch(err => console.error(err));
}

/* ====== Submodal y gestión de tareas en cliente ====== */

function openTaskCreator() {
    const modal = document.getElementById('task-creator-modal');
    if (!modal) return;
    
    // Reset fields
    document.getElementById('task-descripcion').value = '';
    document.getElementById('task-fecha').value = '';
    document.getElementById('task-tipo').selectedIndex = 0;
    
    modal.classList.add('active');
}

function closeTaskCreator() {
    const modal = document.getElementById('task-creator-modal');
    if (modal) modal.classList.remove('active');
}

function addTaskToPlan() {
    const desc = document.getElementById('task-descripcion').value.trim();
    const fecha = document.getElementById('task-fecha').value;
    
    // Obtener datos del SELECT (ID y Texto)
    const tipoSelect = document.getElementById('task-tipo');
    const tipoId = tipoSelect.value; // El ID (para la BD)
    const tipoNombre = tipoSelect.options[tipoSelect.selectedIndex].text; // El Nombre (para mostrar)
    const medSelect = document.getElementById('task-medicamento-init');
    const medNombre = medSelect.value; // El value es el nombre del medicamento

    if (!desc) {
        alert('La descripción es obligatoria.');
        return;
    }
    if (!tipoId) {
        alert('Debes seleccionar un tipo de tarea.');
        return;
    }
    if (!fecha) {
        alert('La fecha es obligatoria.');
        return;
    }

    const key = 't' + Date.now(); // ID temporal único

    // 1. Añadir a la lista VISIBLE (Usuario ve nombres bonitos)
    const list = document.getElementById('plan-tasks-list');
    const li = document.createElement('li');
    li.dataset.taskKey = key;
    li.style.padding = "10px";
    li.style.borderBottom = "1px solid #eee";
    li.style.display = "flex";
    li.style.justifyContent = "space-between";
    li.style.alignItems = "center";
    const medInfo = medNombre ? `<br><small style="color:#000033;"> ${escapeHtml(medNombre)}</small>` : '';
    
    li.innerHTML = `
        <div>
            <strong>${escapeHtml(tipoNombre)}</strong>: ${escapeHtml(desc)} <br>
            <small style="color: #555;">📅 ${fecha.replace('T',' ')}</small>
        </div>
        <button type="button" onclick="removeTask('${key}')" class="btn-delete" style="padding: 4px 8px; font-size: 12px;">Quitar</button>
    `;
    list.appendChild(li);

    // 2. Añadir inputs HIDDEN (Servidor recibe IDs)
    const inputsContainer = document.getElementById('plan-tasks-inputs');
    
    createHiddenInput(inputsContainer, `tareas[${key}][descripcion]`, desc, key);
    createHiddenInput(inputsContainer, `tareas[${key}][fecha_programada]`, fecha, key);
    createHiddenInput(inputsContainer, `tareas[${key}][tipo]`, tipoId, key); // Aquí va el ID
    if(medNombre) {
        createHiddenInput(inputsContainer, `tareas[${key}][nombre_medicamento]`, medNombre, key);
    }

    closeTaskCreator();

    document.getElementById('task-descripcion').value = '';
    document.getElementById('task-fecha').value = '';
    document.getElementById('task-tipo').selectedIndex = 0;
    if(medSelect) medSelect.selectedIndex = 0;
}

function togglePlanStatus(planId) {
    if(!confirm("¿Deseas cambiar el estado del plan (Vigente <-> Finalizado)?")) return;

    const base = document.querySelector('meta[name="base-url"]').content || '';
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

    fetch(`${base}/profesional/planes/${planId}/estado`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token
        }
    })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            // Actualizar UI sin recargar
            const badge = document.getElementById(`badge-estado-${planId}`);
            if(badge) {
                badge.textContent = res.nuevo_estado;
                if(res.nuevo_estado === 'Vigente') {
                    badge.style.backgroundColor = '#d1fae5';
                    badge.style.color = '#065f46';
                } else {
                    badge.style.backgroundColor = '#e5e7eb';
                    badge.style.color = '#374151';
                }
            }
            alert(res.message);
        } else {
            alert('Error: ' + res.message);
        }
    })
    .catch(err => console.error(err));
}

function createHiddenInput(container, name, value, key) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    input.dataset.taskKey = key;
    container.appendChild(input);
}

function removeTask(key) {
    // Remove visual
    const list = document.getElementById('plan-tasks-list');
    const items = list.querySelectorAll('li');
    items.forEach(li => { if (li.dataset.taskKey === key) li.remove(); });

    // Remove data
    const inputsContainer = document.getElementById('plan-tasks-inputs');
    const hiddenInputs = inputsContainer.querySelectorAll(`input[data-task-key="${key}"]`);
    hiddenInputs.forEach(i => i.remove());
}

/* ====== Modal lista Tareas (Ajax) ====== */

/* ====== Reemplazar la función existente en public/script.js ====== */

function openTasksModal(planId) {
    currentPlanId = planId;
    
    // Resetear formulario de nueva tarea si existe
    const formDiv = document.getElementById('new-task-form');
    if(formDiv) formDiv.style.display = 'none';
    
    // Cargar tipos de tarea en el selector (si no están cargados)
    const typeSelect = document.getElementById('new-task-type');
    if (typeSelect && typeSelect.options.length <= 1 && window.serverData && window.serverData.tipos) {
        window.serverData.tipos.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id_tipo_tarea;
            opt.textContent = t.nombre;
            typeSelect.appendChild(opt);
        });
    }

    // Preparar URL y Meta Tags
    const baseMeta = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '');
    const url = `${baseMeta}/profesional/planes/${planId}/tareas`;

    const list = document.getElementById('tasks-list');
    if (!list) return alert('Error: Elemento tasks-list no encontrado en el DOM.');
    list.innerHTML = '<li style="padding:10px;color:#666;text-align:center;">Cargando tareas...</li>';

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(json => {
            if (!json || !json.success) {
                list.innerHTML = '<li style="padding:10px;color:#c00;">No se pudieron obtener las tareas.</li>';
                return;
            }

            list.innerHTML = '';
            
            // Mapa de tipos de tarea para mostrar nombre en vez de ID
            const tipos = (window.serverData && window.serverData.tipos) ? window.serverData.tipos : [];
            const tipoMap = {};
            tipos.forEach(t => { tipoMap[t.id_tipo_tarea] = t.nombre; });

            if (!json.data || json.data.length === 0) {
                list.innerHTML = '<li class="empty-state" style="padding:20px;color:#666;text-align:center;">No hay tareas registradas para este plan.</li>';
            } else {
                // Ordenar: primero por fecha
                json.data.sort((a, b) => new Date(a.fecha_programada) - new Date(b.fecha_programada));

                json.data.forEach(t => {
                    const li = document.createElement('li');
                    li.style.padding = '15px';
                    li.style.borderBottom = '1px solid #f1f5f9';
                    li.style.background = '#fff';
                    
                    // Helpers de visualización
                    const tipoNombre = tipoMap[t.id_tipo_tarea] || (t.id_tipo_tarea || 'Tarea');
                    // Formatear fecha (cortar segundos y reemplazar T)
                    const fecha = t.fecha_programada ? t.fecha_programada.substring(0,16).replace('T',' ') : 'Sin fecha';
                    const estadoIcon = t.estado === 'Completada' ? '<i class="fas fa-check-circle"></i>' : '<i class="far fa-circle"></i>';
                    const estadoStyle = t.estado === 'Completada' ? 'color:#10b981; font-weight:700;' : 'color:#f59e0b;';
                    
                    // --- LÓGICA NUEVA: BOTÓN DE EVIDENCIA ---
                    let btnEvidencia = '';
                    if (t.doc_id) {
                        const downloadUrl = `${baseMeta}/profesional/documentos/download/${t.doc_id}`;
                        // Usamos la clase btn-evidence que definimos en el CSS
                        btnEvidencia = `
                            <a href="${downloadUrl}" target="_blank" class="btn-evidence" title="Ver comprobante: ${escapeHtml(t.doc_titulo || 'Archivo')}">
                                <i class="fas fa-paperclip"></i> Ver Comprobante
                            </a>
                        `;
                    }
                    // ----------------------------------------

                    // Helper simple para escapar HTML (si no tienes uno global)
                    const safeDesc = escapeHtml(t.descripcion || '');
                    const safeComent = escapeHtml(t.comentarios_paciente || '');

                    li.innerHTML = `
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:15px;">
                          <div style="flex-grow:1;">
                            <div style="font-size:1.05em; margin-bottom:6px; color:#334155;">
                                <strong>${t.num_tarea || '#'}. ${escapeHtml(tipoNombre)}</strong>: ${safeDesc}
                            </div>
                            
                            <div style="font-size:0.85em; color:#64748b; margin-bottom:5px;">
                                <i class="far fa-calendar-alt"></i> ${escapeHtml(fecha)} &nbsp; | &nbsp; 
                                <span style="${estadoStyle}">${estadoIcon} ${escapeHtml(t.estado)}</span>
                            </div>

                            ${t.nombre_medicamento ? `<div style="font-size:0.85em; color:#4338ca; margin-top:2px;"><i class="fas fa-pills"></i> ${escapeHtml(t.nombre_medicamento)}</div>` : ''}
                            
                            ${t.comentarios_paciente ? `
                                <div style="margin-top:8px; background:#f8fafc; padding:8px 12px; border-left:3px solid #cbd5e1; border-radius:4px; font-size:0.85em; color:#475569; font-style:italic;">
                                    <i class="fas fa-comment-dots"></i> "${safeComent}"
                                </div>` : ''}
                          </div>

                          <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-end; min-width: 140px;">
                            ${btnEvidencia}
                            <button class="btn-delete" onclick="deleteTask(${t.id || t.id_tarea})" style="font-size:0.8em; padding:4px 10px; border:1px solid #fee2e2; background:#fef2f2; color:#ef4444; border-radius:6px; cursor:pointer;">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                          </div>
                        </div>
                    `;
                    list.appendChild(li);
                });
            }

            const modal = document.getElementById('tasks-modal');
            if (modal) modal.classList.add('active');
        })
        .catch(err => {
            console.error(err);
            list.innerHTML = '<li style="padding:10px;color:#c00;text-align:center;">Error de conexión.</li>';
        });
}

// Helper simple por si no lo tienes definido globalmente
function escapeHtml(text) {
  if (!text) return text;
  return text
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function toggleNewTaskForm() {
    const form = document.getElementById('new-task-form');
    if (form.style.display === 'none') {
        form.style.display = 'block';
        // Limpiar inputs
        document.getElementById('new-task-desc').value = '';
        document.getElementById('new-task-date').value = '';
        document.getElementById('new-task-type').selectedIndex = 0;
        const medSelect = document.getElementById('new-task-medicamento');
        if(medSelect) medSelect.selectedIndex = 0;
    } else {
        form.style.display = 'none';
    }
}

function saveNewTask(e) {
    e.preventDefault();
    
    if (!currentPlanId) return alert("Error: ID de plan perdido.");

    const tipo = document.getElementById('new-task-type').value;
    const desc = document.getElementById('new-task-desc').value;
    const fecha = document.getElementById('new-task-date').value;
    const medicamento = document.getElementById('new-task-medicamento').value;

    if(!tipo || !desc || !fecha) return alert("Todos los campos son obligatorios");

    const base = document.querySelector('meta[name="base-url"]').content || '';
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Preparamos los datos como FormData
    const formData = new FormData();
    formData.append('id_plan', currentPlanId);
    formData.append('id_tipo_tarea', tipo);
    formData.append('descripcion', desc);
    formData.append('fecha_programada', fecha);
    if (medicamento) {
        formData.append('nombre_medicamento', medicamento);
    }

    fetch(`${base}/profesional/tareas`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            // Ocultar formulario
            toggleNewTaskForm();
            // Recargar la lista de tareas
            openTasksModal(currentPlanId); 
        } else {
            alert('Error: ' + (data.message || JSON.stringify(data.errors)));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión al guardar tarea.');
    });
}

function closeTasksModal() {
    const modal = document.getElementById('tasks-modal');
    if (modal) modal.classList.remove('active');
}

function deleteTask(idTarea) {
    if (!confirm('¿Eliminar esta tarea?')) return;
    const base = document.querySelector('meta[name="base-url"]').content || '';
    const url = `${base}/profesional/tareas/${idTarea}`;
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token
        },
        body: '_method=DELETE'
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            alert('Tarea eliminada');
            // Refrescar lista
            const modal = document.getElementById('tasks-modal');
            if (modal && modal.classList.contains('active')) {
                // Recuperar planId from URL in header or re-open: easiest is to reload modal content via last opened plan id
                // For simplicity reload page
                location.reload();
            }
        } else {
            alert('Error al eliminar: ' + (data.message || JSON.stringify(data)));
        }
    })
    .catch(err => { console.error(err); alert('Error de comunicación.'); });
}

function openProgressModal(planId) {
    const base = document.querySelector('meta[name="base-url"]').content || '';
    const url = `${base}/profesional/planes/${planId}/tareas`;

    // Resetear vista del modal
    document.getElementById('progress-percent-text').textContent = '0%';
    document.getElementById('progress-bar-fill').style.width = '0%';
    const container = document.getElementById('progress-tasks-list');
    container.innerHTML = '<div style="text-align:center; padding:20px; color:#666;">Cargando...</div>';

    // Abrir modal
    const modal = document.getElementById('progress-modal');
    if (modal) modal.classList.add('active');

    // Fetch datos
    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(json => {
        if (!json.success) {
            container.innerHTML = '<div style="color:red; text-align:center;">Error al cargar datos.</div>';
            return;
        }

        const tasks = json.data || [];
        const total = tasks.length;
        
        if (total === 0) {
            container.innerHTML = '<div style="text-align:center; padding:20px; color:#666;">Este plan no tiene tareas asignadas aún.</div>';
            return;
        }

        // 1. Calcular Progreso
        const completedCount = tasks.filter(t => t.estado === 'Completada').length;
        const percent = Math.round((completedCount / total) * 100);

        // Actualizar barra (con un pequeño delay para que se vea la animación)
        setTimeout(() => {
            document.getElementById('progress-bar-fill').style.width = `${percent}%`;
            document.getElementById('progress-percent-text').textContent = `${percent}%`;
        }, 100);

        // 2. Renderizar Lista
        container.innerHTML = '';
        
        // Ordenamos para ver las completadas o pendientes primero? 
        // Generalmente es útil ver el orden cronológico (fecha_programada)
        tasks.sort((a, b) => new Date(a.fecha_programada) - new Date(b.fecha_programada));

        tasks.forEach(t => {
            const item = document.createElement('div');
            item.style.padding = '15px';
            item.style.marginBottom = '10px';
            item.style.backgroundColor = '#fff';
            item.style.border = '1px solid #eee';
            item.style.borderRadius = '8px';
            item.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';

            // Estilos según estado
            const isCompleted = t.estado === 'Completada';
            const badgeColor = isCompleted ? '#d1fae5' : '#fff7ed'; // Verde claro / Naranja claro
            const textColor = isCompleted ? '#065f46' : '#9a3412';  // Verde oscuro / Naranja oscuro
            const icon = isCompleted ? '✅' : '⏳';

            // HTML para el comentario (solo si existe)
            let commentHtml = '';
            if (t.comentarios_paciente && t.comentarios_paciente.trim() !== '') {
                commentHtml = `
                    <div style="margin-top: 10px; background-color: #f8f9fa; padding: 10px; border-left: 4px solid #000033; border-radius: 4px;">
                        <strong style="font-size: 0.85em; color: #555;">💬 Comentario del Paciente:</strong>
                        <p style="margin: 5px 0 0 0; font-style: italic; color: #333;">"${escapeHtml(t.comentarios_paciente)}"</p>
                    </div>
                `;
            }

            // Fecha formateada
            const fecha = t.fecha_programada ? t.fecha_programada.replace('T', ' ') : 'Sin fecha';

            item.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div style="flex: 1; padding-right: 10px;">
                        <div style="font-weight:bold; font-size:1.05em; color:#222;">${escapeHtml(t.descripcion)}</div>
                        <div style="font-size:0.85em; color:#666; margin-top:4px;">
                            📅 ${escapeHtml(fecha)} &nbsp;|&nbsp; Tarea #${t.num_tarea}
                        </div>
                    </div>
                    <span style="background-color:${badgeColor}; color:${textColor}; padding: 4px 10px; border-radius: 15px; font-size: 0.85em; font-weight: 600; white-space: nowrap;">
                        ${icon} ${escapeHtml(t.estado)}
                    </span>
                </div>
                ${commentHtml}
            `;
            container.appendChild(item);
        });

    })
    .catch(err => {
        console.error(err);
        container.innerHTML = '<div style="color:red; text-align:center;">Error de conexión.</div>';
    });
}


function escapeHtml(text) {
    if (!text) return text;
    return String(text).replace(/[&<>\"]+/g, function (s) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]);
    });
}

// Listener para el envío AJAX del formulario principal
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('entity-form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Stop envío normal

            const formData = new FormData(this);
            const url = this.action;
            
            // Obtener headers (especialmente CSRF si cambia)
            const currentCsrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": currentCsrf
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(text) });
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message || "Guardado correctamente");
                    closeModal('modal');
                    location.reload();
                } else {
                    let errorMsg = "Errores:\n";
                    if (data.errors) {
                        for (const [field, msg] of Object.entries(data.errors)) {
                            errorMsg += `- ${msg}\n`;
                        }
                    } else {
                        errorMsg += data.message || 'Error desconocido';
                    }
                    alert(errorMsg);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Error de comunicación con el servidor. Ver consola.");
            });
        });
    }
});

// Exponer funciones globales
window.openModal = openModal;
window.closeModal = closeModal;
window.deleteRecord = deleteRecord;
window.filterTable = filterTable;
window.openTaskCreator = openTaskCreator;
window.closeTaskCreator = closeTaskCreator;
window.addTaskToPlan = addTaskToPlan;
window.removeTask = removeTask;
window.openTasksModal = openTasksModal;
window.closeTasksModal = closeTasksModal;
window.deleteTask = deleteTask;
window.toggleNewTaskForm = toggleNewTaskForm;
window.saveNewTask = saveNewTask;
window.openProgressModal = openProgressModal;
window.markTaskComplete = markTaskComplete;
window.togglePlanStatus = togglePlanStatus;