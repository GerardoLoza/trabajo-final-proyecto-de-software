<!DOCTYPE html>
<html>
<head>
    <title>Lista de Usuarios</title>
    <!-- Incluir el CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
    <div class="usuario-lista">
        <h1>Usuarios Activos</h1>
        
        <?php foreach ($usuarios as $usuario): ?>
            <div class="usuario-item">
                <h3 class="usuario-nombre"><?= esc($usuario['nombre']) ?></h3>
                <p class="usuario-email"><?= esc($usuario['email']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>