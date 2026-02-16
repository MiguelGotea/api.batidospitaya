<?php

namespace Core\Components\Balances;

/**
 * Base Balance Component
 */
abstract class BaseBalance
{
    protected $conn;
    protected $config;

    public function __construct($conn, $configJson = null)
    {
        $this->conn = $conn;
        $this->config = $configJson ? json_decode($configJson, true) : [];
    }

    /**
     * Render balance data
     * @param int $userId
     * @return array
     */
    abstract public function render($userId);
}
