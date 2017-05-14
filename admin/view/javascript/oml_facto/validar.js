$(document).ready(function() {
	$('#input-oml_facto_backend_rut').Rut({
		on_error: function(){
			alert('El rut ingresado es incorrecto');$('#input-oml_facto_backend_rut').val('');$('#input-oml_facto_backend_rut').focus(); 
		},
       format_on: 'keyup' 
	});
});