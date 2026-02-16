<?php
/**
 * ComponentRegistry - Registro unificado de indicadores y balances
 * Carga componentes basados en permisos del cargo usando tienePermiso()
 */

namespace Core\Components;

// Autoloader para clases de componentes
// Autoloader para clases de componentes
spl_autoload_register(function ($class) {
    // Solo procesar clases de Core\Components
    $prefix = 'Core\\Components\\';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    // Remover el prefijo
    $relative_class = substr($class, strlen($prefix));

    // Convertir a ruta usando separadores de directorio del sistema
    $path_parts = explode('\\', $relative_class);

    // 1. Manejar BaseIndicator y BaseBalance
    if (count($path_parts) === 2 && $path_parts[1] === 'BaseIndicator' && $path_parts[0] === 'Indicators') {
        $file = __DIR__ . '/indicators/base/BaseIndicator.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    if (count($path_parts) === 2 && $path_parts[1] === 'BaseBalance' && $path_parts[0] === 'Balances') {
        $file = __DIR__ . '/balances/base/BaseBalance.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 2. Mapeo Modular para Indicadores: Indicators\List\TardanzasPendientesIndicator
    if (count($path_parts) === 3 && $path_parts[0] === 'Indicators' && $path_parts[1] === 'List') {
        $className = $path_parts[2];
        $folderName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Indicator', '', $className)));
        $file = __DIR__ . '/indicators/' . $folderName . '/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 3. Mapeo Modular para Balances: Balances\List\VentasVsMetaBalance
    if (count($path_parts) === 3 && $path_parts[0] === 'Balances' && $path_parts[1] === 'List') {
        $className = $path_parts[2];
        $folderName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Balance', '', $className)));
        $file = __DIR__ . '/balances/' . $folderName . '/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 4. Fallback para compatibilidad con subcarpetas antiguas o estructuras simples
    $relative_path = str_replace('\\', '/', $relative_class);
    $relative_path = str_replace(['Indicators/', 'Balances/'], ['indicators/', 'balances/'], $relative_path);
    $relative_path = str_replace(['list/', 'List/'], ['', ''], $relative_path); // Intentar sin /list/

    $file = __DIR__ . '/' . $relative_path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

class ComponentRegistry
{
    private $conn;
    private $indicators = [];
    private $balances = [];
    private $quickAccess = [];

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Cargar indicadores para un cargo específico usando el sistema de permisos
     */
    public function getIndicatorsForCargo($userId, $codNivelesCargos)
    {
        error_log("DEBUG ComponentRegistry: Cargando indicadores para usuario $userId, cargo $codNivelesCargos");

        // Obtener todos los indicadores activos
        $stmt = $this->conn->prepare("
            SELECT * FROM tools_erp
            WHERE tipo_componente = 'indicador'
            AND activo = 1
            ORDER BY orden ASC
        ");
        $stmt->execute();

        $allIndicators = $stmt->fetchAll();
        error_log("DEBUG ComponentRegistry: Encontrados " . count($allIndicators) . " indicadores en BD");

        $indicators = [];
        foreach ($allIndicators as $row) {
            error_log("DEBUG ComponentRegistry: Procesando indicador '{$row['nombre']}'");

            // Verificar permiso de vista usando tienePermiso()
            $tienePermiso = tienePermiso($row['nombre'], 'vista', $codNivelesCargos);
            error_log("DEBUG ComponentRegistry: tienePermiso('{$row['nombre']}', 'vista', $codNivelesCargos) = " . ($tienePermiso ? 'true' : 'false'));

            if (!$tienePermiso) {
                error_log("DEBUG ComponentRegistry: Sin permiso para '{$row['nombre']}', saltando");
                continue; // Saltar si no tiene permiso
            }

            $className = "Core\\Components\\Indicators\\List\\" . $row['class_name'];
            error_log("DEBUG ComponentRegistry: Intentando cargar clase: $className");

            // Intentar cargar la clase
            if (!class_exists($className)) {
                error_log("ERROR ComponentRegistry: Clase no encontrada: {$className} para indicador {$row['nombre']}");
                continue;
            }

            try {
                $indicator = new $className($this->conn, $row['config_json']);
                $indicatorData = $indicator->render($userId);

                // Agregar metadata del registro
                $indicatorData['id'] = $row['id'];
                $indicatorData['grupo'] = $row['grupo'];
                $indicatorData['nombre_herramienta'] = $row['nombre'];
                $indicatorData['config'] = json_decode($row['config_json'], true);
                $indicatorData['codigo'] = $row['class_name']; // Usamos class_name como código

                // Determinar carpeta modular
                $folderName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Indicator', '', $row['class_name'])));

                // Assets base
                $assets = [
                    'css' => "core/components/indicators/{$folderName}/{$folderName}.css",
                    'js' => "core/components/indicators/{$folderName}/{$folderName}.js"
                ];

                // Agregar assets de modal si existen
                $modalCssPath = __DIR__ . "/indicators/{$folderName}/{$folderName}_modal.css";
                $modalJsPath = __DIR__ . "/indicators/{$folderName}/{$folderName}_modal.js";

                if (file_exists($modalCssPath)) {
                    $assets['modal_css'] = "core/components/indicators/{$folderName}/{$folderName}_modal.css";
                }
                if (file_exists($modalJsPath)) {
                    $assets['modal_js'] = "core/components/indicators/{$folderName}/{$folderName}_modal.js";
                }

                $indicatorData['assets'] = $assets;

                $indicators[] = $indicatorData;
                error_log("DEBUG ComponentRegistry: Indicador '{$row['nombre']}' cargado exitosamente");
            } catch (\Exception $e) {
                error_log("ERROR ComponentRegistry: Error cargando indicador {$row['nombre']}: " . $e->getMessage());
                error_log("ERROR ComponentRegistry: Stack trace: " . $e->getTraceAsString());
            }
        }

        error_log("DEBUG ComponentRegistry: Total indicadores cargados: " . count($indicators));
        return $indicators;
    }

    /**
     * Cargar balances para un cargo específico
     */
    public function getBalancesForCargo($userId, $codNivelesCargos)
    {
        // Obtener todos los balances activos
        $stmt = $this->conn->prepare("
            SELECT * FROM tools_erp
            WHERE tipo_componente = 'balance'
            AND activo = 1
            ORDER BY orden ASC
        ");
        $stmt->execute();

        $balances = [];
        while ($row = $stmt->fetch()) {
            // Verificar permiso de vista
            if (!tienePermiso($row['nombre'], 'vista', $codNivelesCargos)) {
                continue;
            }

            $className = "Core\\Components\\Balances\\List\\" . $row['class_name'];
            error_log("DEBUG ComponentRegistry: Intentando cargar balance '{$row['nombre']}' con clase $className");

            if (class_exists($className)) {
                error_log("DEBUG ComponentRegistry: ✓ Clase $className encontrada");
                try {
                    $balance = new $className($this->conn, $row['config_json']);
                    $balanceData = $balance->render($userId);

                    // Agregar metadata
                    $balanceData['id'] = $row['id'];
                    $balanceData['grupo'] = $row['grupo'];
                    $balanceData['nombre_herramienta'] = $row['nombre'];
                    $balanceData['codigo'] = $row['class_name'];

                    // Determinar carpeta modular
                    $folderName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Balance', '', $row['class_name'])));
                    $balanceData['assets'] = [
                        'css' => "core/components/balances/{$folderName}/{$folderName}.css",
                        'js' => "core/components/balances/{$folderName}/{$folderName}.js"
                    ];
                    $balanceData['container_id'] = $folderName . '_balance';
                    $balanceData['ajax_url'] = "../../core/components/balances/{$folderName}/get_{$folderName}_data.php"; // Estándar propuesto

                    $balances[] = $balanceData;
                } catch (\Exception $e) {
                    error_log("Error cargando balance {$row['nombre']}: " . $e->getMessage());
                }
            }
        }

        return $balances;
    }

    /**
     * Cargar accesos rápidos para un cargo (herramientas marcadas)
     */
    public function getQuickAccessForCargo($codNivelesCargos)
    {
        // Obtener todas las herramientas activas
        $stmt = $this->conn->prepare("
            SELECT * FROM tools_erp
            WHERE tipo_componente = 'herramienta'
            AND activo = 1
            ORDER BY orden ASC
        ");
        $stmt->execute();

        $quickAccess = [];
        while ($row = $stmt->fetch()) {
            // Verificar si tiene permiso de vista
            if (tienePermiso($row['nombre'], 'vista', $codNivelesCargos)) {
                $quickAccess[] = $row;
            }
        }

        return $quickAccess;
    }

    /**
     * Cargar accesos directos (shortcuts) para un cargo específico
     * Retorna herramientas que tienen permiso de acción 'shortcut'
     */
    public function getShortcutsForCargo($codNivelesCargos)
    {
        // Obtener herramientas con permiso de shortcut
        $stmt = $this->conn->prepare("
            SELECT t.* 
            FROM tools_erp t
            INNER JOIN acciones_tools_erp a ON t.id = a.tool_erp_id
            INNER JOIN permisos_tools_erp p ON a.id = p.accion_tool_erp_id
            WHERE a.nombre_accion = 'shortcut'
            AND p.CodNivelesCargos = ?
            AND p.permiso = 'allow'
            AND t.activo = 1
            ORDER BY t.orden ASC, t.nombre ASC
        ");
        $stmt->execute([$codNivelesCargos]);

        $shortcuts = [];
        while ($row = $stmt->fetch()) {
            $shortcuts[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'descripcion' => $row['descripcion'],
                'icono' => $row['icono'] ?? 'fa-link',
                'url' => $row['url_real'] ?? '#',
                'grupo' => $row['grupo'],
                'tipo' => 'shortcut'
            ];
        }

        return $shortcuts;
    }

    /**
     * Obtener un indicador específico por código
     */
    public function getIndicator($codigo, $userId)
    {
        // El código puede venir como 'tardanzas_pendientes' o 'TardanzasPendientesIndicator'
        // Si viene como snake_case, intentar convertirlo a StudlyCase para class_name
        $classNameSearch = $codigo;
        if (strpos($codigo, '_') !== false) {
            $classNameSearch = str_replace('_', '', ucwords($codigo, '_'));
            // Asegurar que termine en Indicator si no lo tiene
            if (strpos($classNameSearch, 'Indicator') === false) {
                $classNameSearch .= 'Indicator';
            }
        }

        $stmt = $this->conn->prepare("
            SELECT * FROM tools_erp 
            WHERE (nombre = ? OR class_name = ? OR class_name = ?) 
            AND tipo_componente = 'indicador'
            AND activo = 1
            LIMIT 1
        ");
        $stmt->execute([$codigo, $codigo, $classNameSearch]);
        $row = $stmt->fetch();

        if (!$row)
            return null;

        $className = "Core\\Components\\Indicators\\List\\" . $row['class_name'];

        if (class_exists($className)) {
            $indicator = new $className($this->conn, $row['config_json']);
            return $indicator;
        }

        return null;
    }

    /**
     * Obtener datos del modal de un indicador específico
     */
    public function getIndicatorModalData($codigo, $userId, $params = [])
    {
        $indicator = $this->getIndicator($codigo, $userId);

        if (!$indicator) {
            return ['error' => 'Indicador no encontrado'];
        }

        return $indicator->getModalData($userId, $params);
    }
}
