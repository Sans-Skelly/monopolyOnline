<?php	include "includes/cabecera.php";	if(isset($_SESSION['id_usuario'])){		$logeado = true;	}?><div id="portada_contenido">		<div class="espaciador"></div>	<div class="feature">		<h1>Administradores</h1>		<br />		<ol>		<?php foreach($administradores as $admin){			echo '<li style="color: '.$admin['color'].'">'.$admin['rango'].' '.$admin['nombre'].'</li>';		}?>		</ol>	</div>	<div class="espaciador"></div>	<div class="feature">		<h1>GMs (Maestros de Juego)</h1>		<br />		<ol>		<?php foreach($gms as $gm){			echo '<li style="color: '.$gm['color'].'">'.$gm['rango'].' '.$gm['nombre'].'</li>';		}?>		</ol>	</div>	<div class="espaciador"></div>	<div class="feature">		<h1>Moderadores</h1>		<br />		<ol>		<?php foreach($moderadores as $mod){			echo '<li style="color: '.$mod['color'].'">'.$mod['rango'].' '.$mod['nombre'].'</li>';		}?>		</ol>	</div>		<div class="espaciador"></div>	<div class="feature"><center>		<script src="js/adsense_horizontal.js"></script>		<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>	</center></div>		<div class="espaciador"></div></div><?php include "includes/pie.php"; ?>