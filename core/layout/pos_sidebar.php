<?php

/**
 * POS Sidebar Component
 * Modular layout with hover expansion and accordion categories
 */

function renderPOSSidebar($activeModule = 'inicio')
{
    $menu = [
        [
            'id' => 'inicio',
            'titulo' => 'Inicio',
            'icon' => 'fas fa-home',
            'url' => '/index.php',
            'sub' => []
        ],
        [
            'id' => 'ventas',
            'titulo' => 'Ventas',
            'icon' => 'fas fa-cash-register',
            'url' => '#',
            'sub' => [
                ['titulo' => 'Nueva Venta', 'url' => '/modulos/facturacion/'],
                ['titulo' => 'Historial Ventas', 'url' => '/modulos/facturacion/historial.php'],
            ]
        ],
        [
            'id' => 'caja',
            'titulo' => 'Caja',
            'icon' => 'fas fa-vault',
            'url' => '#',
            'sub' => [
                ['titulo' => 'Caja Inicial', 'url' => '/modulos/inicial/'],
                ['titulo' => 'Corte de Caja', 'url' => '/modulos/caja/corte.php'],
            ]
        ],
        [
            'id' => 'inventario',
            'titulo' => 'Inventario',
            'icon' => 'fas fa-boxes-stacked',
            'url' => '#',
            'sub' => [
                ['titulo' => 'Productos', 'url' => '/modulos/productos/'],
                ['titulo' => 'Ajustes', 'url' => '/modulos/inventario/'],
                ['titulo' => 'Promociones', 'url' => '/modulos/promociones/'],
            ]
        ],
        [
            'id' => 'clientes',
            'titulo' => 'Clientes',
            'icon' => 'fas fa-users',
            'url' => '/modulos/clientes/',
            'sub' => []
        ],
        [
            'id' => 'config',
            'titulo' => 'Sistemas',
            'icon' => 'fas fa-cog',
            'url' => '/modulos/sistemas/',
            'sub' => []
        ]
    ];

    ob_start();
?>
    <style>
        :root {
            --sidebar-width: 70px;
            --sidebar-expanded: 260px;
            --pos-teal: #51B8AC;
            --pos-dark: #0E544C;
        }

        .pos-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: #FFFFFF;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .pos-sidebar:hover {
            width: var(--sidebar-expanded);
        }

        .pos-sidebar-header {
            height: 70px;
            display: flex;
            align-items: center;
            padding: 0 15px;
            border-bottom: 1px solid #F1F5F9;
            overflow: hidden;
            flex-shrink: 0;
        }

        .pos-sidebar-logo {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pos-sidebar-logo img {
            width: 32px;
            height: auto;
        }

        .pos-sidebar-brand {
            margin-left: 15px;
            font-weight: 800;
            color: var(--pos-dark);
            font-size: 1.1rem;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .pos-sidebar:hover .pos-sidebar-brand {
            opacity: 1;
        }

        .pos-sidebar-menu {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 15px 0;
        }

        /* Ocultar scrollbar */
        .pos-sidebar-menu::-webkit-scrollbar {
            width: 0px;
        }

        .pos-menu-item {
            position: relative;
        }

        .pos-menu-link {
            display: flex;
            align-items: center;
            height: 50px;
            color: #64748B;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border-left: 4px solid transparent;
        }

        .pos-menu-link:hover,
        .pos-menu-item.active>.pos-menu-link {
            color: var(--pos-teal);
            background: #F8FAFC;
        }

        .pos-menu-item.active>.pos-menu-link {
            border-left-color: var(--pos-teal);
            background: #F0FDFA;
            color: var(--pos-dark);
        }

        .pos-menu-icon {
            min-width: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .pos-menu-text {
            white-space: nowrap;
            font-weight: 600;
            font-size: 0.95rem;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .pos-sidebar:hover .pos-menu-text {
            opacity: 1;
        }

        .pos-menu-chevron {
            margin-left: auto;
            margin-right: 20px;
            font-size: 0.8rem;
            transition: transform 0.3s;
            opacity: 0;
        }

        .pos-sidebar:hover .pos-menu-chevron {
            opacity: 1;
        }

        .pos-menu-item.open>.pos-menu-link>.pos-menu-chevron {
            transform: rotate(90deg);
        }

        .pos-submenu {
            max-height: 0;
            overflow: hidden;
            background: #F8FAFC;
            transition: max-height 0.3s ease-out;
        }

        .pos-menu-item.open .pos-submenu {
            max-height: 500px;
        }

        .pos-submenu-link {
            display: flex;
            align-items: center;
            padding: 10px 20px 10px 70px;
            color: #64748B;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .pos-submenu-link:hover {
            color: var(--pos-teal);
            padding-left: 75px;
        }

        .pos-sidebar-footer {
            padding: 15px;
            border-top: 1px solid #F1F5F9;
            flex-shrink: 0;
            text-align: center;
        }

        .pos-version {
            font-size: 0.65rem;
            color: #94A3B8;
            white-space: nowrap;
            opacity: 0;
        }

        .pos-sidebar:hover .pos-version {
            opacity: 1;
        }
    </style>

    <div class="pos-sidebar" id="posSidebar">
        <div class="pos-sidebar-header">
            <div class="pos-sidebar-logo">
                <img src="/core/assets/img/icon12.png" alt="Logo">
            </div>
            <span class="pos-sidebar-brand">POS Pitaya</span>
        </div>

        <nav class="pos-sidebar-menu">
            <?php foreach ($menu as $item):
                $hasSub = !empty($item['sub']);
                $isActive = ($activeModule === $item['id']);
            ?>
                <div class="pos-menu-item <?= $isActive ? 'active' : '' ?> <?= $hasSub ? 'has-sub' : '' ?>" id="menu-<?= $item['id'] ?>">
                    <a href="<?= $hasSub ? 'javascript:void(0)' : $item['url'] ?>"
                        class="pos-menu-link"
                        onclick="<?= $hasSub ? "toggleSubmenu('menu-" . $item['id'] . "')" : "" ?>">
                        <div class="pos-menu-icon"><i class="<?= $item['icon'] ?>"></i></div>
                        <span class="pos-menu-text"><?= $item['titulo'] ?></span>
                        <?php if ($hasSub): ?>
                            <i class="fas fa-chevron-right pos-menu-chevron"></i>
                        <?php endif; ?>
                    </a>
                    <?php if ($hasSub): ?>
                        <div class="pos-submenu">
                            <?php foreach ($item['sub'] as $sub): ?>
                                <a href="<?= $sub['url'] ?>" class="pos-submenu-link"><?= $sub['titulo'] ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="pos-sidebar-footer">
            <span class="pos-version">v2.1.0 &mdash; POS</span>
        </div>
    </div>

    <script>
        function toggleSubmenu(id) {
            const sidebar = document.getElementById('posSidebar');
            const item = document.getElementById(id);

            // Si el sidebar no está expandido, no hacer nada o expandirlo primero?
            // El usuario pidió tipo ERP. En ERP al clickear categoría se expande.

            const isOpen = item.classList.contains('open');

            // Cerrar otros
            document.querySelectorAll('.pos-menu-item.has-sub').forEach(el => {
                if (el.id !== id) el.classList.remove('open');
            });

            if (isOpen) {
                item.classList.remove('open');
            } else {
                item.classList.add('open');
            }
        }
    </script>
<?php
    return ob_get_clean();
}
