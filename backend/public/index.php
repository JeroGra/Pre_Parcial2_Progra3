<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;

use Slim\Factory\AppFactory;
use \Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';
require_once "../src/poo/usuario.php";
require_once "../src/poo/auto.php";
require_once "../src/poo/autentificadora.php";
require_once "../src/poo/mw.php";

use Citroneta\Usuario;
use Citroneta\Auto;
use Firebase\JWT\JWT;

$app = AppFactory::create();



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////// VERIFICO POR RUTA GET->(/) QUE LEVANTE EL SERVIDOR /////////////////////////////////////////////////////////////////////////////
$app->put('/test', function (Request $request, Response $response, array $args) : Response {  

    $response->getBody()->write("GET => Bienvenido!!! a SlimFramework 4");
    return $response;
});
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

////////////// VERBOS A NIVEL APP //////////////////////////////////////////////////////////////////////////////////////////////////////////////
$app->get('[/]', Usuario::class . ':TraerTodos')->add(MW::class . ':ListadoUsuariosEncargado')->add(MW::class . ':ListadoUsuariosEmpleado')->add(MW::class . '::ListadoUsuariosPropietario')->add(MW::class . ':verificarToken');;

$app->post('[/]', Auto::class . ':AgregarUno')->add(MW::class . ':VerificarPrecioColor');

$app->delete('/{id_auto}', Auto::class . ':BorrarUno')->add(MW::class . '::VerificarPropietario')->add(MW::class . ':verificarToken');

$app->put('/{id_auto}/{json_auto}', Auto::class . ':ModificarUno' )->add(MW::class .':VerificarEncargado')->add(MW::class . ':verificarToken');

///////////// VERBOS A NIVEL RUTA /////////////////////////////////////////////////////////////////////////////////////////////////////////////
$app->group('/usuarios',  function(RouteCollectorProxy $grupo){

    $grupo->post('[/]', Usuario::class . ':AgregarUno')->add(MW::class . '::ExisteCorreo')->add(MW::class . '::ifVoid')->add(MW::class . ':ifSet');
});

$app->group('/autos',  function(RouteCollectorProxy $grupo){

    $grupo->get('[/]', Auto::class . ':TraerTodos')->add(MW::class . ':ListadoAutosEncargado')->add(MW::class . ':ListadoAutosEmpleado')->add(MW::class . '::ListadoAutosPropietario')->add(MW::class . ':verificarToken');
});

$app->group('/login',  function(RouteCollectorProxy $grupo){

    $grupo->post('[/]', function (Request $request, Response $response, array $args) : Response { 
       
        //var_dump("ACCEDE api");

        $obj = new stdClass;
        $obj = json_decode($request->getParsedBody()['user']);
        $usuario = Usuario::ObtenerUsuario(true,$obj);

        $jwt = Autentificadora::crearJWT($usuario);

        $objDelaRespuesta = new stdClass;
        $objDelaRespuesta->exito = true;
        $objDelaRespuesta->jwt = $jwt;
        $newResponse = $response->withStatus(200, "OK");

        $newResponse->getBody()->write(json_encode($objDelaRespuesta));

        return $newResponse->withHeader('Content-Type', 'application/json');
    })->add(MW::class . ':ifExist')->add(MW::class . '::ifVoid')->add(MW::class . ':ifSet');

    /// HACER LA VERIFY
    $grupo->get('[/]', function (Request $request, Response $response, array $args) : Response { 

        $objDelaRespuesta = new stdClass;
        $objDelaRespuesta->exito = true;
        $objDelaRespuesta->mensaje = "Token Valido";
        $newResponse = $response->withStatus(200, "OK");

        $newResponse->getBody()->write(json_encode($objDelaRespuesta));

        return $newResponse->withHeader('Content-Type', 'application/json');
    })->add(MW::class . ':verificarToken');
});

















///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////// CORRE EL SERVIDOR  ULTIMA LINE DE LA APP //////////////////////////////////////////////////////////////////////////////////////
$app->run();