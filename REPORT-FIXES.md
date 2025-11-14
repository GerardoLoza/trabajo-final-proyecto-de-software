REPORT: Refactor y correcciones - Migración a controladores por entidad

Resumen ejecutivo
-----------------
Se aplicó un conjunto de correcciones y refactorizaciones para mejorar la separación de responsabilidades, seguridad y mantenibilidad del proyecto. El objetivo principal fue garantizar que las vistas muestren datos reales desde la base de datos, eliminar dependencias de mocks en frontend, y preparar la aplicación para una migración desde controladores orientados por rol hacia controladores "expertos por entidad" (por ejemplo: `PlanController`, `UsuarioController`, `TareaController`).

Cambios realizados
------------------
- Backend (PHP / CodeIgniter 4):
  - `ProfesionalController`: corregido el cálculo de estadísticas (p. ej. conteo de pacientes) usando comparaciones case-insensitive y obteniendo datos reales desde `UsuarioModel`.
  - `PlanController`: añadido `gestionPlanes()` / `index()` y stubs CRUD (`create`, `store`, `show`, `edit`, `update`, `delete`) con notas sobre validación, transacciones y autorización.
  - `PlanModel`: confirmado `returnType = 'object'` y añadidos métodos auxiliares (`getPlanCompleto`, `getTareasDelPlan`, `getPlanesActivos`, `getPlanesPorProfesional`, `getPlanesPorPaciente`).
  - `app/Config/Routes.php`: añadidos alias de compatibilidad dentro del grupo `profesional` para mantener funcionando URIs antiguas durante la migración:
    - `GET /profesional/planes` -> `PlanController::index`
    - `POST /profesional/planes/crear` -> `PlanController::store`
    - `POST /profesional/planes/eliminar/{id}` -> `PlanController::delete/$1`
    - `GET /profesional/tareas/por_plan/{id}` -> `TareaController::porPlan/$1`
  - `TareaController.php`: detectado que faltan métodos; mantuvimos el alias pero recomendamos implementar `porPlan()` y otros endpoints mínimos.

- Frontend (JavaScript / Vistas):
  - Reemplazado el `public/script.js` que tenía datos mock y lógica que sobreescribía contenido server-rendered.
  - Refactor a módulos ES (en `public/js/`):
    - `api.js`: wrapper `fetchJson()` con manejo de CSRF y `base-url` desde meta tags.
    - `ui.js`: helpers DOM y renderización segura (uso de `textContent`, `esc()` en servidor cuando corresponde).
    - `modals.js`: gestión de modales y formularios.
    - `main.js`: entrypoint que mejora tablas server-side, añade `data-id` a filas y expone shims compatibles con handlers inline existentes.
  - Vistas actualizadas (`planes_view.php`, `dashboard_profesional.php`, `admin_view.php`, etc.) para:
    - Usar `<?= esc($variable ?? '') ?>` correctamente.
    - Acceder a objetos cuando `Model::$returnType = 'object'` (p. ej. `<?= esc($plan->id) ?>`).
    - Incluir `<meta name="base-url">` y `<meta name="csrf-token">` para facilitar fetch desde módulos.
    - Cargar `main.js` con `type="module"`.

Motivación y ventajas
---------------------
- Separación de responsabilidades: los controladores son "expertos" en su entidad, lo que facilita pruebas, mantenimiento y comprensión.
- Evitar diferencias entre datos server-rendered y frontend: eliminar mocks en producción evita condiciones de carrera y datos inconsistentes.
- Seguridad mejorada: uso de CSRF token para llamadas POST/DELETE desde `fetch`, y separación clara de rutas con filtros.
- Compatibilidad: aliases de rutas temporales permiten migración incremental sin romper el frontend existente.

Pasos recomendados por entidad (organizados por rol)
---------------------------------------------------
Nota: las siguientes tareas se agrupan por rol para que cada equipo de historia de usuario pueda trabajar sobre su área.

- Equipo: Profesional (interfaces que usan profesionales) - Tomi
  - Implementar en `TareaController` los endpoints necesarios:
    - `porPlan($id)`: devuelve JSON con las tareas del plan (si la UI necesita JSON).
    - `marcarCumplimiento($id)` / `validarCumplimiento` (POST): actualizar estado de tarea.
  - Revisar `PlanController::delete` y `store` para asegurar autorización (solo el profesional dueño o admin puede borrar/crear).
  - Ajustar vistas de `dashboard_profesional.php` y `planes_view.php` para llamar a los nuevos endpoints o usar render server-side; preferir render server-side cuando la operación no requiere refresh inmediato.

- Equipo: Paciente - Cristian
  - Asegurarse de que `UsuarioController` tenga métodos para:
    - `misPlanes()` que muestre solo los planes asignados al paciente.
    - endpoint para solicitar cambios o completar tareas (si aplica).
  - Revisar permisos: filtrar por `session()->get('id_usuario')` para evitar fugas de datos.

- Equipo: Admin - Gera
  - Revisar y migrar rutas en `Routes.php` de `AdminController` y `UsuarioController` para usar controladores por entidad cuando aplique.
  - Implementar endpoints administrativos para listar/editar usuarios y diagnosticos con paginación y filtros.
  - Añadir auditoría mínima (logs) para acciones críticas (borrado de planes, creación de usuarios).

- Equipo: Backend / Integración (cross-role) 
  - Completar métodos CRUD en `PlanController` con validación y transacciones:
    - Validar campos requeridos (tipos, longitudes), usar `Validation` de CodeIgniter.
    - En `delete($id)`, usar transacción si hay datos relacionados (tareas, asignaciones), marcar como borrado lógico si necesario.
  - Implementar `TareaController::porPlan($id)` y asegurar respuesta JSON consistente (`{ success: true, data: [...] }`).
  - Auditar y actualizar `app/Config/Filters.php` y `app/Config/Routes.php` para asegurar que filtros de rol siguen aplicando correctamente.

Consideraciones de seguridad y calidad
-------------------------------------
- Siempre escapar datos impresos en vistas con `esc()`.
- Usar CSRF tokens en llamadas mutativas: los módulos JS leen `<meta name="csrf-token">` añadida a las vistas.
- Sanitizar entradas en controladores y validar antes de persistir.
- No exponer información sensible en endpoints JSON (e.g., hashes, tokens).
- Añadir pruebas unitarias y de integración para:
  - Controladores principales (Plan/Tarea/Usuario).
  - Modelos: métodos de consulta más complejos (joins, paginación).

Checklist previo a merge
------------------------
- [ ] Implementar endpoints en `TareaController` y completar `PlanController` CRUD.
- [ ] Ejecutar pruebas unitarias y linting PHP (`php -l`) en archivos modificados.
- [ ] Probar en entorno local: abrir `dashboard_profesional`, `planes_view`, realizar crear/borrar/editar.
- [ ] Revisar Network tab: asegurar que todas las llamadas `fetch` responden 200/201 y CSRF funciona.
- [ ] Actualizar documentación de rutas y API si se exponen endpoints JSON.

Archivos modificados o añadidos (principales)
---------------------------------------------
- Modificados:
  - `app/Controllers/ProfesionalController.php`
  - `app/Controllers/PlanController.php` (stubs -> CRUD)
  - `app/Models/PlanModel.php`
  - `app/Views/planes_view.php`
  - `app/Views/dashboard_profesional.php`
  - `app/Config/Routes.php` (alias de compatibilidad)
- Nuevos:
  - `public/js/api.js`
  - `public/js/ui.js`
  - `public/js/modals.js`
  - `public/js/main.js`
  - `REPORT-FIXES.md` (documentación generada)

Siguientes pasos sugeridos (rápidos)
-----------------------------------
1. Implementar `TareaController::porPlan` y `PlanController::delete` (métodos mínimos JSON).
2. Actualizar `public/js/main.js` para consumir las URIs definitivas (si planean cambiar el prefijo `/profesional/`).
3. Ejecutar pruebas manuales y actualizar `REPORT-FIXES.md` con hallazgos post-QA.

Contacto
--------
Si lo desean, puedo:
- Implementar los endpoints JSON mínimos (por ejemplo `porPlan`, `delete`) y adjuntar ejemplos de llamadas `fetch`.
- Ejecutar una búsqueda en el repo y actualizar el TODO de auditoría de rutas (listar todas las referencias por rol).

---
Generado automáticamente por el asistente. Si quieres, genero también un PR con estos cambios o ejecuto la auditoría de referencias por rol ahora.