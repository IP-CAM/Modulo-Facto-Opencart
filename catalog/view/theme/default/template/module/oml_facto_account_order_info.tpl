<div id="div_facto" style="display:none;">
	<?php if(isset($oml_frontend_tipo_code)) { ?>
	<h3>Datos de FACTO</h3>
	<table class="table table-bordered table-hover">
		<tbody>
			<tr>
				<th>Tipo de documento</th>
				<td><?php echo $oml_frontend_tipo; ?></td>
			</tr>
			
			<?php
				if($oml_frontend_tipo_code=="fe" || $oml_frontend_tipo_code=="fee") {
					?>
						<tr>
							<th>RUT</th>
							<td><?php echo $oml_frontend_rut; ?></td>
						</tr>
						
						<tr>
							<th>Raz&oacute;n social</th>
							<td><?php echo $oml_frontend_razonsocial; ?></td>
						</tr>
						
						<tr>
							<th>Giro</th>
							<td><?php echo $oml_frontend_giro; ?></td>
						</tr>
					<?php
				}	
			?>
		</tbody>
	</table>
	<?php } ?>
	<br>
		<h3>Facturaci&oacute;n</h3>
		
		<table class="table table-bordered table-hover">
		<tbody>
			<tr>
			<?php		
			if($oml_status == 1 && $oml_msg=='' && $oml_enlace=='') {
				echo "<td>Todav&iacute;a no se ha generado el documento.</td>";
			}
			else if($oml_status==1 && $oml_msg!='' && $oml_enlace=='') {
				echo "<td>No se ha podido generar el documento, por favor contactar con un administrador.</td>";
			}
			else if($oml_status==2 && $oml_msg!='' && $oml_enlace=='') {
				echo "<td>No se ha podido generar el documento, por favor contactar con un administrador.</td>";
			}
			else if($oml_status==2 && $oml_msg!='' && $oml_enlace!='') {
				echo "<td>Puede ver el borrador de su documento en el siguiente <a href='".$oml_enlace."' target='_new'>enlace</a>.</td>";
			}
			else if($oml_status==0) {
				echo "<td>Puede ver su documento en el siguiente <a href='".$oml_enlace."' target='_new'>enlace</a>.</td>";
			}

		?>
			</tr>
		</tbody>
	</table>
	<br>
</div>

<script>
	$(document).ready(function() {
		<?php if(isset($oml_frontend_tipo_code)) { ?>
			$('h3').before($('#div_facto').html());
		<?php } ?>
	});
</script>