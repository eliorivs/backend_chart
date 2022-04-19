<?php


/*function ChangePosition($value)
{

    $element = str_replace(',', '.', $value);  

    $response =   filter_var( $element, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    if($response=='-')
    {
      return NULL;
    }  
    else
    {
      return $response;
    }

}*/
function ChangePosition($value)
{

    $element = str_replace(',', '.', $value);  

    $response =   filter_var( $element, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    if(is_numeric($response))
    {
      
      return floatval($response);
    }  
    else
    {
      return NULL;
    }

}

 function GetNameEstacion($id_parameter)
{
      global $conn;
      $sql = "SELECT nombre_estacion FROM estaciones WHERE id_estacion='".$id_parameter."'";
      $result = $conn->query($sql);
      while ($row = $result->fetch_array(MYSQLI_ASSOC))
        {
           $nombre_estacion = $row['nombre_estacion'];
        }
       return $nombre_estacion;
}

function GetNameSerie($id_parameter)
{
      global $conn;
      $sql = "SELECT nombre_largo from parametros where id_parametro ='".$id_parameter."'";
      $result = $conn->query($sql);
      while ($row = $result->fetch_array(MYSQLI_ASSOC))
        {
           $nombre_serie = $row['nombre_largo'];
      
        }
        return $nombre_serie;
}
function GetUnidadSerie($id_parameter)
{
      global $conn;
      $sql = "SELECT x.unidad as unidad FROM unidades x, parametros y WHERE x.id_unidad=y.id_unidad AND y.id_parametro='".$id_parameter."'";
      $result = $conn->query($sql);
      while ($row = $result->fetch_array(MYSQLI_ASSOC))
      {
           $unidad = $row['unidad'];
      }
      return $unidad;
}
function GetColorArray($id_parameter)
{
      global $conn;
      $sql = "select name_color from colors  where id_colors='".$id_parameter."'";
      $result = $conn->query($sql);
      while ($row = $result->fetch_array(MYSQLI_ASSOC))
      {
           $color = $row['name_color'];
      }
      return $color;
}
function GetDecimals($id_parameter)
{
      global $conn;
       $sql = "SELECT decimal_nro from parametros where id_parametro ='".$id_parameter."'";
       $result = $conn->query($sql);
       while ($row = $result->fetch_array(MYSQLI_ASSOC))
        {
           $decimal = utf8_encode($row['decimal_nro']);
        }
       return $decimal;
}
function NotShowDisabled($parametro,$estacion,$fecha)
{

       global $conn;
       $sql = "select COUNT(*) AS cantidad FROM disabled_qaqc WHERE parametro='".$parametro."' AND estacion='".$estacion."' and fecha='".$fecha."'";
       $result = $conn->query($sql);
       while ($row = $result->fetch_array(MYSQLI_ASSOC))
        {
           $cantidad= $row['cantidad'];
        }
       return $cantidad;

}


function define_program($programa){
    
       global $conn;
       $sql = " select nombre_serie from programas where id_programa='".$programa."'";
       $result = $conn->query($sql);
       while ($row = $result->fetch_array(MYSQLI_ASSOC))
       {
           $nombre = $row['nombre_serie'];
       }
       return $nombre;
    
}
function search_program($programa){
    
       global $conn;
       $nombre ='';
       $sql = " select columna_programa as columna from programas where id_programa='".$programa."'";
       $result = $conn->query($sql);
       while ($row = $result->fetch_array(MYSQLI_ASSOC))
       {
           $nombre =" and ". $row['columna']."='1'";
       }
       //echo $nombre;
       return $nombre;

}


function GetFechaMax($id_parameter)
{
    global $conn;
    $sql = "SELECT min(fecha) AS datemin,max(fecha) AS datemax FROM muestras where estacion ='" . $id_parameter . "'";
    $result = $conn->query($sql);
    while ($row = $result->fetch_array(MYSQLI_ASSOC))
    {
        $datemin = $row['datemin'];
        $datemax = $row['datemax'];
    }
    $dates_serie['datemin'] = $datemin;
    $dates_serie['datemax'] = $datemax;
    return $dates_serie;
}
function GetValueMax($fecha, $estacion, $parametro)
{
    global $conn;
    $sql = "SELECT min(fecha) AS datemin,max(fecha) AS datemax FROM muestras WHERE estacion = 'LM-05';";
    $result = $conn->query($sql);
    while ($row = $result->fetch_array(MYSQLI_ASSOC))
    {
        $datemin = $row['datemin'];
        $datemax = $row['datemax'];
    }
    $dates_serie['datemin'] = $datemin;
    $dates_serie['datemax'] = $datemax;
    return $dates_serie;
}
function GetValueMin($fecha, $estacion, $parametro)
{
    global $conn;
    $sql = "SELECT min(fecha) AS datemin,max(fecha) AS datemax FROM muestras WHERE estacion = 'LM-05';";
    $result = $conn->query($sql);
    while ($row = $result->fetch_array(MYSQLI_ASSOC))
    {
        $datemin = $row['datemin'];
        $datemax = $row['datemax'];
    }
    $dates_serie['datemin'] = $datemin;
    $dates_serie['datemax'] = $datemax;
    return $dates_serie;
}
function ConvertMilis($time)
{
    $date = new DateTime($time);
    date_add($date, date_interval_create_from_date_string('1 day'));
    $timeArray = $date->getTimestamp() * 1000;
    return ($timeArray);
}


function ConvertVal($value)
{
    $data = $value * 1;
    return($data);
}


function verMesesMin($inicio,$fin,$parametro){


$start    = (new DateTime($inicio))->modify('first day of this month');
$end      = (new DateTime($fin))->modify('first day of next month');
$interval = DateInterval::createFromDateString('1 month');
$period   = new DatePeriod($start, $interval, $end);


foreach ($period as $dt) {

    $valor = GetDatoEstacionMin($parametro,$dt->format("m"));
    $serie[] = array(ConvertMilis($dt->format("Y-m-d")),ConvertVal($valor));
 }
 return $serie;
}

function verMesesMax($inicio,$fin,$parametro)
{
 $start    = (new DateTime($inicio))->modify('first day of this month');
 $end      = (new DateTime($fin))->modify('first day of next month');
 $interval = DateInterval::createFromDateString('1 month');
 $period   = new DatePeriod($start, $interval, $end);


foreach ($period as $dt) {

    $valor = GetDatoEstacionMax($parametro,$dt->format("m"));
    $serie[] = array(ConvertMilis($dt->format("Y-m-d")),ConvertVal($valor));
 }
 return $serie;
}



function GetDatoEstacionMin($parametro,$mes){

      global $conn;
      $sql = "select verano_min,oton_min,invierno_min,primavera_min from normas_estacional where id_parametro='" . $parametro . "'";
      $result = $conn->query($sql);
      while ($row = $result->fetch_array(MYSQLI_ASSOC))
      {
                $verano = $row['verano_min'];
                $otono = $row['oton_min'];
                $invierno = $row['invierno_min'];
                $primavera = $row['primavera_min'];
      }
      if(($mes=='12')||($mes=='01')||($mes=='02'))
      {
        return $verano;
      }
       if(($mes=='03')||($mes=='04')||($mes=='05'))
      {
        return $otono;
      }
       if(($mes=='06')||($mes=='07')||($mes=='08'))
      {
        return $invierno;
      }
       if(($mes=='09')||($mes=='10')||($mes=='11'))
      {
        return $primavera;
      }


}

function GetDatoEstacionMax($parametro,$mes){

      global $conn;
      $sql = "select verano_max,oton_max,invierno_max,primavera_max from normas_estacional where id_parametro='" . $parametro . "'";
      $result = $conn->query($sql);
      while ($row = $result->fetch_array(MYSQLI_ASSOC))
      {
                $verano = $row['verano_max'];
                $otono = $row['oton_max'];
                $invierno = $row['invierno_max'];
                $primavera = $row['primavera_max'];
      }
      if(($mes=='12')||($mes=='01')||($mes=='02'))
      {
        return $verano;
      }
       if(($mes=='03')||($mes=='04')||($mes=='05'))
      {
        return $otono;
      }
       if(($mes=='06')||($mes=='07')||($mes=='08'))
      {
        return $invierno;
      }
       if(($mes=='09')||($mes=='10')||($mes=='11'))
      {
        return $primavera;
      }

}

function DateValueMin($estacion)
{
    global $conn;
    $sql = "SELECT min(fecha) AS datemin FROM muestras WHERE estacion = '".$estacion."';";
    $result = $conn->query($sql);
    while ($row = $result->fetch_array(MYSQLI_ASSOC))
    {
        $datemin = $row['datemin'];
       
    }
    $datemin = $datemin;

    return $datemin;
}
function DateValueMinP($estacion,$parametro)
{
    global $conn;
    $sql ="select Min(fecha) FROM muestras WHERE parametro_".$parametro." IS NOT null and estacion='".$estacion."'";
    //$sql = "SELECT min(fecha) AS datemin FROM muestras WHERE estacion = '".$estacion."';";
    $result = $conn->query($sql);
    while ($row = $result->fetch_array(MYSQLI_ASSOC))
    {
        $datemin = $row['datemin'];
       
    }
    $datemin = $datemin;

    return $datemin;
}
function DateValueMaxP($estacion,$parametro)
{
    global $conn;
    $sql ="select Min(fecha) FROM muestras WHERE parametro_".$parametro." IS NOT null and estacion='".$estacion."'";
    //$sql = "SELECT max(fecha) AS datemax FROM muestras WHERE estacion = '".$estacion."';";
    $result = $conn->query($sql);
    while ($row = $result->fetch_array(MYSQLI_ASSOC))
    {
        $datemax = $row['datemax'];
       
    }
    $datemax = $datemax;

    return $datemax;
}

function DateValueMax($estacion)
{
    global $conn;
    $sql = "SELECT max(fecha) AS datemax FROM muestras WHERE estacion = '".$estacion."';";
    $result = $conn->query($sql);
    while ($row = $result->fetch_array(MYSQLI_ASSOC))
    {
        $datemax = $row['datemax'];
       
    }
    $datemax = $datemax;

    return $datemax;
}

  



?>