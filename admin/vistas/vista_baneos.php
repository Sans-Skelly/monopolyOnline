<?php	include "includes/cabecera_admin.php";?>	<h1>		<img height="40px" style="vertical-align: middle;" src="../img/homescreen.png" /> 		Baneos	</h1>	<hr />	<a class="boton" href="javascript:nuevo_ban();">Nuevo ban</a>	<br />	<br />	<table>	<?php		echo "<tr>";			echo "<th>ID</th>";			echo "<th>Jugador</th>";			echo "<th>Tipo</th>";			echo "<th>Inicio</th>";			echo "<th>Finaliza</th>";			echo "<th>Motivo</th>";		echo "</tr>";		foreach($baneos as $ban){		echo "<tr>";			echo "<td>".$ban['id_ban']."</td>";			echo "<td>".$ban['nombre']."</td>";			echo "<td>".$ban['tipo']."</td>";			echo "<td>".formatear_fecha($ban['inicio'])."</td>";			echo "<td>".formatear_fecha($ban['fin'])."</td>";			echo "<td>".$ban['motivo']."</td>";		echo "			<td>				<form class='personal' action='' method='POST'>					<input type='submit' class='borrar' name='ban_borrar' value='' title='".$idioma['adm_borrar']."' />					<input type='hidden' name='ban_id' value='".$ban['id_ban']."' />				</form>			</td>		</tr>";	}	echo "</table>";?><?php	include "includes/pie.php"; ?>