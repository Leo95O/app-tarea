<?php
namespace Api\Controllers;

use Api\Core\Response;
use Api\Core\JWTHelper;
use Api\Repositories\AuthRepository;
use Api\Validators\AuthValidator;
use Slim\Slim;

/**
 * Controlador de Autenticación
 * Responsabilidad: Orquestar Login, Bloqueos de Seguridad y Emisión de Tokens.
 */
class AuthController {

    private $repo;
    private $validator;

    public function __construct() {
        // Instanciamos las dependencias actualizadas
        $this->repo = new AuthRepository();
        $this->validator = new AuthValidator();
    }

    /**
     * POST / - Iniciar Sesión
     */
    public function login(Slim $app) {
        try {
            // 1. Obtener datos
            $req = json_decode($app->request()->getBody(), true);
            $ip = $app->request()->getIp(); // Slim obtiene la IP correctamente

            // 2. Validación de Formato (Usando instancia, no estático)
            $validacion = $this->validator->validarLogin($req);
            if (!$validacion['valido']) {
                return Response::enviar($app, Response::advertencia("Datos incompletos", $validacion['errores']), 400);
            }

            // 3. Verificar Bloqueo (Anti-Fuerza Bruta)
            // NOTA: El método en el repo nuevo se llama 'estaBloqueado'
            if ($this->repo->estaBloqueado($ip)) {
                // Registrar auditoría del bloqueo
                $this->repo->auditar(null, 'BLOQUEO_ACTIVO', $ip);
                return Response::enviar($app, Response::advertencia("Demasiados intentos fallidos. Espere 2 minutos."), 429);
            }

            // 4. Buscar Usuario (El método nuevo es 'obtenerPorEmail')
            $usuario = $this->repo->obtenerPorEmail($req['email']);

            // 5. Verificar Credenciales
            // Si usuario no existe O password incorrecto
            if (!$usuario || !password_verify($req['password'], $usuario['password_hash'])) {
                
                // Registrar fallo y castigar IP
                $this->repo->registrarIntentoFallido($req['email'], $ip);
                $this->repo->auditar($usuario['id'] ?? null, 'LOGIN_FALLIDO', $ip);
                
                return Response::enviar($app, Response::advertencia("Credenciales incorrectas"), 401);
            }

            // 6. Verificar si está activo (Regla de negocio)
            if ($usuario['activo'] == 0) {
                return Response::enviar($app, Response::advertencia("Su cuenta ha sido desactivada. Contacte al administrador."), 403);
            }

            // --- LOGIN EXITOSO ---

            // 7. Limpiar castigos previos
            $this->repo->limpiarIntentos($ip);

            // 8. Generar Token
            $datosUsuario = [
                'id' => $usuario['id'],
                'nombre_completo' => $usuario['nombre_completo'],
                'email' => $usuario['email'],
                'rol' => $usuario['rol'],
                'id_sucursal' => $usuario['id_sucursal']
            ];

            $token = JWTHelper::generarToken($datosUsuario);

            // 9. Auditar éxito
            $this->repo->auditar($usuario['id'], 'LOGIN_EXITOSO', $ip);

            // 10. Responder
            return Response::enviar($app, Response::exito([
                'token' => $token,
                'usuario' => $datosUsuario,
                'expira_en_segundos' => defined('JWT_DURACION_SEGUNDOS') ? JWT_DURACION_SEGUNDOS : 32400
            ], "Bienvenido al sistema"));

        } catch (\Exception $e) {
            error_log("Error AuthController::login: " . $e->getMessage());
            return Response::enviar($app, Response::error("Error interno de autenticación"), 500);
        }
    }

    /**
     * GET /verificar - Verificar Token
     */
    public function verificarToken(Slim $app) {
        try {
            $datos = JWTHelper::obtenerUsuarioDesdeToken();
            
            if ($datos) {
                return Response::enviar($app, Response::exito([
                    'usuario' => $datos, 
                    'valido' => true
                ], "Token activo"));
            } else {
                return Response::enviar($app, Response::advertencia("Token inválido o expirado"), 401);
            }
        } catch (\Exception $e) {
            error_log("Error verificarToken: " . $e->getMessage());
            return Response::enviar($app, Response::error("Error al verificar token"), 500);
        }
    }
}