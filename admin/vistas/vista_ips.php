<?php	include "includes/cabecera_admin.php";?>	<h1>		<img height="40px" style="vertical-align: middle;" src="../img/homescreen.png" /> 		Control de IPs	</h1>	<hr />	<br />	<br />	<?php	foreach($grupos_ip as $grupo){		echo "<table>";			echo "<tr>";				echo "<th>ID de Usuario</th>";				echo "<th>Nombre</th>";				echo "<th>Email</th>";				echo "<th>Vic</th>";				echo "<th>Derr</th>";				echo "<th>Desc</th>";				echo "<th>Última IP</th>";			echo "</tr>";			foreach($grupo as $ip){				echo "<tr>";					echo "<td>".$ip['user']."</td>";					echo "<td>".$ip['nombre']."</td>";					echo "<td>".$ip['email']."</td>";					echo "<td>".$ip['victorias']."</td>";					echo "<td>".$ip['derrotas']."</td>";					echo "<td>".$ip['desconexiones']."</td>";					echo "<td>".$ip['ultima_ip']."</td>";				echo "</tr>";			}		echo "</table>";		echo "<br style='clear: both;' />";	}		include "includes/pie.php"; ?>