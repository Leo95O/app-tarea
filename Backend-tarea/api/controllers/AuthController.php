<?php
namespace Api\Controllers;

use Api\Core\Response;
use Api\Core\JWTHelper;
use Api\Validators\AuthValidator;
use Api\Repositories\AuthRepository;
use Slim\Slim;

/**
 * Controlador de Autenticación
 *
 * Orquesta el flujo de login:
 * 1. Valida formato de credenciales
 * 2. Verifica bloqueo por intentos fallidos
 * 3. Busca usuario en BD
 * 4. Verifica contraseña
 * 5. Genera token JWT
 * 6. Registra auditoría
 */
class AuthController {

    private $authRepository;

    /**
     * Constructor
     */
    public function __construct() {
        $this->authRepository = new AuthRepository();
    }

    /**
     * Login de usuario
     *
     * Endpoint: POST /auth/login
     *
     * @param Slim $app Instancia de Slim
     */
    public function login(Slim $app) {
        try {
            // 1. Obtener datos del request
            $datos = json_decode($app->request()->getBody(), true);
            $email = $datos['email'] ?? '';
            $password = $datos['password'] ?? '';
            $ip_origen = $this->obtenerIPCliente();

            // 2. Validar formato de credenciales
            $validacion = AuthValidator::validarCredenciales($email, $password);
            if (!$validacion['valido']) {
                $respuesta = Response::advertencia($validacion['errores']);
                Response::enviar($app, $respuesta, 400);
                return;
            }

            // 3. Verificar bloqueo por intentos fallidos
            $bloqueo = $this->authRepository->verificarBloqueo($email, $ip_origen);
            if ($bloqueo['bloqueado']) {
                $tiempo_minutos = ceil($bloqueo['tiempo_restante'] / 60);
                $mensaje = "Cuenta temporalmente bloqueada por múltiples intentos fallidos. " .
                          "Intente nuevamente en {$tiempo_minutos} minuto(s).";

                $this->authRepository->registrarAuditoria(null, 'BLOQUEO_CUENTA', "Email: {$email}, IP: {$ip_origen}");

                $respuesta = Response::advertencia($mensaje);
                Response::enviar($app, $respuesta, 429); // 429 Too Many Requests
                return;
            }

            // 4. Buscar usuario por email
            $usuario = $this->authRepository->buscarPorEmail($email);

            if (!$usuario) {
                // Usuario no existe - Registrar intento fallido sin dar pistas
                $this->authRepository->registrarIntentoFallido($email, $ip_origen);
                $this->authRepository->registrarAuditoria(null, 'LOGIN_FALLIDO', "Email inexistente: {$email}, IP: {$ip_origen}");

                $respuesta = Response::advertencia("Credenciales incorrectas");
                Response::enviar($app, $respuesta, 401);
                return;
            }

            // 5. Verificar contraseña
            if (!password_verify($password, $usuario->password_hash)) {
                // Contraseña incorrecta
                $this->authRepository->registrarIntentoFallido($email, $ip_origen);
                $this->authRepository->registrarAuditoria($usuario->id, 'LOGIN_FALLIDO', "Contraseña incorrecta, IP: {$ip_origen}");

                $respuesta = Response::advertencia("Credenciales incorrectas");
                Response::enviar($app, $respuesta, 401);
                return;
            }

            // 6. Login exitoso - Limpiar intentos fallidos
            $this->authRepository->limpiarIntentosLogin($email);

            // 7. Generar token JWT
            $datos_usuario = $usuario->aArray();
            $token = JWTHelper::generarToken($datos_usuario);

            // 8. Registrar auditoría
            $this->authRepository->registrarAuditoria($usuario->id, 'LOGIN_EXITOSO', "IP: {$ip_origen}");

            // 9. Preparar respuesta (sin datos sensibles)
            unset($datos_usuario['password_hash']);
            unset($datos_usuario['activo']);
            unset($datos_usuario['creado_en']);

            $respuesta = Response::exito([
                'token' => $token,
                'usuario' => $datos_usuario,
                'expira_en_segundos' => JWT_DURACION_SEGUNDOS
            ], "Inicio de sesión exitoso");

            Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $respuesta = Response::error("Error al procesar el inicio de sesión");
            Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * Verifica token JWT (útil para renovación o validación)
     *
     * Endpoint: GET /auth/verificar
     *
     * @param Slim $app Instancia de Slim
     */
    public function verificarToken(Slim $app) {
        try {
            $datos_usuario = JWTHelper::obtenerUsuarioDesdeToken();

            if (!$datos_usuario) {
                $respuesta = Response::advertencia("Token inválido o expirado");
                Response::enviar($app, $respuesta, 401);
                return;
            }

            $respuesta = Response::exito([
                'usuario' => $datos_usuario,
                'token_valido' => true
            ], "Token válido");

            Response::enviar($app, $respuesta, 200);

        } catch (\Exception $e) {
            error_log("Error en verificarToken: " . $e->getMessage());
            $respuesta = Response::error("Error al verificar el token");
            Response::enviar($app, $respuesta, 500);
        }
    }

    /**
     * Obtiene la IP real del cliente (considerando proxies)
     *
     * @return string IP del cliente
     */
    private function obtenerIPCliente() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
}
