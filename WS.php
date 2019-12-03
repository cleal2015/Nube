<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');

// BODY form-data keys (IPServidor,Usuario,Contraseña,Campaña,Telefono,Folio)
if ($_SERVER['REQUEST_METHOD'] == 'POST')	
{
	$ip = isset($_POST['IPServidor']) ? $_POST['IPServidor'] : null;
	$user = isset($_POST['Usuario']) ? $_POST['Usuario'] : null;
	$pass = isset($_POST['Contraseña']) ? $_POST['Contraseña'] : null;
	$campaign_id = isset($_POST['Campaña']) ? $_POST['Campaña'] : null;
	$number_phone = isset($_POST['Telefono']) ? $_POST['Telefono'] : null;
	$folio = isset($_POST['Folio']) ? $_POST['Folio'] : null;
	
	$result = "";
	$errores = [];
	$hayError = false;

	
	if (!validar_requerido($ip)) 
	{
		$errores[] = 'El parametro IPServidor es obligatorio.';
		$hayError = true;
	}
	if (!validar_requerido($user)) 
	{
		$errores[] = 'El parametro Usuario es obligatorio.';
		$hayError = true;
	}
	if (!validar_requerido($pass)) 
	{
		$errores[] = 'El parametro Contraseña es obligatorio.';
		$hayError = true;
	}
	if (!validar_requerido($campaign_id)) 
	{
		$errores[] = 'El parametro Campaña es obligatorio.';
		$hayError = true;
	}
	if (!validar_requerido($number_phone)) 
	{
		$errores[] = 'El parametro Teléfono es obligatorio.';
		$hayError = true;
	}
	if (!validar_requerido($folio)) 
	{
		$errores[] = 'El parametro Folio es obligatorio.';
		$hayError = true;
	}

	
	if (!validar_entero($campaign_id)) 
	{
		$errores[] = 'El parametro Campaña debe ser un número.';
		$hayError = true;
	}
	if ((strlen($number_phone) != 10)&&(strlen($number_phone) != 0)) 
	{
		$errores[] = 'El parametro Teléfono debe ser de solo 10 digitos.';
		$hayError = true;
	}
	if (validarSiNumero($number_phone)) 
	{
		$errores[] = 'El parametro Teléfono debe contener solo números.';
		$hayError = true;
	}	
	if (validar_alfanumerico($folio)) 
	{
		$errores[] = 'El parametro Folio debe contener solamente valores alfanumericos.';
		$hayError = true;
	}
	
	
	if ($hayError)
	{
		header("HTTP/1.1 406 NOT ACCEPTABLE");
		echo json_encode($errores);
	}
	else
	{
		$mysqli = new mysqli($ip,$user,$pass,'call_center');
		$mysqli->set_charset("utf8");

		if (mysqli_connect_errno()) 
		{
			$result = "Connect failed";
			header("HTTP/1.1 500 INTERNAL SERVER ERROR");
			echo json_encode($result);
			exit();
		}
		else
		{
			if(!ExistCampaign($campaign_id,$mysqli))
			{
				$result = "El numero de campania [".$campaign_id. "] no existe";
				header("HTTP/1.1 404 NO FOUND");
				echo json_encode($result);
				exit();
			}
			else
			{
				$idnewcall = InsertCalls($campaign_id, $number_phone,$mysqli);
				InsertCallAttribute($idnewcall, $folio,$mysqli);
				$result = "New Records Successfully Created";		
				header("HTTP/1.1 201 OK");
				echo json_encode($result);
				exit();
			}			
		}
		$mysqli->close();	
	}
}


function ExistCampaign($id_campaign,$mysqli)
{	
	$ExistCampaign = false;
	if(!empty($id_campaign))
	{
		$sql = "SELECT * FROM campaign Where id='". $id_campaign ."'";
		$result = $mysqli->query($sql);
		if($result->num_rows > 0) 
		{
			$ExistCampaign = true;		
		}
	}
    return $ExistCampaign;
} 
function InsertCalls($id_campaign, $phone,$mysqli)
{	
	$NewId = 0;
    $sql = "INSERT INTO calls (id_campaign, phone) VALUES ('$id_campaign', '$phone')";
	
	if($mysqli->query($sql) === TRUE) 
	{
		$NewId = $mysqli->insert_id;		
    } 
    return $NewId;
} 
function InsertCallAttribute($id_call, $valfolio,$mysqli)
{	
    $sql = "INSERT INTO call_attribute (id_call, columna, value, column_number) VALUES ('$id_call', 'PK', '$valfolio', 1)";
	if($mysqli->query($sql) === TRUE) 
	{
    } 
} 
function validar_requerido(string $texto): bool
{
	return !(trim($texto) == '');
}
function validar_entero(string $numero): bool
{
	return (filter_var($numero, FILTER_VALIDATE_INT) === FALSE) ? False : True;
}
function validarSiNumero(string $texto): bool
{
	$patron_texto = "/^[[:digit:]]+$/";
	return !(preg_match($patron_texto, $texto));	
}
function validar_alfanumerico(string $texto): bool
{
	return !(ctype_alnum($texto));
}
  
header("HTTP/1.1 400 Bad Request");

?>