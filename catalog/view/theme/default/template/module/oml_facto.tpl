<!-- FACTO -->
<script src="admin/view/javascript/oml_facto/jquery.Rut.js"></script>

<script>
$.ajaxSetup({
			beforeSend: function(jqXHR) {  
				jqXHR.abort();
			}
		});
</script>

<script>
	$(window).load(function(){ 
		var facto = "<div class='panel panel-default'>" +
						"<div class='panel-heading'>" +
							"<h4 class='panel-title'>Datos de FACTO</h4>" +
						"</div>" +
						"<div class='panel-collapse collapse' id='collapse-datos-de-facto'>" +
							"<div class='panel-body'>" +
			
								"<div class='form-group'>" +
									"<label for='facto_frontend_tipo'>Tipo de documento:</label>" +
									"<select class='form-control' id='facto_frontend_tipo' name='facto_frontend_tipo' onchange='facto_cambiar()'>" +
										<?php if($tipo_fe == "fe") {?> "<option value='fe'>Factura electr&oacute;nica</option>" + <?php } ?>
										<?php if($tipo_fee == "fee") {?> "<option value='fee'>Factura exenta electr&oacute;nica</option>" + <?php } ?>
										<?php if($tipo_be == "be") {?> "<option value='be'>Boleta electr&oacute;nica</option>" +<?php } ?>
										<?php if($tipo_bee == "bee") {?> "<option value='bee'>Boleta exenta electr&oacute;nica</option>" +<?php } ?>
									"</select>" +
								"</div>" +
								
								"<div id='facto_div_factura'>" +
									"<div class='form-group'>" +
										"<label for='facto_frontend_rut'>RUT:</label>" +
										"<input type='text' class='form-control' id='facto_frontend_rut' name='facto_frontend_rut'>" +						
									"</div>" +
									"<div class='form-group'>" +
										"<label for='facto_frontend_razonsocial'>Raz&oacute;n social:</label>" +
										"<input type='text' class='form-control' id='facto_frontend_razonsocial' name='facto_frontend_razonsocial'>" +						
									"</div>" +
									"<div class='form-group'>" +
										"<label for='facto_frontend_giro'>Giro:</label>" +
										"<input type='text' class='form-control' id='facto_frontend_giro' name='facto_frontend_giro'>" +						
									"</div>" +
								"</div>" +
								
								"<div class='buttons'>" +
									"<div class='pull-right'>" +
										"<input type='button' value='Siguiente' id='button-facto' class='btn btn-primary' loading='Cargando...'>" +
									"</div>" +
								"</div>" +
							"</div>" +
						"</div>" +
					"</div>";
	
		$('#accordion').prepend(facto);		
		
		facto_cambiar();
		
		$('#facto_frontend_rut').Rut({
			on_error: function(){
				alert('El rut ingresado es incorrecto');$('#facto_frontend_rut').val('');$('#facto_frontend_rut').focus(); 
			},
		   format_on: 'keyup' 
		});
				
		$('#collapse-checkout-option').parent().find('.panel-heading .panel-title').html('<?php echo $text_checkout_option; ?>');
		
		$.ajaxSetup({
			beforeSend: function(jqXHR) { // before jQuery send the request we will push it to our array
				
			}
		});
		
		$('#collapse-datos-de-facto').parent().find('.panel-heading .panel-title').html('<a href="#collapse-datos-de-facto" data-toggle="collapse" data-parent="#accordion" class="accordion-toggle">Datos de FACTO<i class="fa fa-caret-down"></i></a>');
		$('a[href=\'#collapse-datos-de-facto\']').trigger('click');
		
		
		
	});
	
$(document).delegate('#button-facto', 'click', function() {
    //validamos
	$('#error_rut').remove();
	$('#error_razonsocial').remove();
	$('#error_giro').remove();
	var tipo = $('#facto_frontend_tipo').val();
	
	if(tipo=="fe" || tipo=="fee") {
		if($('#facto_frontend_rut').val()) {
			if($('#facto_frontend_razonsocial').val()) {
				if($('#facto_frontend_giro').val()) {
					valido();
				}
				else {
					var elemento = $('#collapse-datos-de-facto .panel-body');
					elemento.prepend("<div class='alert alert-danger' id='error_giro'>Debe ingresar un giro</div>");
				}
			}
			else {
				var elemento = $('#collapse-datos-de-facto .panel-body');
				elemento.prepend("<div class='alert alert-danger' id='error_razonsocial'>Debe ingresar una razon social</div>");
			} 
		}
		else {
			var elemento = $('#collapse-datos-de-facto .panel-body');
			elemento.prepend("<div class='alert alert-danger' id='error_rut'>Debe ingresar el RUT</div>");
		} 
	}
	else {		
		valido();
	}
	
	
});

function valido() {
	$.ajax({
		url: "index.php?route=oml_facto/datos_facto/save",
		type: 'post',
		dataType: 'html',
		data: {
			'tipo': $('#facto_frontend_tipo').val(),
			'rut': $('#facto_frontend_rut').val(),
			'razonsocial': $('#facto_frontend_razonsocial').val(),
			'giro': $('#facto_frontend_giro').val()
		},
		complete: function() {
			$('#button-facto').button('reset');
		},
		success: function(html) {
			console.log(html);
			
			<?php if (!$logged) { ?>
				$.ajax({
					url: 'index.php?route=checkout/login',
					dataType: 'html',
					success: function(html) {
					   $('#collapse-checkout-option .panel-body').html(html);

						$('#collapse-checkout-option').parent().find('.panel-heading .panel-title').html('<a href="#collapse-checkout-option" data-toggle="collapse" data-parent="#accordion" class="accordion-toggle"><?php echo $text_checkout_option; ?> <i class="fa fa-caret-down"></i></a>');

						$('a[href=\'#collapse-checkout-option\']').trigger('click');
					},
					error: function(xhr, ajaxOptions, thrownError) {
						alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
					}
				});
			<?php } else { ?>
				$.ajax({
					url: 'index.php?route=checkout/payment_address',
					dataType: 'html',
					success: function(html) {
						$('#collapse-payment-address .panel-body').html(html);

						$('#collapse-payment-address').parent().find('.panel-heading .panel-title').html('<a href="#collapse-payment-address" data-toggle="collapse" data-parent="#accordion" class="accordion-toggle"><?php echo $text_checkout_payment_address; ?> <i class="fa fa-caret-down"></i></a>');

						$('a[href=\'#collapse-payment-address\']').trigger('click');
					},
					error: function(xhr, ajaxOptions, thrownError) {
						alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
					}
				});
			<?php } ?>
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}

/*
$(document).delegate('#button-confirm', 'click', function() {
    alert('facto');
	$.ajax({
		url: "index.php?route=oml_facto/datos_facto/confirmar",
		dataType: 'html',
		async: false,
		complete: function() {
			//$('#button-confirm').button('reset');
		},
		success: function(html) {
			
			console.log(html);
		
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
});
*/

function facto_cambiar(){
	var tipo = $('#facto_frontend_tipo').val();
		
	if(tipo=="fe" || tipo=="fee") {
		document.getElementById("facto_div_factura").style.display = "block";
	}
	else {		
		document.getElementById("facto_div_factura").style.display = "none";
	}
}
</script>