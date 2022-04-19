<?php
error_reporting(0);
header("Content-Type: application/json; charset=UTF-8");
require ('../conexion/conexion.php');
require ('utils.php');
mysqli_set_charset($conn, "utf8");

$minusalldata = [];

$estaciones = $_POST['estaciones'];
$parametros = $_POST['parametros'];
$normas = $_POST['normas'];
$hitos = $_POST['hitos'];
$programas = $_POST['programas'];

/*****************************************/
$resultado = primary_object($estaciones, $parametros, $normas, $hitos,$programas);
$rules = SetNormas($normas,$parametros);
$series = GetNormas($rules, $parametros, $estaciones, $resultado, $hitos);
$plotlines = getHitos($hitos);
foreach ($all_min as $key => $value)
if (empty($value)) unset($all_min[$key]);

$response = array('nameseries'=>$nameserie,
                  'series' => $series,
                  'total'=>(count($series)-count($rules)),
                  'plotlines' => $plotlines, 
                  'min'  => ConvertMilis(min($all_min))-(86400000*15), 
                  'minimos' => ($all_min));

print json_encode($response);
/*****************************************/
function primary_object($estaciones, $parametros, $normas, $hitos,$programas)
{
	global $conn;
	global $nameserie;
	$j = 0;
	$series = 0;
	$arr = array();
	
	$resultado = array();
	if ($programas == '') {
		$programas = [' '];
	}
	$color_counter = 0;

	foreach ($estaciones as $estacion) {
		$pto = GetNameEstacion($estacion);

		foreach ($parametros as $parametro) {
			$elemento = $parametro;
			$parametro = 'parametro_' . $parametro;

			foreach ($programas as $programa) {
				$busqueda = search_program($programa);
				$define_program = define_program($programa);
				$color_counter++;

				$j = 0;
				unset($arr);
				$series++;
			    $sql = "select count(*) as cantidad  from muestras  where estacion ='" . $pto . "' and " . $parametro . " !='' and " . $parametro . " !='-' " . $busqueda . " and estatus='1' and ".$parametro." !='SD' and  ".$parametro." is not null";
				$result = $conn->query($sql);
				while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
					$cantidad = $row['cantidad'];
				}
				if ($cantidad > 0) {
			         $sql = " select " . $parametro . " as parametro, fecha as date from muestras where estacion ='" . $pto . "' and " . $parametro . " !='' and " . $parametro . " !='-' " . $busqueda . " and estatus='1' and ".$parametro." !='SD' and ".$parametro." is not null order by date";
                     $result = $conn->query($sql);

					while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
						if ($j == 0) {
							if ($define_program == '') {
								$arr['name'] = GetNameSerie($elemento) . " [" . $pto."]";
							} else {
								$arr['name'] = GetNameSerie($elemento) . " [" .$pto."-".$define_program . "]";
							}
					
							$time = $row['date'];
							if ($color_counter <= 18) {
								$arr['color'] = GetColorArray($color_counter);
							} else {
								$arr['color'] = GetColorArray(rand(1, 18));
							}
							$j++;
						}

						$time = $row['date'];
						$date = new DateTime($time);
						$timeArray = $date->getTimestamp() * 1000;
						$band= NotShowDisabled($parametro,$pto,$time);

						if($band=='0')
						{
					
                            $date = new DateTime($time);
                            $timeArray = $date->getTimestamp() * 1000;
                            $arr['data'][] = [$timeArray, ChangePosition($row['parametro']),$band];					
  
					   }
					   else
					   {
					   
                            $date = new DateTime($time);
                            $timeArray = $date->getTimestamp() * 1000;
					
					   }
                       $arr['tooltip'] = array('valueDecimals' => GetDecimals($elemento), 'valueSuffix' => " " . GetUnidadSerie($elemento));
							
					

					}
					array_push($resultado, $arr);
				}
			}
		}
	}
    $nameserie = GetNameSerie($elemento).'['.trim(GetUnidadSerie($elemento)).']';
	return ($resultado);
}

function DateMinMax($estaciones, $parametros)
{
	foreach ($estaciones as $estacion) {

        foreach($parametros as $parametro)
        {
            $datemin[] = DateValueMinX(GetNameEstacion($estacion),$parametro);
	     	$datemax[] = DateValueMax(GetNameEstacion($estacion),$parametro);
        }
		
	}
    $datemin = array_filter($datemin, "myFilter"); 
	$inicio = min($datemin);  
	$fin   = max($datemax);
	global $all_min;
	$all_min = array();
	array_push($all_min, $inicio);
	$data = array('min' => $inicio, 'max' => $fin);
	return ($data);
}


function myFilter($var){
    return ($var !== NULL && $var !== FALSE && $var !== "");
}

function DateValueMinX($estacion,$parametro)
{
    global $conn;
    $sql = "SELECT min(fecha) AS datemin FROM muestras WHERE estacion = '".$estacion."' AND parametro_".$parametro." != '';";
    $result = $conn->query($sql);
    while ($row = $result->fetch_array(MYSQLI_ASSOC))
    {   
        $datemin = $row['datemin'];       
    }
    return $datemin;
}

function SetNormas($normas,$parametros)
{
	global $conn;

	$series = ['maxima', 'minima'];
	$alias = ['max', 'min'];
	$color = ['#ff0000', '#C6C600'];
	$i = 0;
	
	foreach ($normas as $norma) {
		
		$i = 0;
		foreach ($series as $serie) {
			$sql = "select " . $serie . " as serie , norma_description  from normas where id_norma='" . $norma . "'";
            $result = $conn->query($sql);
			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
				$rules[] = array('color' => $color[$i], 'serie' => $row['serie'], 'alias' => $alias[$i], 'description' => $row['norma_description']);
				$i++;
			}
		}
	}
	
	return ($rules);
}

function SetColor($alias,$parametro,$color)
{
	if(($parametro=='71')||($parametro=='43'))
	{
		if($alias=='min')
		{
          return ('navy');
		}
		if($alias=='max')
		{
			return ('red');
		}		
	}
	else
	{
		return $color;
	}
}

function GetNormas($normas, $parametros, $estaciones, $resultado, $hitos)
{
	global $conn;
	global $all_min;

	$fechahitomenor = getMenorHito($hitos);
	$fechahitomayor = getMaximoHito($hitos);    
	$MinMax = DateMinMax($estaciones, $parametros);
	$inicio = $MinMax['min'];
	$fin   = $MinMax['max'];
	$minimos = [$fechahitomenor, $inicio];
	$maximos = [$fechahitomayor, $fin];
	foreach ($minimos as $key => $value)
	if (empty($value)) unset($minimos[$key]);

	foreach ($maximos as $key => $value)
	if (empty($value)) unset($maximos[$key]);

	array_push($all_min, min($minimos));

	$fechas_serie = array('datemin' => min($minimos), 'datemax' => max($maximos));
	$plotline = -1;

	foreach ($parametros as $parametro) {
		$unidad = GetUnidadSerie($parametro);
		$parametro_result = GetNameSerie($parametro);

		foreach ($normas as $norma) {
		    
			$sql = "select " . $norma['serie'] . " as value  from normas_config where id_parameter='" . $parametro . "'";
			$result = $conn->query($sql);
			
			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			
				if ($row['value'] != '') {
					
					$plotline++;
					$forserie = array(
					
						'name' => "".$norma['description'] . " " . $norma['alias'] . "[" . $row['value'] . " " . GetUnidadSerie($parametro) . "] ",
						'marker'=>array('enabled'=>false),
						'lineWidth' => '1.5',
				     	'color' =>  SetColor($norma['alias'],$parametro, $norma['color']),
						'tooltip' => array('valueDecimals' => 2, 'valueSuffix' => GetUnidadSerie($parametro)),
						'dashStyle' => 'shortdash',
						'parametro' => $parametro,
						'fechas' => array($fechas_serie['datemin'], $fechas_serie['datemax']),
						'data' => array(
							array(
								ConvertMilis($fechas_serie['datemin']),
								$row['value'] * 1
							),
							array(
								ConvertMilis($fechas_serie['datemax']),
								$row['value'] * 1
							)
						)

					);

					array_push($resultado, $forserie);
				}
			}
		}
	}
	return $resultado;
}



function getMenorHito($hitos)
{
	global $conn;
	global $all_min;
	if (count($hitos) > 0) {
		foreach ($hitos as $hito) {
			$sql = "select color, comentario, fecha, id_hito, width from hitos where id_hito='" . $hito . "'";
			$result = $conn->query($sql);
			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
				$datemin[] = $row['fecha'];
			}
		}
		if (min($datemin) != '') {
			array_push($all_min, min($datemin));
		}

		return min($datemin);
	} else {
		return '';
	}
}

function getMaximoHito($hitos)
{
	global $conn;

	if (count($hitos) > 0) {
		foreach ($hitos as $hito) {
			$sql = "select color, comentario, fecha, id_hito, width from hitos where id_hito='" . $hito . "'";
			$result = $conn->query($sql);
			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
				$datemax[] = $row['fecha'];
			}
		}

		return max($datemax);
	} else {
		return '';
	}
}

function getHitos($hitos)
{
	global $conn;
	$resultado = [];

	foreach ($hitos as $hito) {
		$sql = "select color, comentario, fecha, id_hito, width from hitos where id_hito='" . $hito . "'";

		$result = $conn->query($sql);

		while ($row = $result->fetch_array(MYSQLI_ASSOC))
        {
			
			$color = $row['color'];
			$comentario = $row['comentario'];
			$fecha = $row['fecha'];
			$id_hito = $row['id_hito'];
			$width = $row['width'];
			$date = DateTime::createFromFormat("Y-m-d", $fecha);
			$time = $row['fecha'];
			$date = new DateTime($time);
			date_add($date, date_interval_create_from_date_string('1 day'));
			$timeArray = $date->getTimestamp() * 1000;
			$resultado[] = array(
				'value' => $timeArray,
				'width' => $row['width'],
				'color' => $color,
				'dashStyle' => 'dash',
				'year' => $date->format("Y"),
				'month' => $date->format("m"),
				'day' => $date->format("d"),
				'label' => array(
					'text' => $comentario,
					'rotation' => 90,
					'textAlign' => 'left', 'style' => array('font' => '11px "Poppins"')
				)
			);  
        }
	}
	return ($resultado);
}
