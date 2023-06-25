<?php

//use Slim\Handlers\Strategies\RequestHandler;
//use Slim\Psr7\Request;

use Citroneta\Auto;
use Citroneta\Usuario;
use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
require_once "usuario.php";
require_once "auto.php";
require_once "accesodatos.php";
class MW
{
    /// VERIFICA SETEO DEL DATO A ESPERAR y VERIFICA QUE LOS CONTENIDOS A ESPERAR DEL DATO EXISTAN
    public function ifSet(Request $request, RequestHandler $handler) : Response
    {
        //var_dump("ACCEDE ifSet");

        if(isset($request->getParsedBody()['user']))
        {
            $obj = new stdClass;
            $obj = json_decode($request->getParsedBody()['user']);
            if(isset($obj->correo) && isset($obj->clave))
            {
                $newResponse = $handler->handle($request);
            }
            else
            {
                $objRespuesta = new stdClass;
                $objRespuesta->exito = false;
                $objRespuesta->mensaje = "CORREO O CLAVE NO ENVIADOS";    
                $newResponse = new Response(403);
                $newResponse->getBody()->write(json_encode($objRespuesta));            
            }
        }
        else
        {
            $objRespuesta = new stdClass;
            $objRespuesta->exito = false;
            $objRespuesta->mensaje = "USER NO ENVIADO";    
            $newResponse = new Response(403);
            $newResponse->getBody()->write(json_encode($objRespuesta));
        }
    

        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    /// VERIFICA QUE EL CONTENIDO DEL DATO PRINCIPAL NO ESTE CON UNA CADENA SIN CARACTERES
    public static function ifVoid(Request $request, RequestHandler $handler) : Response
    {
        //var_dump("ACCEDE ifVoid");
        $obj = new stdClass;
        $obj = json_decode($request->getParsedBody()['user']);

        if(!($obj->correo == "" || $obj->clave == ""))
        {
            $newResponse = $handler->handle($request);
        }
        else
        {
            $objRespuesta = new stdClass;
            $objRespuesta->exito = false;
            $objRespuesta->mensaje = "DATOS VACIOS";    
            $newResponse = new Response(403);
            $newResponse->getBody()->write(json_encode($objRespuesta));
        }

        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    /// VERIFICA QUE LOS DATOS ESTEN EN LA BASE DE DATOS
    public function ifExist(Request $request, RequestHandler $handler) : Response
    {
        //var_dump("ACCEDE ifExist");
        $obj = new stdClass;
        $obj = json_decode($request->getParsedBody()['user']);

        $UsuarioBuscado = Usuario::ObtenerUsuario(true,$obj);
        if($UsuarioBuscado != NULL)
        {        
            $newResponse = $handler->handle($request);
        }
        else
        {
            $objRespuesta = new stdClass;
            $objRespuesta->exito = false;
            $objRespuesta->mensaje = "DATOS INCORRECTOS";
            $newResponse = new Response(403);
            $newResponse->getBody()->write(json_encode($objRespuesta));
        }

        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    /// VERIFICA LA EXISTENCIA DE UN CORREO EN LA BASE DE DATOS
    public static function ExisteCorreo(Request $request, RequestHandler $handler) : Response
    {
        $obj = new stdClass;
        $obj = json_decode($request->getParsedBody()['user']);
     
        $UsuarioBuscado = Usuario::ObtenerUsuarioPorCorreo($obj->correo);

        if($UsuarioBuscado == NULL)
        {        
            $newResponse = $handler->handle($request);
        }
        else
        {
            $objRespuesta = new stdClass;
            $objRespuesta->exito = false;
            $objRespuesta->mensaje = "EL CORREO YA EXISTE. UTILICE OTRO CORREO";
            $newResponse = new Response(403);
            $newResponse->getBody()->write(json_encode($objRespuesta));
        }

        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    public function VerificarPrecioColor(Request $request, RequestHandler $handler) : Response
    {
        $obj = new Stdclass;
        $obj = json_decode($request->getParsedBody()['auto']); 
        
        if($obj->precio > 50000 && $obj->precio < 600000)
        {
            $obj->color = strtolower($obj->color);        
            if($obj->color != "azul")
            {
                $newResponse = $handler->handle($request);
            }
            else
            {
                $objRespuesta = new stdClass;
                $objRespuesta->exito = false;
                $objRespuesta->mensaje = "ERROR, EL COLOR AZUL NO ES VALIDO";
                $newResponse = new Response(409);
                $newResponse->getBody()->write(json_encode($objRespuesta));
            }
        }
        else
        {
            $objRespuesta = new stdClass;
            $objRespuesta->exito = false;
            $objRespuesta->mensaje = "ERROR, NO CUMPLE CON EL RANGO DE PRECIO (50.000 - 600.000)";
            $newResponse = new Response(409);
            $newResponse->getBody()->write(json_encode($objRespuesta));
        }

        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    /// VERIFICA TOKEN, SI ES VALIDO PASA A LA SIGUINETE CALL
    public function verificarToken(Request $request, RequestHandler $handler) : Response 
    {

        if(isset($request->getHeader("token")[0]))
        {
            $token = $request->getHeader("token")[0];

            $obj_token = Autentificadora::verificarJWT($token);
    
            if($obj_token->exito){
    
                $response = $handler->handle($request);
            }
            else
            {
                $objRespuesta = new stdClass;
                $objRespuesta->exito = false;
                $objRespuesta->mensaje = "ERROR, TOKEN NO VALIDO";
                $response = new Response(403);
                $response->getBody()->write(json_encode($objRespuesta));
            } 
        }
        else
        {
            $objRespuesta = new stdClass;
            $objRespuesta->exito = false;
            $objRespuesta->mensaje = "ERROR, TOKEN NO ENVIADO";
            $response = new Response(403);
            $response->getBody()->write(json_encode($objRespuesta));
        }  

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function VerificarPropietario(Request $request, RequestHandler $handler) : Response 
    {
        $token = $request->getHeader("token")[0];

        $objJwt = Autentificadora::obtenerPayLoad($token);

        $usuario = $objJwt->payload->data;

        $objRespuesta = new stdClass;
        $objRespuesta->exito = false;

        if($usuario->perfil === "propietario")
        {
            $objRespuesta->mensaje = "Operacion Realizada";
            $objRespuesta->exito = true;
            $response = new Response(200);
            $response->getBody()->write(json_encode($objRespuesta));
            
            $response = $handler->handle($request);
        }
        else
        {
            $objRespuesta->mensaje = "No se puede Realizar lar Operacion, NO ES PROPIETARIO";
            $response = new Response(409);
            $response->getBody()->write(json_encode($objRespuesta));
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function VerificarEncargado(Request $request, RequestHandler $handler) : Response 
    {
        $token = $request->getHeader("token")[0];

        $objJwt = Autentificadora::obtenerPayLoad($token);

        $usuario = $objJwt->payload->data;

        $objRespuesta = new stdClass;
        $objRespuesta->exito = false;

        if($usuario->perfil === "encargado")
        {
            $objRespuesta->mensaje = "Operacion Realizada";
            $objRespuesta->exito = true;
            $response = new Response(200);
            $response->getBody()->write(json_encode($objRespuesta));
            
            $response = $handler->handle($request);
        }
        else
        {
            $objRespuesta->mensaje = "No se puede Realizar lar Operacion, NO ES ENCARGADO";
            $response = new Response(409);
            $response->getBody()->write(json_encode($objRespuesta));
        }

        return $response->withHeader('Content-Type', 'application/json');
    }


    // A ////////////////////////////////////////////////////////////////////////////////////

    public function ListadoAutosEncargado(Request $request, RequestHandler $handler) : Response  
    {
        $token = $request->getHeader("token")[0];

        $objJwt = Autentificadora::obtenerPayLoad($token);

        $usuario = $objJwt->payload->data;

        $response = new Response(200);

        if($usuario->perfil === "encargado")
        {

            $listado = array();
            $autos = Auto::traerAutos();
           
            foreach($autos as $auto)
            {
                $objAuto = new stdClass;
                $objAuto->color = $auto->color;
                $objAuto->marca = $auto->marca;
                $objAuto->precio = $auto->precio;
                $objAuto->modelo = $auto->modelo;

                array_push($listado,$objAuto);
            }

            $response->getBody()->write(json_encode($listado));
        }
        else
        { 
            $response = $handler->handle($request);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }


    public function ListadoAutosEmpleado(Request $request, RequestHandler $handler) : Response  
    {
        $token = $request->getHeader("token")[0];

        $objJwt = Autentificadora::obtenerPayLoad($token);

        $usuario = $objJwt->payload->data;

        $response = new Response(200);

        if($usuario->perfil === "empleado")
        {

            $listado = array();
            $autos = Auto::traerAutos();
            $primerIngreso = true;
           
            foreach($autos as $auto)
            {
                if($primerIngreso)
                {
                    array_push($listado,$auto->color);
                    $primerIngreso = false;
                }
                else
                {
                    foreach($listado as $color)
                    {
                        if($auto->color != $color)
                        {
                            $esDistinto = true;   
                        }
                        else
                        {
                            $esDistinto = false;
                        }
                    }

                    if($esDistinto)
                    {
                        array_push($listado,$auto->color);
                    }
                }
            }

            $response->getBody()->write(json_encode($listado));
        }
        else
        { 
            $response = $handler->handle($request);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }


    public static function ListadoAutosPropietario(Request $request, RequestHandler $handler) : Response  
    {
        $token = $request->getHeader("token")[0];

        $objJwt = Autentificadora::obtenerPayLoad($token);

        $usuario = $objJwt->payload->data;

        $response = new Response(200);

        if($usuario->perfil === "propietario")
        {
            if(!isset($request->getHeader('id')[0]))
            {
                $listado = Auto::traerAutos();
                $response->getBody()->write(json_encode($listado));
            }
            else
            {
                if($request->getHeader('id')[0] === "")
                {
                    $listado = Auto::traerAutos();
                    $response->getBody()->write(json_encode($listado));
                }
                else
                {               
                    $autoSegunId = array();
                    $autos = Auto::traerAutos();
                
                    foreach($autos as $auto)
                    {
                        if($auto->id == $request->getHeader('id')[0])
                        {
                            array_push($autoSegunId,$auto);
                            break;
                        }             
                    }

                    $response->getBody()->write(json_encode($autoSegunId));
                }
            }
        }
        else
        { 
            $response = $handler->handle($request);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    // B //////////////////////////////////////////////////////////////////////////////////

    public function ListadoUsuariosEncargado(Request $request, RequestHandler $handler) : Response  
    {
        $token = $request->getHeader("token")[0];

        $objJwt = Autentificadora::obtenerPayLoad($token);

        $usuario = $objJwt->payload->data;

        $response = new Response(200);

        if($usuario->perfil === "encargado")
        {

            $listado = array();
            $usuarios = Usuario::traerUsuarios();
           
            foreach($usuarios as $us)
            {
                $objUs = new stdClass;
                $objUs->correo = $us->correo;
                $objUs->nombre = $us->nombre;
                $objUs->apellido = $us->apellido;
                $objUs->perfil = $us->perfil;
                $objUs->foto = $us->foto;
                array_push($listado,$objUs);
            }

            $response->getBody()->write(json_encode($listado));
        }
        else
        { 
            $response = $handler->handle($request);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
    public function ListadoUsuariosEmpleado(Request $request, RequestHandler $handler) : Response  
    {
        $token = $request->getHeader("token")[0];

        $objJwt = Autentificadora::obtenerPayLoad($token);

        $usuario = $objJwt->payload->data;

        $response = new Response(200);

        if($usuario->perfil === "empleado")
        {

            $listado = array();
            $usuarios = Usuario::traerUsuarios();
           
            foreach($usuarios as $us)
            {
                $objUs = new stdClass;
                $objUs->nombre = $us->nombre;
                $objUs->apellido = $us->apellido;
                $objUs->foto = $us->foto;
                array_push($listado,$objUs);
            }

            $response->getBody()->write(json_encode($listado));
        }
        else
        { 
            $response = $handler->handle($request);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function ListadoUsuariosPropietario(Request $request, RequestHandler $handler) : Response  
    {
        $token = $request->getHeader("token")[0];

        $objJwt = Autentificadora::obtenerPayLoad($token);

        $usuario = $objJwt->payload->data;

        $response = new Response(200);

        if($usuario->perfil === "propietario")
        {
            if(!isset($request->getHeader('apellido')[0]))
            {
                
                $apellidos = array();
                $listado = Usuario::traerUsuarios();
            
                $primerIngreso = true;
                foreach($listado as $us)
                {
                    if($primerIngreso)
                    {
                        array_push($apellidos,$us->apellido);
                        $primerIngreso = false;
                    }
                    else
                    {
                        foreach($apellidos as $apellido)
                        {
                            if($us->apellido != $apellido)
                            {
                                $esDistinto = true;
                            }
                            else{
                                $esDistinto = false;
                            }
                        }

                        if($esDistinto)
                        {
                            array_push($apellidos,$us->apellido);
                        }
                    }
                }

                $arrayRespuesta = array();

                foreach($apellidos as $apellido)
                {
                    
                    $usuario = new stdClass;
                    $usuario->cantidad = 0;
                    $banderaPrimerPush = true;
                    
                    foreach($listado as $us)
                    {
                        if($us->apellido ===  $apellido)
                        {
                            $usuario->apellido = $apellido;
                            $usuario->cantidad++;
                            if($banderaPrimerPush)
                            {
                                $banderaPrimerPush = false;
                                array_push($arrayRespuesta,$usuario);
                            }
                        }
                    }
                }

                $response->getBody()->write(json_encode($arrayRespuesta));
            }
            else
            {
                ///REPITE LO DE ARRIBA (NO SE APLICO RECURSIVIDAD)
                if($request->getHeader('apellido')[0] === "")
                {
                    $apellidos = array();
                    $listado = Usuario::traerUsuarios();
                
                    $primerIngreso = true;
                    foreach($listado as $us)
                    {
                        if($primerIngreso)
                        {
                            array_push($apellidos,$us->apellido);
                            $primerIngreso = false;
                        }
                        else
                        {
                            foreach($apellidos as $apellido)
                            {
                                if($us->apellido != $apellido)
                                {
                                    $esDistinto = true;
                                }
                                else{
                                    $esDistinto = false;
                                }
                            }
    
                            if($esDistinto)
                            {
                                array_push($apellidos,$us->apellido);
                            }
                        }
                    }
    
                    $arrayRespuesta = array();
    
                    foreach($apellidos as $apellido)
                    {
                        
                        $usuario = new stdClass;
                        $usuario->cantidad = 0;
                        $banderaPrimerPush = true;
                        
                        foreach($listado as $us)
                        {
                            if($us->apellido ===  $apellido)
                            {
                                $usuario->apellido = $apellido;
                                $usuario->cantidad++;
                                if($banderaPrimerPush)
                                {
                                    $banderaPrimerPush = false;
                                    array_push($arrayRespuesta,$usuario);
                                }
                            }
                        }
                    }
    
                    $response->getBody()->write(json_encode($arrayRespuesta));
                }
                else
                {               
                    $us = new stdClass;
                    $respuesta = array();
                    $listado = Usuario::traerUsuarios();
                    $us->apellido = $request->getHeader('apellido')[0];
                    $us->cont = 0;
                
                    foreach($listado as $usuario)
                    {
                        if($usuario->apellido == $us->apellido)
                        {
                            $us->cont++;
                        }             
                    }
                    
                    array_push($respuesta,$us);

                    $response->getBody()->write(json_encode($respuesta));
                }
            }
        }
        else
        { 
            $response = $handler->handle($request);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

}