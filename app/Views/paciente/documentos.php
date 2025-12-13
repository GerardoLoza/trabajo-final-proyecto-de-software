<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Documentos - HealthTracker</title>
    <link rel="stylesheet" href="<?= base_url('styles.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <aside class="sidebar">
        <h1>HealthTracker</h1>
        <nav>
            <button class="nav-btn" onclick="window.location.href='<?= base_url('paciente') ?>'">
                <i class="fas fa-home" style="margin-right: 8px;"></i> Mi Panel
            </button>
            <button class="nav-btn active">
                <i class="fas fa-file-medical" style="margin-right: 8px;"></i> Documentos
            </button>
            <button onclick="window.location.href='<?= base_url('logout') ?>'" class="nav-btn" style="margin-top: auto; background-color: #dc2626;">
                <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i> Cerrar Sesión
            </button>
        </nav>
    </aside>

    <main class="main-content">
        <div class="content-card">
            <div class="header-section">
                <h3>Mis Documentos</h3>
                <button class="btn-primary" onclick="document.getElementById('doc-modal').classList.add('active')">
                    <i class="fas fa-upload"></i> Subir
                </button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Plan</th>
                        <th>Subido</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($docs)): ?>
                        <?php foreach ($docs as $d): ?>
                            <tr>
                                <td><strong><?= esc($d->titulo) ?></strong></td>
                                <td><?= esc($d->tipo) ?></td>
                                <td><?= esc($d->id_plan ?? '—') ?></td>
                                <td><?= esc($d->created_at) ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="btn-view btn-icon" href="<?= base_url('paciente/documentos/' . $d->id) ?>" title="Descargar">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn-delete btn-icon" onclick="deleteRecord('documentos', <?= esc($d->id) ?>)" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">No tienes documentos cargados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal para subir documento -->
        <div id="doc-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Subir documento</h3>
                    <button class="close-btn" onclick="closeModal('doc-modal')">&times;</button>
                </div>
                <form action="<?= base_url('paciente/documentos') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Título</label>
                        <input type="text" name="titulo" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="tipo" required>
                            <option value="">Seleccionar</option>
                            <option value="receta">Receta</option>
                            <option value="estudio">Estudio</option>
                            <option value="informe">Informe</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Asociar a plan (opcional)</label>
                        <input type="number" name="id_plan" placeholder="ID plan o dejar vacío">
                    </div>
                    <div class="form-group">
                        <label>Archivo (PDF/JPG/PNG, máx 5MB)</label>
                        <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.heic" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeModal('doc-modal')">Cancelar</button>
                        <button type="submit" class="btn-save">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <script src="<?= base_url('script.js') ?>"></script>
</body>
</html>
