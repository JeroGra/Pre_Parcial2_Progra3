<?php 
namespace Citroneta
{

    use AccesoDatos;
    use Error;
    use FastRoute\RouteParser\Std;
    use ISlimeable;
    use PDO;
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use stdClass;

    require_once "accesodatos.php";
    require_once "islimeable.php";

    class Usuario implements ISlimeable
    {
        public int $id;
        public string $correo;
        public string $clave;
        public string $nombre;
        public string $apellido;
        public string $perfil;
        public string $foto;


        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////// IMPLEMENTACIONES DE INTERFAZ ////////////////////////////////////////////////////////////////////////////////////////////////////
       
        //////////////// LISTAR TODOS LOS OBJ //////////////////////////////////////////////////
        public function TraerTodos(Request $request, Response $response, array $args): Response 
        {
            try
            {
                $usuarios = Usuario::traerUsuarios();

                $respuesta = new stdClass;
                $respuesta->exito = true;
                $respuesta->lista = $usuarios;

                $newResponse = $response->withStatus(200, "OK");
                $newResponse->getBody()->write(json_encode($respuesta));

            }
            catch(Error)
            {
                $respuesta = new stdClass;
                $respuesta->exito = false;
                $respuesta->lista = json_encode("");

                $newResponse = $response->withStatus(424, "ERROR");
                $newResponse->getBody()->write(json_encode($respuesta));
            }
            finally
            {
                return $newResponse->withHeader('Content-Type', 'application/json');	

            }  
        }

        /////// AGREGAR ///////////////////////////////////////////////////////////////////////
        public function agregarUno(Request $request, Response $response, array $args): Response 
        {
            try
            {
                $obj = new Stdclass;
                $obj = json_decode($request->getParsedBody()['user']);                              
            
                $miUs = new Usuario();
                $miUs->correo = $obj->correo;
                $miUs->clave = $obj->clave;
                $miUs->nombre = $obj->nombre;
                $miUs->apellido = $obj->apellido;
                $miUs->perfil = $obj->perfil;
        
                $id_agregado = $miUs->instertarUsuario();

                //// TOMO EL ARCHIVO Y LO GUARDO EN FOTOS /////////////////////////////////////////////////////////
                $archivos = $request->getUploadedFiles();
                $destino = __DIR__ . "/../fotos/";
        
                $nombreAnterior = $archivos['foto']->getClientFilename();
                $extension = explode(".", $nombreAnterior);
        
                $extension = array_reverse($extension);

                $path = "./fotos/". $obj->correo . "_" . $id_agregado . $extension[0];
        
                $archivos['foto']->moveTo($destino . $obj->correo . "_" . $id_agregado . "." . $extension[0]);

                //////// MODIFICO MI USUARIO PARA AGREGARLE EL PATH DE LA FOTO /////////////////////////////////////
    
                $miUs->id = $id_agregado;
                $miUs->foto = $path;

                $objDelaRespuesta = new stdClass();
                $objDelaRespuesta->exito = $miUs->modificarUsuario();
                $objDelaRespuesta->mensaje = "Usuario Agregado";
    
                $newResponse = $response->withStatus(200, "OK");
                $newResponse->getBody()->write(json_encode($objDelaRespuesta));
            }
            catch(Error)
            {
                $objDelaRespuesta = new stdClass();
                $objDelaRespuesta->exito = false;
                $objDelaRespuesta->mensaje = "Ocurrio un Error";
    
                $newResponse = $response->withStatus(418, "ERROR");
                $newResponse->getBody()->write(json_encode($objDelaRespuesta));
            }
            finally
            {
                return $newResponse->withHeader('Content-Type', 'application/json');
            }

    
        }

        ////////////////////////// BORRAR UNO //////////////////////////////////////////////////
        public function BorrarUno(Request $request, Response $response, array $args): Response 
        {		 
            $id = $request['id'];
             
            $us = new Usuario();
            $us->id = $id;
             
            $cantidadDeBorrados = $us->borrarUsuario();
    
            $objDeLaRespuesta = new stdclass();
            $objDeLaRespuesta->cantidad = $cantidadDeBorrados;
            
            if($cantidadDeBorrados>0)
            {
                $objDeLaRespuesta->resultado = "...algo borró!!!";
            }
            else
            {
                $objDeLaRespuesta->resultado = "...no borró nada!!!";
            }
    
            $newResponse = $response->withStatus(200, "OK");
            $newResponse->getBody()->write(json_encode($objDeLaRespuesta));	
    
            return $newResponse->withHeader('Content-Type', 'application/json');
        }

        ///// MODIFICAR /////////////////////////////////////////////////////////////////////////
        public function ModificarUno(Request $request, Response $response, array $args): Response
        {
             try
             {
                 $obj = new Stdclass;
                 $obj = json_decode($request->getParsedBody()['user']);      
                 $obj = json_decode(($args["cadenaJson"]));   
 
                 $miUs = new Usuario();
                 $miUs->correo = $obj->correo;
                 $miUs->clave = $obj->clave;
                 $miUs->nombre = $obj->nombre;
                 $miUs->apellido = $obj->apellido;
                 $miUs->perfil = $obj->perfil;
                 $miUs->id = $obj->id;
 
                 unlink($obj->foto);
                 
                 $archivos = $request->getUploadedFiles();
                 $destino = __DIR__ . "/../fotos/";
                 
                 $nombreAnterior = $archivos['foto']->getClientFilename();
                 $extension = explode(".", $nombreAnterior);
                 
                 $path = "./fotos/". $obj->correo . "_" . $miUs->id . $extension[0];
 
                 $extension = array_reverse($extension);
     
                 $archivos['foto']->moveTo($destino . $obj->correo . "_" . $miUs->id . "." . $extension[0]);
 
                 $miUs->foto = $path;
                 
                 $resultado = $miUs->modificarUsuario();
                 
                 $objDelaRespuesta = new stdclass();
                 $objDelaRespuesta->resultado = $resultado;
                 $objDelaRespuesta->mensaje = "Usuario Modificado";

                 $newResponse = $response->withStatus(200, "OK");
                 $newResponse->getBody()->write(json_encode($objDelaRespuesta));
             }
             catch(Error)
             {
                $objDelaRespuesta = new stdClass();
                $objDelaRespuesta->exito = false;
                $objDelaRespuesta->mensaje = "Ocurrio un Error";
    
                $newResponse = $response->withStatus(418, "ERROR");
                $newResponse->getBody()->write(json_encode($objDelaRespuesta));
             }
             finally
             {
                return $newResponse->withHeader('Content-Type', 'application/json');		
             }  

              
        }
        

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////// FUNCIONES PROPIAS ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        
        //////////////// LISTAR TODOS LOS OBJ //////////////////////////////////////////////////
        public static function traerUsuarios()
        {
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            $consulta = $objetoAccesoDato->retornarConsulta("select id, correo as correo, clave as clave, nombre as nombre, apellido as apellido, perfil as perfil, foto as foto from usuarios");
            $consulta->execute();
            //NO ME RECONOCE USUARIO ASI QUE UTILIZO stdClass			
            return $consulta->fetchAll(PDO::FETCH_CLASS, "stdClass");		
        }

        /////// AGREGAR ///////////////////////////////////////////////////////////////////////
        public function instertarUsuario()
        {
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            $consulta = $objetoAccesoDato->retornarConsulta("INSERT into usuarios (correo,clave,nombre,apellido,perfil)values(:correo,:clave,:nombre,:apellido,:perfil)");
            $consulta->bindValue(':correo', $this->correo, PDO::PARAM_STR);
            $consulta->bindValue(':clave', $this->clave, PDO::PARAM_STR);
            $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
            $consulta->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
            $consulta->bindValue(':perfil', $this->perfil, PDO::PARAM_STR);
           // $consulta->bindValue(':foto', $this->foto, PDO::PARAM_STR);
            $consulta->execute();		
            return $objetoAccesoDato->retornarUltimoIdInsertado();
        }
        /////// MODIFICAR ///////////////////////////////////////////////////////////////////////
        public function modificarUsuario()
        {
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            $consulta = $objetoAccesoDato->retornarConsulta("
                    update usuarios 
                    set correo=:correo,
                    clave=:clave,
                    nombre=:nombre,
                    apellido=:apellido,
                    perfil=:perfil,
                    foto=:foto
                    WHERE id=:id");
            $consulta->bindValue(':id',$this->id, PDO::PARAM_INT);
            $consulta->bindValue(':correo', $this->correo, PDO::PARAM_STR);
            $consulta->bindValue(':clave', $this->clave, PDO::PARAM_STR);
            $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
            $consulta->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
            $consulta->bindValue(':perfil', $this->perfil, PDO::PARAM_STR);
            $consulta->bindValue(':foto', $this->foto, PDO::PARAM_STR);
            return $consulta->execute();
         }

         /// OBTENGO USUARIO POR CORREO Y CLAVE O SOLO POR ID (PASADO POR UN OBJETO)
         public static function ObtenerUsuario($porClaveCorreo = false, $dataObj)
         {
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            if($porClaveCorreo)
            { 
               
                $consulta = $objetoAccesoDato->retornarConsulta("select id, correo as correo, clave as clave, nombre as nombre, apellido as apellido, perfil as perfil, foto as foto
                from usuarios WHERE correo = '$dataObj->correo' AND clave = '$dataObj->clave'");
                $consulta->execute();
            }
            else
            {
                $consulta = $objetoAccesoDato->retornarConsulta("select id, correo as correo, clave as clave, nombre as nombre, apellido as apellido, perfil as perfil, foto as foto 
                from usuarios where id = $dataObj->id");
                $consulta->execute();
            }
          
             $UsuarioBuscado = $consulta->fetchObject('stdClass');
             return $UsuarioBuscado;
         }

         /// OBTENGO USUARIO POR CORREO
         public static function ObtenerUsuarioPorCorreo($correo)
         {
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            $consulta = $objetoAccesoDato->retornarConsulta("select id, correo as correo, clave as clave, nombre as nombre, apellido as apellido, perfil as perfil, foto as foto
            from usuarios WHERE correo = '$correo'");
            $consulta->execute();            
            $UsuarioBuscado = $consulta->fetchObject('stdClass');

            return $UsuarioBuscado;
         }

         /// BORRAR 
         public function borrarUsuario()
         {
             $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
             $consulta = $objetoAccesoDato->RetornarConsulta("delete from usuarios WHERE id=:id");	
             $consulta->bindValue(':id',$this->id, PDO::PARAM_INT);		
             $consulta->execute();
             return $consulta->rowCount();
         }

    }
}