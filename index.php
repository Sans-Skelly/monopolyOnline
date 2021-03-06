<?php
	$server = true;
	include "funciones/conexion.php";
	include "funciones/funciones.php";
	session_start();
		
	/*	Si hay una invitación */
	if(isset($_GET['invitacion']) && cookie("inv") == 0 && !isset($_SESSION['id_usuario'])){
		
		dejar_cookie("inv", base64_decode($_GET['invitacion']));
		header("Location: http://login.imperdiblesoft.com");
		exit();
	}
	
	/*	Obtiene la versión del sistema */
	$sql = "SELECT * FROM mon_changelog ORDER BY version DESC LIMIT 1";
	$resul = mysqli_query($conexion, $sql);
	if(!$resul){
		$error = mysqli_error($conexion);
		include "error.php";
		exit();
	}
	$version = array();
	while($fila = mysqli_fetch_array($resul)){
		$version = $fila['version'];
	}
	
	/*	(Inicio de sesión) Si hay cookies guardadas */
	if(false || (cookie("usr") == 1 && cookie("pwd") == 1)){
		$temp['id_usuario'] = leer_cookie("usr");
		$temp['password'] = leer_cookie("pwd");
		$temp['origen'] = leer_cookie("orig");
		/*
		$temp['id_usuario'] = 1;
		$temp['password'] = md5("password");
		$temp['origen'] = "imperdible";
		*/
		
		/* Si las cookies están vacías */
		if($temp['id_usuario'] == "" || $temp['password'] == ""){
			unset($_SESSION);
			session_destroy();
			
			include "vistas/vista_inicio2.php";
			exit();
		}
		
		/*	Si hay sesion iniciada */
		else if(isset($_SESSION['id_usuario'])){
			
			$sql = "SELECT * FROM usuarios WHERE id_usuario='".$temp['id_usuario']."'";
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$error = mysqli_error($conexion);
				include "error.php";
				exit();
			}
			$usuario = array();
			while ($fila = mysqli_fetch_array($resul)){
				$usuario = limpiar_array($fila);
			}
			/*	Inicia sesión */
			foreach($usuario as $clave => $valor){
				$_SESSION[$clave] = $valor;
			}
			
			/* Actualiza el dato de la última conexión */
			$sql = "UPDATE usuarios SET
					online = 1,
					ip_ultima_conexion = '".$_SERVER['REMOTE_ADDR']."',
					fecha_ultima_conexion = now()
				WHERE id_usuario='".$_SESSION['id_usuario']."'";
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$error = mysqli_error($conexion);
				include "error.php";
				exit();
			}
				
			unset($_SESSION['redireccionar']);
		}
		
		/*	Si no hay sesion iniciada */
		else{
			
			$sql = "SELECT * FROM usuarios WHERE id_usuario='".$temp['id_usuario']."'";
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$error = mysqli_error($conexion);
				include "error.php";
				exit();
			}
			$usuario = array();
			while ($fila = mysqli_fetch_array($resul)){
				$usuario = limpiar_array($fila);
			}

			/*	Si coincide el password */
			if($usuario['password'] == $temp['password']){

				/*	Actualiza las cookies */
				dejar_cookie("usr", $usuario['id_usuario']);
				dejar_cookie("pwd", $usuario['password']);
				dejar_cookie("orig", $temp['origen']);
				
				/*	Inicia sesión */
				foreach($usuario as $clave => $valor){
					$_SESSION[$clave] = $valor;
				}
				
				/* Actualiza el dato de la última conexión */
				$sql = "UPDATE usuarios SET
						online = 1,
						ip_ultima_conexion = '".$_SERVER['REMOTE_ADDR']."',
						fecha_ultima_conexion = now()
					WHERE email='".$_SESSION['email']."'";
				$resul = mysqli_query($conexion, $sql);
				if(!$resul){
					$error = mysqli_error($conexion);
					include "error.php";
					exit();
				}
				
				/*	Actualiza el tipo de cuenta */
				if($usuario['tipo_cuenta'] != '0' && $usuario['tipo_cuenta'] != '' && $usuario['tipo_cuenta'] != 'NULL'){
					if($temp['origen'] == "google"){
						$sql = "UPDATE usuarios SET tipo_cuenta = '4' WHERE email='".$_SESSION['email']."'";
						$_SESSION['tipo_cuenta'] = '4';
					}
					else if($temp['origen'] == "twitter"){
						$sql = "UPDATE usuarios SET tipo_cuenta = '3' WHERE email='".$_SESSION['email']."'";
						$_SESSION['tipo_cuenta'] = '3';
					}
					else if($temp['origen'] == "facebook"){
						$sql = "UPDATE usuarios SET tipo_cuenta = '2' WHERE email='".$_SESSION['email']."'";
						$_SESSION['tipo_cuenta'] = '2';
					}else{
						$sql = "UPDATE usuarios SET tipo_cuenta = '1' WHERE email='".$_SESSION['email']."'";
						$_SESSION['tipo_cuenta'] = '1';
					}
					$resul = mysqli_query($conexion, $sql);
					if(!$resul){
						$error = mysqli_error($conexion);
						include "error.php";
						exit();
					}
				}
				
				unset($_SESSION['redireccionar']);
			}
		}
		
		/* Carga la configuración básica */
		include "funciones/configuracion.php";
	}
	
	/* No hay cookies guardadas */
	else{
	
		unset($_SESSION);
		session_destroy();
	}
		
	/*	Comprueba si está baneado */
	if(isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']) && $_SESSION['id_usuario'] != ""){
		
		$sql = "SELECT * FROM mon_baneados WHERE (id_usuario = '".$_SESSION['id_usuario']."' OR id_usuario = '".$_SERVER['REMOTE_ADDR']."')";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$error = mysqli_error($conexion);
			include "error.php";
			exit();
		}
		while($fila = mysqli_fetch_array($resul)){
			$ban = limpiar_array($fila);
		}
		
		if(isset($ban) && !empty($ban) && date("Y-m-d H:i:s") <= $ban["fin"]){
		
			if(preg_match("/\./", $ban['id_usuario'])){
				$error = "Lo sentimos, tu dirección IP ha sido baneada hasta el ".formatear_fecha($ban['fin'])." por el siguiente motivo:<br />";
			}else{
				$error = "Lo sentimos, tu cuenta ha sido baneada hasta el ".formatear_fecha($ban['fin'])." por el siguiente motivo:<br />";
			}
			
			$error .= "<< ".$ban['motivo']." >><br />";
			$error .= "<br />";
			$error .= "Puedes visitar el <a href='/foro/' >foro</a> para cualquier reclamación.";
			
			include "error.php";
			exit();
		}
		
		else{
		
			/* Comprueba si el usuario está ya sentado en una mesa. */
			$sql = "SELECT id_mesa FROM mon_mesas 
				WHERE jugador1='".$_SESSION['id_usuario']."' 
				OR jugador2='".$_SESSION['id_usuario']."' 
				OR jugador3='".$_SESSION['id_usuario']."' 
				OR jugador4='".$_SESSION['id_usuario']."'";
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$error = mysqli_error($conexion);
				include "error.php";
				exit();
			}
			while($fila = mysqli_fetch_array($resul)){
				$mesa_actual = $fila['id_mesa'];
				$mimesa = $fila['id_mesa'];
			}
		}
	}
	
	if(isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']) && $_SESSION['id_usuario'] != "" && isset($_GET['action']) && $_GET['action'] != "inicio" && $_SESSION['nombre'] == "usuario"){
		
		header("Location: /");
		
	}
	
	/*	Menú usuario - Inicio */
	if(!isset($_GET['action']) || (isset($_GET['action']) && $_GET['action'] == "inicio")){
		
		/*	Obtiene todos los cambios changelog */
		$sql = "SELECT * FROM mon_changelog ORDER BY version DESC LIMIT 1";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$error = mysqli_error($conexion);
			include "error.php";
			exit();
		}
		$changelogs = array();
		while($fila = mysqli_fetch_array($resul)){
			$changelog = limpiar_array($fila);
		}
		
		include "vistas/vista_inicio2.php";
		exit();
	}
	
	/*	Menú usuario - Descargas */
	else if(isset($_GET['action']) && $_GET['action'] == "descargas"){
		
		include "vistas/vista_descargas.php";
		exit();
	}
	
	/*	Menú usuario - Acerca de */
	else if(isset($_GET['action']) && $_GET['action'] == "acercade"){
		
		/*	Obtiene todos los cambios changelog */
		$sql = "SELECT * FROM mon_changelog ORDER BY version DESC";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$error = mysqli_error($conexion);
			include "error.php";
			exit();
		}
		$changelogs = array();
		while($fila = mysqli_fetch_array($resul)){
			$changelogs[] = limpiar_array($fila);
		}
		
		include "vistas/vista_about.php";
		exit();
	}
	
	/*	Menú usuario - Staff */
	else if(isset($_GET['action']) && $_GET['action'] == "staff"){
		
		/*	Obtiene todos los Administradores */
		$sql = "SELECT id_usuario, nombre, administrador, (SELECT codigo FROM tipos_admin WHERE id_tipos_admin=administrador) as rango, (SELECT color FROM tipos_admin WHERE id_tipos_admin=administrador) as color FROM usuarios WHERE administrador='3' ORDER BY id_usuario DESC";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$error = mysqli_error($conexion);
			include "error.php";
			exit();
		}
		$administradores = array();
		while($fila = mysqli_fetch_array($resul)){
			$administradores[] = limpiar_array($fila);
		}
		
		/*	Obtiene todos los GMs */
		$sql = "SELECT id_usuario, nombre, administrador, (SELECT codigo FROM tipos_admin WHERE id_tipos_admin=administrador) as rango, (SELECT color FROM tipos_admin WHERE id_tipos_admin=administrador) as color FROM usuarios WHERE administrador='2' ORDER BY id_usuario DESC";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$error = mysqli_error($conexion);
			include "error.php";
			exit();
		}
		$gms = array();
		while($fila = mysqli_fetch_array($resul)){
			$gms[] = limpiar_array($fila);
		}
		
		/*	Obtiene todos los Moderadores */
		$sql = "SELECT id_usuario, nombre, administrador, (SELECT codigo FROM tipos_admin WHERE id_tipos_admin=administrador) as rango, (SELECT color FROM tipos_admin WHERE id_tipos_admin=administrador) as color FROM usuarios WHERE administrador='1' ORDER BY id_usuario DESC";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$error = mysqli_error($conexion);
			include "error.php";
			exit();
		}
		$moderadores = array();
		while($fila = mysqli_fetch_array($resul)){
			$moderadores[] = limpiar_array($fila);
		}
		
		include "vistas/vista_staff.php";
		exit();
	}
	
	/*	Menú usuario - Clasificación */
	else if(isset($_SESSION['id_usuario']) && isset($_GET['action']) && $_GET['action'] == "clasificacion"){
		
		/* Obtengo los datos de los usuarios */
		$sql = "SELECT 
				id_usuario,
				id_usuario as usuario,
				nombre,
				tipo_cuenta,
				administrador,
				online,
				ausente,
				(SELECT count(*) FROM mon_victorias WHERE id_usuario=usuario) as victorias,
				(SELECT count(*) FROM mon_derrotas WHERE id_usuario=usuario) as derrotas,
				(SELECT count(*) FROM mon_desconexiones WHERE id_usuario=usuario) as desconexiones,
				((SELECT count(*) FROM mon_victorias WHERE id_usuario=usuario)-((SELECT count(*) FROM mon_derrotas WHERE id_usuario=usuario)*0.3)-((SELECT count(*) FROM mon_desconexiones WHERE id_usuario=usuario)*0.6)) as rate,
				(SELECT codigo FROM tipos_admin WHERE id_tipos_admin=administrador) as rango,
				(SELECT color FROM tipos_admin WHERE id_tipos_admin=administrador) as color,
				(SELECT nombre FROM tipos_cuenta WHERE id_tipos_cuenta='tipo_cuenta') as cuenta_nombre,
				(SELECT codigo FROM tipos_cuenta WHERE id_tipos_cuenta='tipo_cuenta') as cuenta_codigo
			FROM usuarios 
			WHERE nombre != 'usuario' AND administrador = '0'
			ORDER BY 
				rate DESC, 
				victorias DESC, 
				desconexiones ASC, 
				derrotas ASC,
				registrado ASC,
				id_usuario ASC,
				online DESC,
				ausente DESC";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$response['error'] = mysqli_error($conexion)." - ".$sql;
			echo json_encode($response);
			exit();
		}
		$temp = array();
		while($fila = mysqli_fetch_array($resul)){
			$temp[] = limpiar_array($fila);
		}
		
		$contador = 0;
		foreach($temp as $x){
		
			/* Obtengo los nombres y codigos de los tipos de cuenta */
			$sql = "SELECT nombre, codigo FROM tipos_cuenta WHERE id_tipos_cuenta='".$x['tipo_cuenta']."'";
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$response['error'] = mysqli_error($conexion)." - ".$sql;
				echo json_encode($response);
				exit();
			}
			while($fila = mysqli_fetch_array($resul)){
				$temp[$contador]['cuenta_nombre'] = $fila['nombre'];
				$temp[$contador]['cuenta_codigo'] = $fila['codigo'];
			}
			
			/* Personalizo los estados */
			if($x['online']==1 && $x['ausente']==1){ $estado = "img/online.png"; }
			else if($x['online']==0 && $x['ausente']==1){ $estado = "img/online-aus.png"; }
			else if($x['online']==1 && $x['ausente']==0){ $estado = "img/offline.png"; }
			else if($x['online']==0 && $x['ausente']==0){ $estado = "img/offline.png"; }
			$temp[$contador]['estado'] = $estado;
			
			/* Pasamos al siguiente usuario */
			$contador++;
		}
		$clasificacion = $temp;
		
		include "vistas/vista_clasificacion.php";
		exit();
	}

	/*	Menú usuario - Online */
	else if(isset($_SESSION['id_usuario']) && isset($_GET['action']) && $_GET['action'] == "online"){
		
		/* Obtengo los datos de los usuarios */
		$sql = "SELECT 
				id_usuario,
				id_usuario as usuario,
				nombre,
				tipo_cuenta,
				administrador,
				online,
				ausente,
				(SELECT count(*) FROM mon_victorias WHERE id_usuario=usuario) as victorias,
				(SELECT count(*) FROM mon_derrotas WHERE id_usuario=usuario) as derrotas,
				(SELECT count(*) FROM mon_desconexiones WHERE id_usuario=usuario) as desconexiones,
				((SELECT count(*) FROM mon_victorias WHERE id_usuario=usuario)-((SELECT count(*) FROM mon_derrotas WHERE id_usuario=usuario)*0.3)-((SELECT count(*) FROM mon_desconexiones WHERE id_usuario=usuario)*0.6)) as rate,
				(SELECT codigo FROM tipos_admin WHERE id_tipos_admin=administrador) as rango,
				(SELECT color FROM tipos_admin WHERE id_tipos_admin=administrador) as color,
				(SELECT nombre FROM tipos_cuenta WHERE id_tipos_cuenta='tipo_cuenta') as cuenta_nombre,
				(SELECT codigo FROM tipos_cuenta WHERE id_tipos_cuenta='tipo_cuenta') as cuenta_codigo
			FROM usuarios 
			WHERE nombre != 'usuario' AND ausente = '1' AND administrador = '0'
			ORDER BY 
				rate DESC, 
				victorias DESC, 
				desconexiones ASC, 
				derrotas ASC,
				registrado ASC,
				id_usuario ASC,
				online DESC,
				ausente DESC";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$response['error'] = mysqli_error($conexion)." - ".$sql;
			echo json_encode($response);
			exit();
		}
		$temp = array();
		while($fila = mysqli_fetch_array($resul)){
			$temp[] = limpiar_array($fila);
		}
		
		$contador = 0;
		foreach($temp as $x){
		
			/* Obtengo los nombres y codigos de los tipos de cuenta */
			$sql = "SELECT nombre, codigo FROM tipos_cuenta WHERE id_tipos_cuenta='".$x['tipo_cuenta']."'";
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$response['error'] = mysqli_error($conexion)." - ".$sql;
				echo json_encode($response);
				exit();
			}
			while($fila = mysqli_fetch_array($resul)){
				$temp[$contador]['cuenta_nombre'] = $fila['nombre'];
				$temp[$contador]['cuenta_codigo'] = $fila['codigo'];
			}
			
			/* Personalizo los estados */
			if($x['online']==1 && $x['ausente']==1){ $estado = "img/online.png"; }
			else if($x['online']==0 && $x['ausente']==1){ $estado = "img/online-aus.png"; }
			else if($x['online']==1 && $x['ausente']==0){ $estado = "img/offline.png"; }
			else if($x['online']==0 && $x['ausente']==0){ $estado = "img/offline.png"; }
			$temp[$contador]['estado'] = $estado;
			
			/* Pasamos al siguiente usuario */
			$contador++;
		}
		$online = $temp;
		
		include "vistas/vista_online.php";
		exit();
	}

	/*	Menú usuario - Ver mesas */
	else if(isset($_SESSION['id_usuario']) && isset($_GET['action']) && $_GET['action'] == "ver_mesas"){
		
		/*	Menú usuario - Ver mesas - Crear mesa */
		if(isset($_POST['crear_mesa'])){
		
			/* Comprueba si el usuario está ya sentado en una mesa. */
			$sql = "SELECT * FROM mon_mesas 
				WHERE jugador1='".$_SESSION['id_usuario']."' 
				OR jugador2='".$_SESSION['id_usuario']."' 
				OR jugador3='".$_SESSION['id_usuario']."' 
				OR jugador4='".$_SESSION['id_usuario']."'";
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$error = mysqli_error($conexion);
				include "error.php";
				exit();
			}
			$mesajugador = array();
			while($fila = mysqli_fetch_array($resul)){
				$mesajugador = limpiar_array($fila);
			}
			
			/* Si no está sentado en ninguna mesa, crea una nueva y la marca */
			if(empty($mesajugador)){
				
				/* Obtenemos los datos de los avatares. */
				$sql = "SELECT id_avatares, ruta, ".$_SESSION['idioma']." FROM mon_avatares";
				$resul = mysqli_query($conexion, $sql);
				if(!$resul){
					$error = mysqli_error($conexion);
					echo $error;
					exit;
				}
				$avatares = array();
				while($fila = mysqli_fetch_array($resul)){
					if($fila['ruta'] != "administrador" || $_SESSION['tipo_cuenta']==0){
						$avatares[$fila['id_avatares']] = limpiar_array($fila);
					}
				}
				
				/* Marcamos los que están usados por otros usuarios. */
				$avatar_usado = array();
				$avatar_usado[] = "ejemplo";
				foreach($mesajugador as $clave2 => $valor2){
					if(
						$clave2 == "jugador1avatar" ||
						$clave2 == "jugador2avatar" ||
						$clave2 == "jugador3avatar" ||
						$clave2 == "jugador4avatar"
					){
						if($valor2 != null){
							$avatar_usado[] = $valor2;
						}
					}
				}
				
				/* Nos sentamos en el primer asiento, con el primer avatar libre. */
				foreach($avatares as $avatar3){
					foreach($avatar_usado as $avatar2){
						if($avatar3['id_avatares'] != $avatar2){
							$sql = "INSERT INTO mon_mesas (jugador1, jugador1avatar)
									VALUES  ('".$_SESSION['id_usuario']."', '".$avatar3['id_avatares']."')";
							$resul = mysqli_query($conexion, $sql);
							if(!$resul){
								$error = mysqli_error($conexion);
								include "error.php";
								exit();
							}
							$sql = "SELECT id_mesa FROM mon_mesas WHERE jugador1='".$_SESSION['id_usuario']."'";
							$resul = mysqli_query($conexion, $sql);
							if(!$resul){
								$error = mysqli_error($conexion);
								include "error.php";
								exit();
							}
							$temp = array();
							while($fila = mysqli_fetch_array($resul)){
								$temp = $fila['id_mesa'];
							}
							
							$sql = "INSERT INTO mon_chat (id_usuario, id_mesa, hora, mensaje)
									VALUES  ('1', '".$temp."', now(), 'Bienvenidos. Por favor, si te vas a desconectar, sal de la mesa, para no molestar al resto de jugadores. Muchas gracias.')";
							$resul = mysqli_query($conexion, $sql);
							if(!$resul){
								$error = mysqli_error($conexion);
								include "error.php";
								exit();
							}
							
							
							header('Location: /entrar_mesa-'.$temp);
							exit();
						}
					}
				}
			}
			
			else{
				
				/* Entra a la mesa */
				header('Location: /entrar_mesa-'.$mesajugador['id_mesa']);
				exit();
			
			}
		}
		
		/* Comprueba si el usuario es espectador de alguna mesa. */
		$sql = "SELECT * FROM mon_espectadores WHERE id_usuario='".$_SESSION['id_usuario']."'";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$error = mysqli_error($conexion);
			include "error.php";
			exit();
		}
		$espectador = array();
		while($fila = mysqli_fetch_array($resul)){
			$espectador = limpiar_array($fila);
		}
		
		/* Si es espectador de alguna mesa, lo borra. */
		if(!empty($espectador)){
			$sql = "DELETE FROM mon_espectadores WHERE id_usuario='".$_SESSION['id_usuario']."'";
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$error = mysqli_error($conexion);
				include "error.php";
				exit();
			}
		}
	
		include "vistas/vista_mesas.php";
		exit();
	}

	/*  Menú usuario - Ver mesas - Entrar en una mesa */
	else if(isset($_SESSION['id_usuario']) && isset($_GET['action']) && $_GET['action'] == "entrar_mesa"){
	
		/* Busca la mesa donde se va a sentar el usuario el usuario */
		$sql = "SELECT * FROM mon_mesas WHERE id_mesa=".$_GET['mesa'];
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$error = mysqli_error($conexion);
			include "error.php";
			exit();
		}
		$datosmesa = array();
		while($fila = mysqli_fetch_array($resul)){
			$datosmesa = limpiar_array($fila);
		}
		
		/*	Comprueba si se apretó el botón "Dejar mesa" */
		if(isset($_POST['dejar_mesa'])){
		
			/* Busca el asiento donde está sentado el usuario */
			foreach($datosmesa as $clave => $valor){
				if(
				$clave=="jugador1" && $valor==$_SESSION['id_usuario'] || 
				$clave=="jugador2" && $valor==$_SESSION['id_usuario'] ||
				$clave=="jugador3" && $valor==$_SESSION['id_usuario'] ||
				$clave=="jugador4" && $valor==$_SESSION['id_usuario']
				){
					$miasiento = $clave;
				}
			}
			
			/* Borra al usuario de la mesa */
			$sql = "UPDATE mon_mesas SET ".$miasiento."=null, ".$miasiento."avatar=null, ".$miasiento."ok='0' WHERE id_mesa='".$datosmesa['id_mesa']."'";
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$error = mysqli_error($conexion);
				include "error.php";
				exit();
			}
			
			$sql = "SELECT * FROM mon_mesas WHERE id_mesa=".$datosmesa['id_mesa'];
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$error = mysqli_error($conexion);
				include "error.php";
				exit();
			}
			$datosmesa = array();
			while($fila = mysqli_fetch_array($resul)){
				$datosmesa = limpiar_array($fila);
			}
			
			if($datosmesa['jugador1'] == "" || $datosmesa['jugador1'] == null){
				$datosmesa['jugador1'] = $datosmesa['jugador2'];
				$datosmesa['jugador1avatar'] = $datosmesa['jugador2avatar'];
				$datosmesa['jugador1ok'] = $datosmesa['jugador2ok'];
				$datosmesa['jugador2'] = null;
				$datosmesa['jugador2ok'] = 0;
				$sql = "UPDATE
						mon_mesas 
					SET 
						jugador1 = '".$datosmesa['jugador1']."',
						jugador1avatar = '".$datosmesa['jugador1avatar']."',
						jugador1ok = '".$datosmesa['jugador1ok']."',
						jugador2 = null,
						jugador2avatar = null,
						jugador2ok = 0
					WHERE 
						id_mesa='".$datosmesa['id_mesa']."'";
				$resul = mysqli_query($conexion, $sql);
				if(!$resul){
					$error = mysqli_error($conexion);
					include "error.php";
					exit();
				}
			}
			if($datosmesa['jugador2'] == "" || $datosmesa['jugador2'] == null){
				$datosmesa['jugador2'] = $datosmesa['jugador3'];
				$datosmesa['jugador2avatar'] = $datosmesa['jugador3avatar'];
				$datosmesa['jugador2ok'] = $datosmesa['jugador3ok'];
				$datosmesa['jugador3'] = null;
				$datosmesa['jugador3avatar'] = null;
				$datosmesa['jugador3ok'] = 0;
				$sql = "UPDATE
						mon_mesas 
					SET 
						jugador2 = '".$datosmesa['jugador2']."',
						jugador2avatar = '".$datosmesa['jugador2avatar']."',
						jugador2ok = '".$datosmesa['jugador2ok']."',
						jugador3 = null,
						jugador3avatar = null,
						jugador3ok = 0
					WHERE 
						id_mesa='".$datosmesa['id_mesa']."'";
				$resul = mysqli_query($conexion, $sql);
				if(!$resul){
					$error = mysqli_error($conexion);
					include "error.php";
					exit();
				}
			}
			if($datosmesa['jugador3'] == "" || $datosmesa['jugador3'] == null){
				$datosmesa['jugador3'] = $datosmesa['jugador4'];
				$datosmesa['jugador3avatar'] = $datosmesa['jugador4avatar'];
				$datosmesa['jugador3ok'] = $datosmesa['jugador4ok'];
				$datosmesa['jugador4'] = null;
				$datosmesa['jugador4avatar'] = null;
				$datosmesa['jugador4ok'] = 0;
				$sql = "UPDATE
						mon_mesas 
					SET 
						jugador3 = '".$datosmesa['jugador3']."',
						jugador3avatar = '".$datosmesa['jugador3avatar']."',
						jugador3ok = '".$datosmesa['jugador3ok']."',
						jugador4 = null,
						jugador4avatar = null,
						jugador4ok = 0
					WHERE 
						id_mesa='".$datosmesa['id_mesa']."'";
				$resul = mysqli_query($conexion, $sql);
				if(!$resul){
					$error = mysqli_error($conexion);
					include "error.php";
					exit();
				}
			}
			
			if($datosmesa['jugador1'] == "" || $datosmesa['jugador1'] == null){
				if($datosmesa['jugador2'] == "" || $datosmesa['jugador2'] == null){
					if($datosmesa['jugador3'] == "" || $datosmesa['jugador3'] == null){
						if($datosmesa['jugador4'] == "" || $datosmesa['jugador4'] == null){
							$sql = "DELETE FROM mon_mesas WHERE id_mesa='".$datosmesa['id_mesa']."'";
							$resul = mysqli_query($conexion, $sql);
							if(!$resul){
								echo mysqli_error($conexion);
								exit();
							}
							$sql = "DELETE FROM mon_chat WHERE id_mesa='".$datosmesa['id_mesa']."'";
							$resul = mysqli_query($conexion, $sql);
							if(!$resul){
								$error = mysqli_error($conexion);
								include "error.php";
								exit();
							}
						}
					}
				}
			}
			
			header('Location: /ver_mesas');
			exit();
		}
		
		/*	Comprueba si se apretó el botón "Dejar mesa" por un espectador */
		if(isset($_POST['dejar_mesa_espectador'])){
			header('Location: /ver_mesas');
			exit();
		}
		
		/*	Comprueba si el usuario pulsó el botón "Sentarse" */
		if(isset($_GET[str_replace(" ", "_", $idioma["mesas_sentarse"])]) && $_GET[str_replace(" ", "_", $idioma["mesas_sentarse"])]=="ok"){
		
			/* Si el jugador todavía no está registrado en la mesa,  */
			/* se registra en el primer asiento vacío. */
			if(
			$datosmesa['jugador1'] != $_SESSION['id_usuario'] && 
			$datosmesa['jugador2'] != $_SESSION['id_usuario'] && 
			$datosmesa['jugador3'] != $_SESSION['id_usuario'] && 
			$datosmesa['jugador4'] != $_SESSION['id_usuario']
			){
			
				/* Obtenemos los datos de los avatares. */
				$sql = "SELECT id_avatares, ruta, ".$_SESSION['idioma']."
					FROM mon_avatares";
				$resul = mysqli_query($conexion, $sql);
				if(!$resul){
					$error = mysqli_error($conexion);
					echo $error;
					exit;
				}
				$avatares = array();
				while($fila = mysqli_fetch_array($resul)){
					if($fila['ruta'] != "administrador" || $_SESSION['tipo_cuenta'] == 0){
						$avatares[$fila['id_avatares']] = limpiar_array($fila);
					}
				}

				/* Marcamos los que están usados por otros usuarios. */
				$avatar_usado = array();
				$avatar_usado[] = "ejemplo";
				foreach($datosmesa as $clave2 => $valor2){
					if(
						$clave2 == "jugador1avatar" ||
						$clave2 == "jugador2avatar" ||
						$clave2 == "jugador3avatar" ||
						$clave2 == "jugador4avatar"
					){
						if($valor2 != null){
							$avatar_usado[] = $valor2;
						}
					}
				}
				
				/* Nos sentamos en el primer asiento vacío. */
				foreach($datosmesa as $clave => $valor){
					if(
					$clave=="jugador1" && $valor==null || 
					$clave=="jugador2" && $valor==null ||
					$clave=="jugador3" && $valor==null ||
					$clave=="jugador4" && $valor==null
					){
						foreach($avatares as $avatar3){
							$usable = true;
							foreach($avatar_usado as $avatar2){
								if($avatar3['id_avatares'] == $avatar2){
									$usable = false;
								}
							}
							
							if($usable == true){
								$sql = "UPDATE mon_mesas SET 
									".$clave."='".$_SESSION['id_usuario']."', 
									".$clave."avatar='".$avatar3['id_avatares']."', 
									".$clave."ok='0' 
								WHERE id_mesa='".$datosmesa['id_mesa']."'";
								
								$resul = mysqli_query($conexion, $sql);
								if(!$resul){
									$error = mysqli_error($conexion);
									include "error.php";
									exit();
								}
								
								header('Location: /entrar_mesa-'.$datosmesa['id_mesa']);
								exit();
							}
						}
					}
				}
			}
			
		}
		
		/*	Comprueba si el usuario pulsó el botón "Mirar mesa" */
		if(isset($_GET[$idioma["mesas_mirar"]]) && $_GET[$idioma["mesas_mirar"]] == "ok"){
			
			/* Registrar como espectador. */
			$sql = "INSERT INTO mon_espectadores (id_usuario, id_mesa) VALUES ('".$_SESSION['id_usuario']."', '".$_GET['mesa']."')";
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$error = mysqli_error($conexion);
				include "error.php";
				exit();
			}
			$soyespectador = true;
			
			$mimesa = $_GET['mesa'];
			if($datosmesa['jugando'] == 1){
			
				header("Location: /partida");
				exit();
			}
		}
		
		/*	Crea una lista con los espectadores anotados en la mesa */
		$sql = "SELECT * FROM mon_espectadores WHERE id_mesa='".$_GET['mesa']."'";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$error = mysqli_error($conexion);
			include "error.php";
			exit();
		}
		$id_espectadores = array();
		while($fila = mysqli_fetch_array($resul)){
			$id_espectadores[] = limpiar_array($fila);
		}
		
		$espectadores = array();
		foreach($id_espectadores as $id_espectador){
			foreach($id_espectador as $clave => $valor){
				if($clave == "id_espectador"){
					$sql = "SELECT id_usuario, email, nombre, tipo_cuenta FROM usuarios WHERE id_usuario='".$valor."'";
					$resul = mysqli_query($conexion, $sql);
					if(!$resul){
						$error = mysqli_error($conexion);
						include "error.php";
						exit();
					}
					$temp = array();
					while($fila = mysqli_fetch_array($resul)){
						$temp[] = limpiar_array($fila);
					}
					$espectadores[$valor] = $temp['0'];
				}
			}
		}
		
		/*	Crea una lista con los avatares */
		$sql = "SELECT * FROM mon_avatares ORDER BY ruta ASC";
		$resul = mysqli_query($conexion, $sql);
		if(!$resul){
			$error = mysqli_error($conexion);
			include "error.php";
			exit();
		}
		$avatares = array();
		while($fila = mysqli_fetch_array($resul)){
			$avatares[$fila['id_avatares']] = limpiar_array($fila);
		}
		
		/*	Cuenta los jugadores que hay en la mesa, cuantos de ellos están preparados */
		/*	y determina en qué asiento esta sentado el usuario. */
		$asientos=0; 
		$asientosok=0;
		foreach($datosmesa as $clave => $valor){
			if($clave == "jugador1" && $valor != null ||  $clave == "jugador2" && $valor != null || $clave == "jugador3" && $valor != null || $clave == "jugador4" && $valor != null){
				if($valor == $_SESSION['id_usuario']){
					$asiento = $clave;
					$miAvatar = $datosmesa[$clave."avatar"];
				}
				$asientos++;
				
				$avatares[$datosmesa[$clave."avatar"]]['usado'] = 1;
				
			}
			
			if($clave == "jugador1ok" && $valor != 0 || $clave == "jugador2ok" && $valor != 0 || $clave == "jugador3ok" && $valor != 0 || $clave == "jugador4ok" && $valor != 0){
				$asientosok++;
			}
		}

		/* Comprueba si soy espectador o jugador */
		foreach($espectadores as $observador){
			if($observador['id_usuario'] == $_SESSION['id_usuario']){
				$soyespectador = true;
			}
		}
		
		/* Empieza la partida */
		if(isset($_GET['empezar_partida'])){
			foreach($datosmesa as $clave => $valor){
			if($clave=="id_mesa"){
				$idmesa = $valor;
			}else if(
			$clave=="jugador1" && $valor!=null || 
			$clave=="jugador2" && $valor!=null ||
			$clave=="jugador3" && $valor!=null ||
			$clave=="jugador4" && $valor!=null
			){
			
			/* Se crea en la base de datos la entrada correspondiente para cada jugador */
			/* de la partida. */
				$sql = "INSERT INTO mon_partidas_jugadores (id_jugador, id_partida, id_avatar) VALUES ('".$valor."', '".$idmesa."', '".$$datosmesa[$clave.'avatar']."')";
				$resul = mysqli_query($conexion, $sql);
				if(!$resul){
					$error = mysqli_error($conexion);
					include "error.php";
					exit();
				}
			}}
			
			/* Se cambia el estado de la mesa para representar que ya se está jugando. */
			$sql = "UPDATE mon_mesas SET jugando=1 WHERE id_mesa=".$idmesa;
			$resul = mysqli_query($conexion, $sql);
			if(!$resul){
				$error = mysqli_error($conexion);
				include "error.php";
				exit();
			}
			header('Location: /partida-'.$id_mesa);
			exit();
		}
		
		include "vistas/vista_mesa.php";
		exit();
	}
	
	/* Menú admin */
	else if(isset($_SESSION['id_usuario']) && isset($_GET['action']) && $_GET['action'] == "admin"){
		if($usuario['administrador'] >= 1){
		
			header('Location: admin/');
			exit();
		}
	}
	
	else{
		header("Location: /");
		exit();
	}
?>
