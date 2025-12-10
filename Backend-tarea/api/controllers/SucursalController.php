<?php
namespace Api\Controllers;

use Api\Core\Response;
use Api\Core\JWTMiddleware;
use Api\Validators\SucursalValidator;
use Api\Repositories\SucursalRepository;
use Slim\Slim;

/**
 * Controlador de Sucursales
 *
 * Orquesta la gestión de sucursales con creación atómica.
 * IMPORTANTE: Solo accesible por usuarios con rol CEO.
 */
class SucursalController {

    private $sucursalRepository;

    /**
     * Constructor
     */
    public function __construct() {
        $this->sucursalRepository = new SucursalRepository();
    }

    /**
     * Crea una sucursal con su Gerente General (Transacción Atómica)
     *
     * Endpoint: POST /sucursales
     * Permisos: Solo CEO
     *
     * @param Slim $app Instancia de Slim
     */
    public function crearAtomica(Slim $app) {
        try {
            // Verificar que sea CEO
            if (!JWTMiddleware::verificarRol($app, ['CEO'])) {
                return;
            }

            // Obtener datos del request
            $datos = json_decode($app->request()->getBody(), true);
            $datos_sucursal = $datos['sucursal'] ?? [];
            $datos_gerente = $datos['gerente_general'] ?? [];

            // Validar datos
            $validacion = SucursalValidator::validarCreacionAtomica($datos_sucursal, $datos_gerente);

            if (!$validacion['valido']) {
                $respuesta = Response::advertencia($validacion['errores']);
                Response::enviar($app, $respuesta, 400);
                return;
            }

            // Crear sucursal + GG en transacción atómica
            $resultado = $this->sucursalRepository->crearAtomica($datos_sucursal, $datos_gerente);

            if (!$resultado['exito']) {
                $respuesta = Response::advertencia($resultado['error']);
                Response::enviar($app, $respuesta, 400);
                return;
            }

            // Respuesta exitosa
            $respuesta = Response::exito([
                'id_sucursal' => $resultado['id_sucursal'],
                'id_gerente_general' => $resultado['id_gerente'],
                'sucursal' => $datos_sucursal['nombre'],
                'gerente_general' => $datos_gerente['nombre_completo']
            ], "Sucursal creada exitosamente con su Gerente General");

            Response::enviar($app, $respuesta, 201);

        } catch (\Exception $e) {
            error_log("Error en crearAtomica: " . $e->getMessage());
            $respuesta = Response::error("Error al crear la sucursal");
            Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * Lista todas las sucursales
     *
     * Endpoint: GET /sucursales
     * Permisos: Solo CEO
     *
     * @param Slim $app Instancia de Slim
     */
    public function listar(Slim $app) {
        try {
            // Verificar que sea CEO
            if (!JWTMiddleware::verificarRol($app, ['CEO'])) {
                return;
            }

            // Obtener parámetro opcional: solo_activas
            $solo_activas = $app->request()->get('solo_activas') === 'true';

            $sucursales = $this->sucursalRepository->obtenerTodas($solo_activas);

            $respuesta = Response::exito([
                'sucursales' => $sucursales,
                'total' => count($sucursales)
            ], "Sucursales obtenidas exitosamente");

            Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en listar: " . $e->getMessage());
            $respuesta = Response::error("Error al obtener las sucursales");
            Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * Obtiene una sucursal por ID
     *
     * Endpoint: GET /sucursales/:id
     * Permisos: Solo CEO
     *
     * @param Slim $app Instancia de Slim
     * @param int $id ID de la sucursal
     */
    public function obtenerPorId(Slim $app, $id) {
        try {
            // Verificar que sea CEO
            if (!JWTMiddleware::verificarRol($app, ['CEO'])) {
                return;
            }

            $sucursal = $this->sucursalRepository->obtenerPorId($id);

            if (!$sucursal) {
                $respuesta = Response::advertencia("Sucursal no encontrada");
                Response::enviar($app, $respuesta, 404);
                return;
            }

            $respuesta = Response::exito([
                'sucursal' => $sucursal->aArray()
            ], "Sucursal obtenida exitosamente");

            Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en obtenerPorId: " . $e->getMessage());
            $respuesta = Response::error("Error al obtener la sucursal");
            Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * Actualiza los datos de una sucursal
     *
     * Endpoint: PUT /sucursales/:id
     * Permisos: Solo CEO
     *
     * @param Slim $app Instancia de Slim
     * @param int $id ID de la sucursal
     */
    public function actualizar(Slim $app, $id) {
        try {
            // Verificar que sea CEO
            if (!JWTMiddleware::verificarRol($app, ['CEO'])) {
                return;
            }

            // Verificar que la sucursal existe
            $sucursal = $this->sucursalRepository->obtenerPorId($id);
            if (!$sucursal) {
                $respuesta = Response::advertencia("Sucursal no encontrada");
                Response::enviar($app, $respuesta, 404);
                return;
            }

            // Obtener datos a actualizar
            $datos = json_decode($app->request()->getBody(), true);

            // Validar datos
            $validacion = SucursalValidator::validarActualizacion($datos);
            if (!$validacion['valido']) {
                $respuesta = Response::advertencia($validacion['errores']);
                Response::enviar($app, $respuesta, 400);
                return;
            }

            // Actualizar
            $this->sucursalRepository->actualizar($id, $datos);

            $respuesta = Response::exito(
                ['id' => $id],
                "Sucursal actualizada exitosamente"
            );

            Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en actualizar: " . $e->getMessage());
            $respuesta = Response::error("Error al actualizar la sucursal");
            Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * Desactiva una sucursal (Soft Delete)
     *
     * Endpoint: DELETE /sucursales/:id
     * Permisos: Solo CEO
     *
     * @param Slim $app Instancia de Slim
     * @param int $id ID de la sucursal
     */
    public function desactivar(Slim $app, $id) {
        try {
            // Verificar que sea CEO
            if (!JWTMiddleware::verificarRol($app, ['CEO'])) {
                return;
            }

            // Verificar que la sucursal existe
            $sucursal = $this->sucursalRepository->obtenerPorId($id);
            if (!$sucursal) {
                $respuesta = Response::advertencia("Sucursal no encontrada");
                Response::enviar($app, $respuesta, 404);
                return;
            }

            // Desactivar
            $this->sucursalRepository->desactivar($id);

            $respuesta = Response::exito(
                ['id' => $id],
                "Sucursal desactivada exitosamente. Los datos históricos se han preservado."
            );

            Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en desactivar: " . $e->getMessage());
            $respuesta = Response::error("Error al desactivar la sucursal");
            Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * Reactiva una sucursal
     *
     * Endpoint: POST /sucursales/:id/reactivar
     * Permisos: Solo CEO
     *
     * @param Slim $app Instancia de Slim
     * @param int $id ID de la sucursal
     */
    public function reactivar(Slim $app, $id) {
        try {
            // Verificar que sea CEO
            if (!JWTMiddleware::verificarRol($app, ['CEO'])) {
                return;
            }

            // Verificar que la sucursal existe
            $sucursal = $this->sucursalRepository->obtenerPorId($id);
            if (!$sucursal) {
                $respuesta = Response::advertencia("Sucursal no encontrada");
                Response::enviar($app, $respuesta, 404);
                return;
            }

            // Reactivar
            $this->sucursalRepository->reactivar($id);

            $respuesta = Response::exito(
                ['id' => $id],
                "Sucursal reactivada exitosamente"
            );

            Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en reactivar: " . $e->getMessage());
            $respuesta = Response::error("Error al reactivar la sucursal");
            Response::enviar($app, $respuesta, 500);
        }
    }
}
