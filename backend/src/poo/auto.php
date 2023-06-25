<?php
namespace Citroneta
{

    use AccesoDatos;
    use Error;
    use ISlimeable;
    use PDO;
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use stdClass;

    require_once "accesodatos.php";
    class Auto implements ISlimeable
    {
        public int $id;
        public string $color;
        public string $marca;
        public float $precio;
        public string $modelo;    

         ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////// IMPLEMENTACIONES DE INTERFAZ ////////////////////////////////////////////////////////////////////////////////////////////////////
       
        //////////////// LISTAR TODOS LOS OBJ //////////////////////////////////////////////////
        public function TraerTodos(Request $request, Response $response, array $args): Response 
        {
            try
            {
                $usuarios = Auto::traerAutos();

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
        public function AgregarUno(Request $request, Response $response, array $args): Response 
        {
            try
            {
                $obj = new Stdclass;
                $obj = json_decode($request->getParsedBody()['auto']);                              
            
                $miAuto = new Auto();
                $miAuto->color = $obj->color;
                $miAuto->marca = $obj->marca;
                $miAuto->precio = $obj->precio;
                $miAuto->modelo = $obj->modelo;
        
                $objDelaRespuesta = new stdClass();
                $objDelaRespuesta->exito = 0 != $miAuto->instertarAuto();
                $objDelaRespuesta->mensaje = "Auto Agregado";
    
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
               $objDeLaRespuesta = new stdclass();
               $objDeLaRespuesta->exito = false;

                $id = $args['id_auto'];
                $auto = new Auto();
                $auto->id = $id;
                 
                $cantidadDeBorrados = $auto->borrarAuto();
        
                
                if($cantidadDeBorrados>0)
                {
                    $objDeLaRespuesta->exito = true;
                    $objDeLaRespuesta->mensaje = "Se Borro el vehiculo";
                    $newResponse = $response->withStatus(200, "OK");
                    $newResponse->getBody()->write(json_encode($objDeLaRespuesta));	
                }
                else
                {
                    $objDeLaRespuesta->mensaje = "Error al borrar, id invalido";
                    $newResponse = $response->withStatus(418);
                    $newResponse->getBody()->write(json_encode($objDeLaRespuesta));	
                }             
  
              return $newResponse->withHeader('Content-Type', 'application/json');
          }

          ///// MODIFICAR /////////////////////////////////////////////////////////////////////////
          public function ModificarUno(Request $request, Response $response, array $args): Response
          {
              
                $obj = json_decode($args["json_auto"]);
                $id = $args["id_auto"];
      
                $miAuto = new Auto();
                $miAuto->id =  $id;
                $miAuto->color = $obj->color;
                $miAuto->marca = $obj->marca;
                $miAuto->precio = $obj->precio;
                $miAuto->modelo = $obj->modelo;

                $resultado = $miAuto->modificarAuto();
                
                $objDelaRespuesta = new stdclass();
                $objDelaRespuesta->resultado = $resultado;
                $objDelaRespuesta->mensaje = "Registro Modificado";

                $newResponse = $response->withStatus(200, "OK");
                $newResponse->getBody()->write(json_encode($objDelaRespuesta));

                return $newResponse->withHeader('Content-Type', 'application/json');		
           }

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ///////// FUNCIONES PROPIAS ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        
        //////////////// LISTAR TODOS LOS OBJ //////////////////////////////////////////////////
        public static function traerAutos()
        {
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            $consulta = $objetoAccesoDato->retornarConsulta("select id, color as color, marca as marca, precio as precio, modelo as modelo from autos");
            $consulta->execute();
            //NO ME RECONOCE AUTO ASI QUE UTILIZO stdClass			
            return $consulta->fetchAll(PDO::FETCH_CLASS, "stdClass");		
        }

        /////// AGREGAR ///////////////////////////////////////////////////////////////////////
        public function instertarAuto()
        {
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            $consulta = $objetoAccesoDato->retornarConsulta("INSERT into autos (color,marca,precio,modelo)values(:color,:marca,:precio,:modelo)");
            $consulta->bindValue(':color', $this->color, PDO::PARAM_STR);
            $consulta->bindValue(':marca', $this->marca, PDO::PARAM_STR);
            $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
            $consulta->bindValue(':modelo', $this->modelo, PDO::PARAM_STR);
            $consulta->execute();		
            return $objetoAccesoDato->retornarUltimoIdInsertado();
        }
        /////// MODIFICAR ///////////////////////////////////////////////////////////////////////
        public function modificarAuto()
        {
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            $consulta = $objetoAccesoDato->retornarConsulta("
                    update autos 
                    set color=:color,
                    marca=:marca,
                    precio=:precio,
                    modelo=:modelo
                    where id=:id");
            $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
            $consulta->bindValue(':color', $this->color, PDO::PARAM_STR);
            $consulta->bindValue(':marca', $this->marca, PDO::PARAM_STR);
            $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
            $consulta->bindValue(':modelo', $this->modelo, PDO::PARAM_STR);
            return $consulta->execute();
         }

        /// BORRAR 
        public function borrarAuto()
        {
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            $consulta = $objetoAccesoDato->RetornarConsulta("delete from autos WHERE id=:id");	
            $consulta->bindValue(':id',$this->id, PDO::PARAM_INT);		
            $consulta->execute();
            return $consulta->rowCount();
        }

        
    }

}