<?php
/**
 * POS Header Component
 * Modular layout based on ERP design
 */

function renderPOSHeader($titulo = '') {
    $colaboradorNombre = $_SESSION['pos_colaborador_nombre'] ?? 'Operador';
    $sucursalNombre = $_SESSION['pos_store_name'] ?? 'Sucursal';
    $colaboradorId = $_SESSION['pos_colaborador_id'] ?? null;
    $cargoNombre = $_SESSION['pos_colaborador_cargo'] ?? 'Colaborador';
    
    // Intentar obtener datos extra si no están en sesión
    if ($colaboradorId && !isset($_SESSION['pos_usuario_completo'])) {
        $usuario = obtenerUsuarioActual();
        $_SESSION['pos_usuario_completo'] = $usuario;
    } else {
        $usuario = $_SESSION['pos_usuario_completo'] ?? null;
    }

    $fotoUrl = '';
    if ($usuario && !empty($usuario['foto_perfil'])) {
        // Las fotos están en el dominio del ERP.
        // Si el campo ya trae la ruta completa desde la raíz (ej: uploads/fotos_perfil/489/img.png)
        $rutaLimpia = ltrim($usuario['foto_perfil'], './');
        $fotoUrl = "https://erp.batidospitaya.com/" . $rutaLimpia;
    }

    ob_start();
    ?>
    <style>
        .pos-main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 24px;
            background: #FFFFFF;
            border-bottom: 2px solid #E2E8F0;
            position: sticky;
            top: 0;
            z-index: 900;
            margin-bottom: 25px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .pos-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .pos-header-title {
            color: #0E544C;
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0;
        }

        .pos-header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .pos-user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-right: 15px;
            border-right: 1px solid #E2E8F0;
        }

        .pos-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #51B8AC;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(81, 184, 172, 0.2);
        }

        .pos-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pos-user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .pos-user-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1E293B;
        }

        .pos-user-branch {
            font-size: 0.75rem;
            color: #64748B;
        }

        .pos-logout-btn {
            color: #64748B;
            font-size: 1.1rem;
            transition: all 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 8px;
        }

        .pos-logout-btn:hover {
            color: #E11D48;
            background: #FFF1F2;
        }
    </style>

    <header class="pos-main-header">
        <div class="pos-header-left">
            <h1 class="pos-header-title"><?= htmlspecialchars($titulo ?: 'Panel de Control') ?></h1>
        </div>
        
        <div class="pos-header-right">
            <div class="pos-user-profile">
                <div class="pos-user-info">
                    <span class="pos-user-name"><?= htmlspecialchars($colaboradorNombre) ?></span>
                    <span class="pos-user-branch"><?= htmlspecialchars($sucursalNombre) ?></span>
                </div>
                <div class="pos-avatar">
                    <?php if ($fotoUrl): ?>
                        <img src="<?= $fotoUrl ?>" alt="Avatar" onerror="this.parentElement.innerHTML='<?= strtoupper(substr($colaboradorNombre, 0, 1)) ?>'">
                    <?php else: ?>
                        <?= strtoupper(substr($colaboradorNombre, 0, 1)) ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="/logout.php?type=colaborador" class="pos-logout-btn" title="Cerrar Sesión">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </header>
    <?php
    return ob_get_clean();
}
