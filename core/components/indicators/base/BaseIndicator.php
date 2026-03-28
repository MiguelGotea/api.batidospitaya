<?php
/**
 * Clase base para todos los indicadores del sistema
 * Soporta configuración JSON para modales, URLs dinámicas y refresh automático
 */

namespace Core\Components\Indicators;

abstract class BaseIndicator
{
    protected $conn;
    protected $codigo;
    protected $nombre;
    protected $icono;
    protected $config; // Configuración JSON del indicador

    public function __construct($conn, $config = null)
    {
        $this->conn = $conn;
        $this->config = $config ? json_decode($config, true) : [];
    }

    /**
     * Método principal que debe implementar cada indicador
     * @param int $userId - ID del usuario actual
     * @return array - Datos del indicador
     */
    abstract public function getData($userId);

    /**
     * Renderizar el indicador con toda su configuración
     */
    public function render($userId)
    {
        $data = $this->getData($userId);

        $result = [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre ?? $data['nombre'],
            'icono' => $this->icono ?? $data['icono'],
            'valor' => $data['valor'],          // Número/texto principal
            'color' => $data['color'],          // verde, amarillo, rojo, gris
            'fecha_limite' => $data['fecha_limite'] ?? null,
            'url' => $this->buildUrl($data),
            'detalles' => $data['detalles'] ?? [],
            'config' => $this->getClientConfig()
        ];

        return $result;
    }

    /**
     * Construir URL con parámetros dinámicos
     */
    protected function buildUrl($data)
    {
        if (!isset($this->config['url'])) {
            return $data['url'] ?? '#';
        }

        $url = $this->config['url']['base'] ?? '#';

        if (isset($this->config['url']['params'])) {
            $params = [];
            foreach ($this->config['url']['params'] as $key => $value) {
                if (strpos($value, 'fixed:') === 0) {
                    // Valor fijo: "fixed:operaciones"
                    $params[$key] = substr($value, 6);
                } elseif ($value === 'dynamic' && isset($data[$key])) {
                    // Valor dinámico desde $data
                    $params[$key] = $data[$key];
                }
            }

            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        }

        return $url;
    }

    /**
     * Obtener configuración del cliente (modal, refresh, etc.)
     */
    protected function getClientConfig()
    {
        $clientConfig = [];

        // Configuración de modal
        if (isset($this->config['modal']) && $this->config['modal']['enabled']) {
            $clientConfig['modal'] = [
                'id' => $this->config['modal']['modal_id'],
                'ajax_url' => $this->config['modal']['ajax_url'] ?? null,
                'auto_load' => $this->config['modal']['auto_load'] ?? false
            ];
        }

        // Intervalo de refresh (en milisegundos)
        if (isset($this->config['refresh_interval'])) {
            $clientConfig['refresh_interval'] = $this->config['refresh_interval'];
        }

        return $clientConfig;
    }

    /**
     * Verificar si el usuario tiene permiso para ver este indicador
     */
    public function hasPermission($userId, $cargoId)
    {
        global $conn;

        $stmt = $conn->prepare("
            SELECT p.vista 
            FROM permissions p
            INNER JOIN tools_erp t ON p.tool_id = t.id
            WHERE t.codigo = ? 
            AND t.tipo_componente = 'indicador'
            AND p.cargo_id = ? 
            AND p.vista = 1
        ");
        $stmt->execute([$this->codigo, $cargoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtener detalles del modal (si aplica)
     * Los indicadores pueden sobrescribir este método para proporcionar datos del modal
     */
    public function getModalData($userId, $params = [])
    {
        return null; // Por defecto no hay datos de modal
    }
}
