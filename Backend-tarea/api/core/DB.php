<?php
namespace Api\Core;

use PDO;
use PDOException;

/**
 * Clase de Conexión a Base de Datos (Patrón Singleton)
 *
 * Garantiza una única instancia de conexión PDO durante toda la ejecución.
 * Configurada con:
 * - Modo de errores: Excepciones
 * - Fetch mode: Arrays asociativos
 * - Charset: UTF-8
 */
class DB {

    private static $instancia = null;
    private $conexion;

    /**
     * Constructor Privado (Patrón Singleton)
     *
     * Crea la conexión PDO con configuración de seguridad y rendimiento.
     *
     * @throws PDOException Si falla la conexión
     */
    private function __construct() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lanza excepciones en errores SQL
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Retorna arrays asociativos
                PDO::ATTR_EMULATE_PREPARES   => false,                   // Prepared statements reales
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET // Fuerza charset
            ];

            $this->conexion = new PDO($dsn, DB_USER, DB_PASS, $opciones);

        } catch (PDOException $e) {
            // Registrar error en log y ocultar detalles al cliente
            error_log("Error de conexión a BD: " . $e->getMessage());
            throw new PDOException("No se pudo conectar a la base de datos. Contacte al administrador.");
        }
    }

    /**
     * Obtiene la instancia única de DB
     *
     * @return DB Instancia singleton
     */
    public static function obtenerInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene la conexión PDO activa
     *
     * @return PDO Objeto de conexión
     */
    public function obtenerConexion() {
        return $this->conexion;
    }

    /**
     * Inicia una transacción SQL
     *
     * @return bool True si se inició correctamente
     */
    public function iniciarTransaccion() {
        return $this->conexion->beginTransaction();
    }

    /**
     * Confirma (commit) una transacción
     *
     * @return bool True si se confirmó correctamente
     */
    public function confirmarTransaccion() {
        return $this->conexion->commit();
    }

    /**
     * Revierte (rollback) una transacción
     *
     * @return bool True si se revirtió correctamente
     */
    public function revertirTransaccion() {
        return $this->conexion->rollBack();
    }

    /**
     * Obtiene el ID del último registro insertado
     *
     * @return string ID del último INSERT
     */
    public function obtenerUltimoId() {
        return $this->conexion->lastInsertId();
    }

    /**
     * Previene clonación de la instancia (Singleton)
     */
    private function __clone() {}

    /**
     * Previene deserialización de la instancia (Singleton)
     */
    public function __wakeup() {
        throw new \Exception("No se puede deserializar un Singleton");
    }
}
