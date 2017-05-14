<?php echo $header; ?><?php echo $column_left; ?>

<div id="content">
	<div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-slideshow" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
	<?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
	
	<form action="<?php echo $action; ?>" method="post" id="form-oml_facto" class="form-horizontal" enctype="multipart/form-data">
	
	<div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-gears"></i> <?php echo $text_edit; ?></h3>
      </div>
		
	  <div class="panel-body">
		
            			
		  <div class="form-group">
            <label class="col-sm-2 control-label" for="input-oml_facto_backend_rut">Tipo de documento</label>
            <div class="col-sm-10">
              
				<div class="checkbox">
					<label>
						<input type="checkbox" value="fe" name="oml_facto_backend_tipo_fe" id="input-oml_facto_backend_tipo_fe" 
							<?php 
								if($oml_facto_backend_tipo_fe == "fe") echo "checked='checked'";
							?>
						/> 
						Factura electr&oacute;nica
					</label>
				</div>
				
				<div class="checkbox">
					<label>
						<input type="checkbox" value="fee" name="oml_facto_backend_tipo_fee" id="input-oml_facto_backend_tipo_fee" 
							<?php 
								if($oml_facto_backend_tipo_fee == "fee") echo "checked='checked'";
							?>
						/> 
						Factura exenta electr&oacute;nica
					</label>
				</div>
				
				<div class="checkbox">
					<label>
						<input type="checkbox" value="be" name="oml_facto_backend_tipo_be" id="input-oml_facto_backend_tipo_be" 
							<?php 
								if($oml_facto_backend_tipo_be == "be") echo "checked='checked'";
							?>
						/> 
						Boleta electr&oacute;nica
					</label>
				</div>
				
				<div class="checkbox">
					<label>
						<input type="checkbox" value="bee" name="oml_facto_backend_tipo_bee" id="input-oml_facto_backend_tipo_bee" 
							<?php 
								if($oml_facto_backend_tipo_bee == "bee") echo "checked='checked'";
							?>
						/> 
						Boleta exenta electr&oacute;nica
					</label>
				</div>
			  
              <?php if ($error_oml_facto_backend_tipo) { ?>
              <div class="text-danger"><?php echo $error_oml_facto_backend_tipo; ?></div>
              <?php } ?>
            </div>
          </div>
			
		   <div class="form-group">
            <label class="col-sm-2 control-label" for="input-oml_facto_backend_rut">RUT de vendedor</label>
            <div class="col-sm-10">
              <input type="text" name="oml_facto_backend_rut" value="<?php echo $oml_facto_backend_rut; ?>" placeholder="Por favor ingrese el RUT del vendedor." id="input-oml_facto_backend_rut" class="form-control" />
              <?php if ($error_oml_facto_backend_rut) { ?>
              <div class="text-danger"><?php echo $error_oml_facto_backend_rut; ?></div>
              <?php } ?>
            </div>
          </div>
		  
		  <div class="form-group">
            <label class="col-sm-2 control-label" for="input-oml_facto_backend_user">Webservice user</label>
            <div class="col-sm-10">
              <input type="text" name="oml_facto_backend_user" value="<?php echo $oml_facto_backend_user; ?>" placeholder="Por favor ingrese el nombre de usuario solicitado por FACTO." id="input-oml_facto_backend_user" class="form-control" />
              <?php if ($error_oml_facto_backend_user) { ?>
              <div class="text-danger"><?php echo $error_oml_facto_backend_user; ?></div>
              <?php } ?>
            </div>
          </div>
		  
		  <div class="form-group">
            <label class="col-sm-2 control-label" for="input-oml_facto_backend_pass">Webservice pass</label>
            <div class="col-sm-10">
              <input type="text" name="oml_facto_backend_pass" value="<?php echo $oml_facto_backend_pass; ?>" placeholder="Por favor ingrese el password solicitado por FACTO." id="input-oml_facto_backend_pass" class="form-control" />
              <?php if ($error_oml_facto_backend_pass) { ?>
              <div class="text-danger"><?php echo $error_oml_facto_backend_pass; ?></div>
              <?php } ?>
            </div>
          </div>
		  
		  <div class="form-group">
            <label class="col-sm-2 control-label" for="input-oml_facto_backend_url">Webservice URL</label>
            <div class="col-sm-10">
              <input type="text" name="oml_facto_backend_url" value="<?php echo $oml_facto_backend_url; ?>" placeholder="Por favor ingrese la URL utilizada por el webservice de FACTO." id="input-oml_facto_backend_url" class="form-control" />
              <?php if ($error_oml_facto_backend_url) { ?>
              <div class="text-danger"><?php echo $error_oml_facto_backend_url; ?></div>
              <?php } ?>
            </div>
          </div>
		
		
	  </div>
	</div>
	
	
	<div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-bank"></i> M&eacute;todos de pago</h3>
      </div>
		
	  <div class="panel-body">
		<table class="table table-bordered table-hover" id="lista">
			<thead>
				<tr>
					<th>Medio de pago</th>
					<th>Facturaci&oacute;n</th>
				</tr>
			</thead>
			<tbody>
				<?php 
					foreach($method_data as $metodo) { 
						$fact = $metodo['fact'];
						if(!$fact) $fact = "manual";
				?>
					  <tr>
						<td><?php echo $metodo['metodo']; ?></td>
						<td>
							<select name="oml_<?php echo $metodo['metodo']; ?>_fact" id="oml_<?php echo $metodo['metodo']; ?>_fact" class="form-control">
								<option value="manual" <?php if($fact=="manual") echo "selected='selected'"; ?>>Manual</option>
								<option value="auto" <?php if($fact=="auto") echo "selected='selected'"; ?>>Autom&aacute;tica</option>
							</select>
						</td>
					  </tr>
				<?php }	?>
			</tbody>
		</table>
	  </div>
	</div>
	
	</form>
</div>

<script src="view/javascript/oml_facto/jquery.Rut.js"></script>
<script src="view/javascript/oml_facto/validar.js"></script>