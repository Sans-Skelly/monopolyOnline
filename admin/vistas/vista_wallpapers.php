<?php	include "includes/cabecera_admin.php";?>	<h1>		<img height="40px" style="vertical-align: middle;" src="../img/homescreen.png" /> 		<?php echo $idioma['cfg_fondo']."s"; ?>	</h1>	<hr />		<form id="nuevo_wallpaper" class="personal" action="<?php echo "admin-".$_GET['seccion']; ?>" method="POST">		<?php foreach($wallpapers['0'] as $clave => $valor){			if($clave == "ruta"){				echo "					<span class='nuevo_elemento'>						<label>".$idioma['glo_ruta'].": </label>						<input class='nuevo_elemento' type='text' name='wallpaper_".$clave."' />					</span>";			}else if($clave == "propiedades"){				echo "					<span class='nuevo_elemento'>						<label>".$idioma['glo_propiedades'].": </label>						<input class='nuevo_elemento' type='text' name='wallpaper_".$clave."' />					</span>";			}else if($clave != "id_wallpapers"){				echo "					<span class='nuevo_elemento'>						<label>".$clave.": </label>						<input class='nuevo_elemento' type='text' name='wallpaper_".$clave."' />					</span>";			}		}?>		<br />		<br />		<span class="nuevo_elemento">			<input type="submit" name="nuevo_wallpaper" value="<?php echo $idioma['adm_agregar']; ?>" />		</span>	<br />	<br />	<table style="width: 100%;">		<tr>		<?php		foreach($wallpapers['0'] as $clave => $valor){			if($clave == "ruta"){				echo "<th>".$idioma['glo_ruta']."</th>";			}else if($clave == "propiedades"){				echo "<th>".$idioma['glo_propiedades']."</th>";			}else if($clave != "id_wallpapers"){				echo "<th>".$clave."</th>";			}		}		?>		</tr><?php	foreach($wallpapers as $lista_wallpaper){		echo "<tr>";		foreach ($lista_wallpaper as $clave => $valor){			if($clave != "id_wallpapers"){				echo "<td>".$valor."</td>";			}		}		echo "			<td>				<form class='personal' action='admin-".$_GET['seccion']."' method='POST'>					<input type='submit' class='borrar' name='borrar_wallpapers' value='' title='".$idioma['adm_borrar']."' />					<input type='hidden' name='borrar_wallpapers_id' value='".$lista_wallpaper['id_wallpapers']."' />				</form>			</td>		</tr>";	}	echo "</table>";	echo "</form>";	include "includes/pie.php"; ?>