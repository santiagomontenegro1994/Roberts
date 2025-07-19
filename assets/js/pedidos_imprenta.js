  /**
   * Funciones de Agregar
   */

$(document).ready(function() { //Se asegura que el DOM este cargado 

    //Activa campos para agregar cliente
    $('.btn_new_cliente_imprenta').click(function(e){
        e.preventDefault();
        $('#nom_cliente_imprenta').removeAttr('disabled');
        $('#ape_cliente_imprenta').removeAttr('disabled');

        $('#div_registro_cliente_imprenta').slideDown();

    });

    //Buscar clientes
    $('#tel_cliente_imprenta').keyup(function(e){ //cada vez que teclean un valor se activa
        e.preventDefault(); //evito que se recargue

        var cl = $(this).val(); //capturo lo que se teclea en cl
        var action = 'searchClienteImprenta';

        $.ajax({
            url: '../shared/ajax_imprenta.php',
            type: "POST",
            async : true,
            data: {action:action,cliente:cl},

            success: function(response)
            {
                if(response == 0){
                    $('#idCliente_imprenta').val('');
                    $('#nom_cliente_imprenta').val('');
                    $('#ape_cliente_imprenta').val('');
                    //mostrar boton agregar
                    $('.btn_new_cliente_imprenta').slideDown();
                }else{
                    var data = $.parseJSON(response);
                    $('#idCliente_imprenta').val(data.idCliente);
                    $('#nom_cliente_imprenta').val(data.nombre);
                    $('#ape_cliente_imprenta').val(data.apellido);
                    //Ocultar boton agregar
                    $('.btn_new_cliente_imprenta').slideUp();

                    //Bloquea campos
                    $('#nom_cliente_imprenta').attr('disabled','disabled');
                    $('#ape_cliente_imprenta').attr('disabled','disabled');

                    //Oculta boton guardar
                    $('#div_registro_cliente_imprenta').slideUp();

                }
            },
            error: function(error){
                console.log('Error:', error);
            }

        });

    });

    //Crear clientes
    $('#formularioClientePedidoImprenta').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: '../shared/ajax_imprenta.php',
            type: "POST",
            async : true,
            data: $('#formularioClientePedidoImprenta').serialize(), //le paso todos los elementos del formulario

            success: function(response)
            {
                if(response != 'error'){
                    //Agregar id al input hiden
                    $('#idCliente_imprenta').val(response);
                    //Bloquea campos
                    $('#nom_cliente_imprenta').attr('disabled','disabled');
                    $('#ape_cliente_imprenta').attr('disabled','disabled');

                    //Ocultar boton agregar
                    $('.btn_new_cliente_imprenta').slideUp();

                    //Ocultar boton guardar
                    $('#div_registro_cliente_imprenta').slideUp();
                }
            },
            error: function(error){
                console.log('Error:', error);
            }

        });

    });

    // Evento keyup para recalcular el total restando la seña
    $(document).on('keyup', '#seniaPedidoImprenta', function(e) {
        e.preventDefault();
        
        // Obtener valores y convertirlos a números
        var totalOriginal = parseFloat($('#total_pedido_original').text());
        var senia = parseFloat($(this).val()) || 0; // Si no es un número válido, usar 0
        
        // Calcular el precio total
        var precio_total = totalOriginal - senia;
        
        // Actualizar el total restante en el DOM
        $('#total_pedido').text(precio_total.toFixed(2));
    });

    //Agregar trabajo al detalle temporal de trabajos
    $('#add_trabajo_pedido').click(function(e){
        e.preventDefault();
        const camposRequeridos = ['#estado_trabajo', '#tipo_trabajo', '#enviado', '#fecha_entrega_date', '#hora_entrega'];
        const camposValidos = camposRequeridos.every(selector => $(selector).val().trim() !== '');

        if (!camposValidos) {
            alert('Complete todos los campos requeridos');
            return;
        }

        const formData = {
            action: 'agregarTrabajoDetalle',
            estado: $('#estado_trabajo').val(),
            trabajo: $('#tipo_trabajo').val(),
            enviado: $('#enviado').val(),
            fecha: $('#fecha_entrega_date').val(),
            hora: $('#hora_entrega').val(),
            precio: $('#precio').val() || '0',
            descripcion: $('#descripcion').val()
        };

        $.ajax({
            url: '../shared/ajax_imprenta.php',
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response){
                if(response.error) {
                    console.error('Error del servidor:', response.error);
                    alert('Error: ' + response.error);
                    return;
                }
                
                $('#detalleVentaTrabajo').html(response.detalle);
                $('#detalleTotalTrabajo').html(response.totales);
                
                // Restablecer valores por defecto en lugar de borrar
                $('#descripcion').val(''); // Solo este campo se limpia completamente
                
                // Valores por defecto para los selects
                $('#estado_trabajo').val($('#estado_trabajo option:first').val());
                $('#tipo_trabajo').val('6'); // Valor por defecto original (Flyer)
                $('#enviado').val('7'); // Valor por defecto original (Impresión Propia)
                
                // Fecha y hora
                $('#fecha_entrega_date').val(''); // Se puede dejar vacío o establecer fecha futura
                $('#hora_entrega').val('08:30'); // Primera opción del select
                
                // Precio
                $('#precio').val('0.00');
                
                // Mantener los campos del cliente intactos
            },
            error: function(xhr, status, error){
                console.error('AJAX Error:', status, error);
                console.error('Server Response:', xhr.responseText);
                alert('Error de conexión. Ver consola para detalles.');
            }
        });
    });

    //Anular pedido trabajo
    $('#btn_anular_pedido_trabajo').click(function(e){
        e.preventDefault();

        var rows =$('#detalleVentaTrabajo tr').length;

        if(rows > 0){
            var action = 'anularPedidoTrabajo';

            $.ajax({
                url: '../shared/ajax_imprenta.php',
                type: "POST",
                async : true,
                data: {action:action}, 

                success: function(response){
                    if(response!='error'){
                        // Limpiar también los campos del cliente localmente
                        $('#idCliente_imprenta').val('');
                        $('#tel_cliente_imprenta').val('');
                        $('#nom_cliente_imprenta').val('').attr('disabled','disabled');
                        $('#ape_cliente_imprenta').val('').attr('disabled','disabled');
                        $('.btn_new_cliente_imprenta').slideDown();
                        
                        location.reload();
                    }
                },
                error: function(error){
                    console.log('Error:', error);
                }
            });    
        }
    });

    //Confirmar pedido trabajo
    $('#btn_new_pedido_trabajo').click(function(e){
        e.preventDefault();
        
        var rows = $('#detalleVentaTrabajo tr').length;
        if(rows <= 0) {
            alert('No hay trabajos agregados en el pedido');
            return false;
        }
        
        var codCliente = $('#idCliente_imprenta').val();
        var senia = parseFloat($('#seniaPedidoImprenta').val()) || 0;
        
        if(!codCliente) {
            alert('Falta agregar cliente');
            return false;
        }
        
        if(senia > 0) {
            $('#montoPagoModal').val(senia.toFixed(2));
            $('#metodoPagoSeleccionado').val(''); // Resetear selección previa
            $('.metodo-pago-btn').removeClass('btn-primary').addClass('btn-outline-primary');
            $('#pagoModal').modal('show');
        } else {
            procesarPedidoTrabajo(codCliente, senia, null, null, null);
        }
    });

    // Función para procesar el pedido
    function procesarPedidoTrabajo(codCliente, senia, idTipoPago, idTipoMovimiento, observaciones) {
        var action = (idTipoPago) ? 'procesarPedidoTrabajoConPago' : 'procesarPedidoTrabajo';
        
        var data = {
            action: action,
            codCliente: codCliente,
            senia: senia
        };
        
        if(action === 'procesarPedidoTrabajoConPago') {
            data.idTipoPago = idTipoPago;
            data.observaciones = observaciones;
        }
        
        $.ajax({
            url: '../shared/ajax_imprenta.php',
            type: "POST",
            data: data,
            dataType: 'json',
            success: function(response){
                if(response && response.status === 'success') {
                    // Limpiar cliente de sesión después de procesar el pedido
                    $.ajax({
                        url: '../shared/ajax_imprenta.php',
                        type: "POST",
                        data: {action: 'limpiarClienteSession'},
                        success: function() {
                            alert('Pedido generado ' + (senia > 0 ? 'y pago registrado ' : '') + 'correctamente');
                            location.reload();
                        }
                    });
                } else {
                    var errorMsg = response?.message || 'Error desconocido';
                    alert('Error al procesar el pedido: ' + errorMsg);
                }
            },
            error: function(xhr, status, error){
                console.error('Error en la petición:', status, error);
                console.error('Respuesta del servidor:', xhr.responseText);
                alert('Error de conexión. Ver consola para detalles.');
            }
        });
    }

    // Manejar el confirmar pago desde el modal
    $('#btnConfirmarPago').click(function() {
        var idTipoPago = $('#metodoPagoSeleccionado').val();
        if(!idTipoPago) {
            alert('Seleccione método de pago');
            return;
        }
        
        var codCliente = $('#idCliente_imprenta').val();
        var senia = parseFloat($('#seniaPedidoImprenta').val()) || 0;
        
        procesarPedidoTrabajo(codCliente, senia, idTipoPago, 3, 'Seña por trabajo');
        $('#pagoModal').modal('hide');
    });

       // Manejar selección de método de pago
    $(document).on('click', '.metodo-pago-btn', function() {
        $('.metodo-pago-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        $('#metodoPagoSeleccionado').val($(this).data('id'));
    });
    
});

//funcion para eliminar el detalle del pedido Trabajo(fuera del ready)---------------------
function del_trabajo_detalle(correlativo){
    var action ='delProductoDetalleTrabajo';
    var id_detalle =correlativo;

    $.ajax({
        url: '../shared/ajax_imprenta.php',
        type: "POST",
        async : true,
        data: {action:action,id_detalle:id_detalle}, 

        success: function(response){
            if(response!='error'){
                var info = JSON.parse(response);
                $('#detalleVentaTrabajo').html(info.detalle);//pasamos el codigo a #detalle_venta y totales
                $('#detalleTotalTrabajo').html(info.totales);

                //ponemos todos los valores por defecto
                $('#txtIdLibro').val('');
                $('#txt_titulo').html('-'); 
                $('#txt_editorial').html('-');
                $('#txt_precio').html('0.00');
                $('#txt_cantidad_libro').val('0');
                $('#txt_precio_total').html('0.00');


            }else{//si trae un error colocamos todo en blanco
                $('#detalleVentaTrabajo').html('');
                $('#detalleTotalTrabajo').html('');
            }
        },
        error: function(error){
            console.log('Error:', error);
        }

    });

}

//funcion para mostrar u ocultar boton de registrar pedido(fuera del ready)----------------------
function viewProcesar(){
    if($('#detalleVenta tr').length > 0){
        $('#btn_new_pedido').show();
    }else{
        $('#btn_new_pedido').hide();
    }

}

//funcion para mostrar siempre el detalle del pedido de trabajos(fuera del ready)
function searchforDetalleTrabajo(){
    var action = 'searchforDetalleTrabajo';

    $.ajax({
        url: '../shared/ajax_imprenta.php',
        type: "POST",
        async : true,
        data: {action:action}, 

        success: function(response){

            if(response != 'error'){//validamos que la respuesta no sea error
                var info = JSON.parse(response);//convertimos en JSON a un objeto
                $('#detalleVentaTrabajo').html(info.detalle);//pasamos el codigo a #detalle_venta y totales
                $('#detalleTotalTrabajo').html(info.totales);

            }else{
                console.log('no data');
            }
            viewProcesar();//llamo la funcion para ver si oculto el boton

        },
        error: function(error){
            console.log('Error:', error);
        }

    });
    
}





    