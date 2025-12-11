<?php
namespace Api\Controllers;

use Api\Core\Response;
use Api\Core\JWTHelper;
use Api\Repositories\UsuarioRepository;
use Api\Validators\JerarquiaValidator;
use Api\Validators\AuthValidator;
use Api\Middlewares\JWTMiddleware;
use Slim\Slim;

class UsuarioController {

    private $repo;
    private $validadorJerarquia;
    private $validadorAuth;

    public function __construct() {
        $this->repo = new UsuarioRepository();
        $this->validadorJerarquia = new JerarquiaValidator();
        $this->validadorAuth = new AuthValidator();
    }

    /**
     * POST / - Crear Usuario
     */
    public function crear(Slim $app) {
        try {
            $creador = JWTMiddleware::obtenerUsuarioAutenticado();
            $datos = json_decode($app->request()->getBody(), true);

            // 1. Validar campos obligatorios
            if (empty($datos['email']) || empty($datos['password']) || empty($datos['nombre_completo']) || empty($datos['rol'])) {
                return Response::enviar($app, Response::advertencia("Faltan datos obligatorios"), 400);
            }

            // 2. Validar formato email
            if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
                return Response::enviar($app, Response::advertencia("Formato de email inválido"), 400);
            }

            // 3. Validar permisos de jerarquía
            if (!$this->validadorJerarquia->puedeCrearUsuario($creador['rol'], $datos['rol'])) {
                return Response::enviar($app, Response::advertencia("No tienes permisos para crear un usuario con el rol: " . $datos['rol']), 403);
            }

            // 4. Determinar sucursal
            $id_sucursal_destino = null;
            if ($creador['rol'] === 'CEO') {
                if (empty($datos['id_sucursal'])) {
                    return Response::enviar($app, Response::advertencia("El CEO debe especificar la sucursal del usuario"), 400);
                }
                $id_sucursal_destino = $datos['id_sucursal'];
            } else {
                $id_sucursal_destino = $creador['id_sucursal'];
            }

            // 5. Preparar datos
            $nuevoUsuario = [
                'nombre_completo' => trim($datos['nombre_completo']),
                'email'           => trim(strtolower($datos['email'])),
                'password'        => $datos['password'],
                'rol'             => $datos['rol'],
                'id_sucursal'     => $id_sucursal_destino
            ];

            // 6. Guardar
            $resultado = $this->repo->crear($nuevoUsuario);

            if (!$resultado['exito']) {
                return Response::enviar($app, Response::advertencia($resultado['error']), 400);
            }

            return Response::enviar($app, Response::exito(['id' => $resultado['id']], "Usuario creado exitosamente"), 201);

        } catch (\Exception $e) {
            error_log("Controller Usuario Crear: " . $e->getMessage());
            return Response::enviar($app, Response::error("Error interno del servidor"), 500);
        }
    }

    /**
     * GET / - Listar usuarios (para Selectores)
     */
    public function listar(Slim $app) {
        try {
            $solicitante = JWTMiddleware::obtenerUsuarioAutenticado();
            $lista = $this->repo->listarParaSelector($solicitante);

            return Response::enviar($app, Response::exito(['usuarios' => $lista]), 200);

        } catch (\Exception $e) {
            error_log("Controller Usuario Listar: " . $e->getMessage());
            return Response::enviar($app, Response::error("Error al obtener usuarios"), 500);
        }
    }

    /**
     * GET /autocomplete?q=Juan
     */
    public function autocomplete(Slim $app) {
        try {
            $usuario = JWTHelper::obtenerUsuarioDesdeToken();
            if (!$usuario) return Response::enviar($app, Response::error("No autorizado"), 401);

            // Permitir query vacío (traer primeros 10)
            $query = $app->request()->get('q') ?? '';

            $resultados = $this->repo->buscarParaAsignacion($query, $usuario);

            return Response::enviar($app, Response::exito($resultados));

        } catch (\Exception $e) {
            error_log("Controller Usuario Autocomplete: " . $e->getMessage());
            return Response::enviar($app, Response::error("Error buscando usuarios"), 500);
        }
    }
}
