/**
 * Funciones de Agregar
 */

$(document).ready(function() { //Se asegura que el DOM este cargado 

    // --- CORRECCIÓN 1: Evitar que el ENTER en el telefono envíe el formulario creando vacíos ---
    $('#tel_cliente_imprenta').on('keypress', function(e) {
        if(e.which === 13) { 
            e.preventDefault();
            return false;
        }
    });

    // Activa campos para agregar cliente manualmente
    $('.btn_new_cliente_imprenta').click(function(e){
        e.preventDefault();
        habilitarCamposCliente();
        $('#nom_cliente_imprenta').focus();
    });

    // --- CORRECCIÓN 2: Usar 'input' en lugar de 'keyup' para detectar PEGAR ---
    $('#tel_cliente_imprenta').on('input', function(e){ 
        e.preventDefault(); 

        var cl = $(this).val().trim();
        var action = 'searchClienteImprenta';

        // Evitamos busquedas innecesarias si el numero es muy corto
        if(cl.length < 5) {
             return; 
        }

        $.ajax({
            url: '../shared/ajax_imprenta.php',
            type: "POST",
            async : true,
            data: {action:action, cliente:cl},

            success: function(response)
            {
                if(response == 0){
                    // NO EXISTE: Limpiamos para que escriba, pero NO guardamos todavía
                    $('#idCliente_imprenta').val('');
                    // No limpiamos nombre/apellido si ya estaba escribiendo
                    if($('#nom_cliente_imprenta').is(':disabled')) {
                         $('#nom_cliente_imprenta').val('');
                         $('#ape_cliente_imprenta').val('');
                    }
                    
                    $('.btn_new_cliente_imprenta').slideDown();
                }else{
                    // SI EXISTE: Llenamos y bloqueamos
                    var data = $.parseJSON(response);
                    $('#idCliente_imprenta').val(data.idCliente);
                    $('#nom_cliente_imprenta').val(data.nombre);
                    $('#ape_cliente_imprenta').val(data.apellido);
                    
                    bloquearCamposCliente();
                }
            },
            error: function(error){
                console.log('Error:', error);
            }
        });
    });

    // --- CORRECCIÓN 3: Validación antes de Crear Cliente ---
    $('#formularioClientePedidoImprenta').submit(function(e){
        e.preventDefault();

        // Validacion: SOLO EL NOMBRE ES OBLIGATORIO
        var nombre = $('#nom_cliente_imprenta').val().trim();
        // var apellido = $('#ape_cliente_imprenta').val().trim(); // Ya no es obligatorio

        if(nombre === '') {
            alert("Error: Debe ingresar al menos el Nombre del cliente.");
            return false;
        }

        $.ajax({
            url: '../shared/ajax_imprenta.php',
            type: "POST",
            async : true,
            data: $('#formularioClientePedidoImprenta').serialize(),

            success: function(response)
            {
                if(response == 'error_datos_vacios'){
                    alert("Error: El nombre no puede estar vacío.");
                }
                else if(response != 'error'){
                    $('#idCliente_imprenta').val(response);
                    bloquearCamposCliente();
                }
            },
            error: function(error){
                console.log('Error:', error);
            }
        });
    });

    // --- Funciones auxiliares para no repetir código ---
    function bloquearCamposCliente() {
        $('.btn_new_cliente_imprenta').slideUp();
        $('#nom_cliente_imprenta').attr('disabled','disabled');
        $('#ape_cliente_imprenta').attr('disabled','disabled');
        $('#div_registro_cliente_imprenta').slideUp();
    }

    function habilitarCamposCliente() {
        $('#nom_cliente_imprenta').removeAttr('disabled');
        $('#ape_cliente_imprenta').removeAttr('disabled');
        $('#div_registro_cliente_imprenta').slideDown();
    }


    // -------------------------------------------------------------------------
    // RESTO DEL CÓDIGO (Lógica de Precios, Trabajos, Anular, etc.) SIN CAMBIOS
    // -------------------------------------------------------------------------

    // Evento keyup para recalcular el total restando la seña
    $(document).on('keyup', '#seniaPedidoImprenta', function(e) {
        e.preventDefault();
        var totalOriginal = parseFloat($('#total_pedido_original').text());
        var senia = parseFloat($(this).val()) || 0;
        var precio_total = totalOriginal - senia;
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
                
                // Restablecer valores
                $('#descripcion').val(''); 
                $('#estado_trabajo').val($('#estado_trabajo option:first').val());
                $('#tipo_trabajo').val('6'); 
                $('#enviado').val('7'); 
                // $('#fecha_entrega_date').val(''); // Opcional, mantener fecha
                $('#hora_entrega').val('08:30'); 
                $('#precio').val('0.00');
            },
            error: function(xhr, status, error){
                console.error('AJAX Error:', status, error);
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
            $('#metodoPagoSeleccionado').val(''); 
            $('.metodo-pago-btn').removeClass('btn-primary').addClass('btn-outline-primary');
            $('#pagoModal').modal('show');
        } else {
            procesarPedidoTrabajo(codCliente, senia, null, null, null);
        }
    });

    // Mostrar modal con detalles al procesar pedido
    function procesarPedidoTrabajo(codCliente, senia, idTipoPago, idTipoMovimiento, observaciones) {
        $('#facturacionModal').modal('show');
        $('#detallesFacturacion').html('<tr><td colspan="4" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>');

        $.ajax({
            url: '../shared/ajax_imprenta.php',
            type: "POST",
            data: {action: 'getDetallesTempTrabajos'},
            dataType: 'json',
            success: function(response) {
                if(response.error) {
                    $('#detallesFacturacion').html(`<tr><td colspan="4" class="text-center text-danger">Error: ${response.error}</td></tr>`);
                    return;
                }

                if(!response.detalles || response.detalles.length === 0) {
                    $('#detallesFacturacion').html('<tr><td colspan="4" class="text-center">No hay trabajos agregados</td></tr>');
                    return;
                }
                
                let html = '';
                response.detalles.forEach(detalle => {
                    html += `
                        <tr>
                            <td><input type="checkbox" class="form-check-input facturar-detalle" value="${detalle.correlativo}" checked></td>
                            <td><strong>${detalle.tipo_trabajo || 'Sin tipo'}</strong><br><small class="text-muted">${detalle.descripcion || 'Sin descripción'}</small></td>
                            <td><div>${detalle.proveedor || 'Sin proveedor'}</div><small class="text-muted">${detalle.fechaEntrega || 'Sin fecha'} - ${detalle.horaEntrega || 'Sin hora'}</small></td>
                            <td class="text-end">$${parseFloat(detalle.precio || 0).toFixed(2)}</td>
                        </tr>
                    `;
                });
                
                $('#detallesFacturacion').html(html);
                
                window.pedidoParams = {
                    codCliente: codCliente,
                    senia: senia,
                    idTipoPago: idTipoPago,
                    idTipoMovimiento: idTipoMovimiento,
                    observaciones: observaciones
                };
            },
            error: function(xhr, status, error) {
                console.error('Error:', status, error);
                $('#detallesFacturacion').html('<tr><td colspan="4" class="text-center text-danger">Error de conexión</td></tr>');
            }
        });
    }

    // Confirmar facturación
    $('#btnConfirmarFactura').click(function() {
        const idTipoFactura = $('#tipoFacturaModal').val();
        const numeroFactura = $('#numeroFacturaModal').val().trim();
        
        if(!numeroFactura) {
            alert('Ingrese número de factura');
            return;
        }
        
        const detallesFacturar = [];
        $('.facturar-detalle:checked').each(function() {
            detallesFacturar.push($(this).val());
        });
        
        if(detallesFacturar.length === 0) {
            alert('Seleccione al menos un detalle para facturar');
            return;
        }
        
        const data = {
            action: 'procesarPedidoTrabajoConFactura',
            codCliente: window.pedidoParams.codCliente,
            senia: window.pedidoParams.senia,
            idTipoPago: window.pedidoParams.idTipoPago,
            idTipoMovimiento: window.pedidoParams.idTipoMovimiento,
            observaciones: window.pedidoParams.observaciones,
            idTipoFactura: idTipoFactura,
            numeroFactura: numeroFactura,
            detallesFacturar: detallesFacturar.join(',')
        };
        
        $.ajax({
            url: '../shared/ajax_imprenta.php',
            type: "POST",
            data: data,
            dataType: 'json',
            success: function(response) {
                if(response && response.status === 'success') {
                    alert('Pedido generado correctamente');
                    location.reload();
                } else {
                    alert('Error al procesar el pedido: ' + (response?.message || 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                alert('Error de conexión.');
            }
        });
        
        $('#facturacionModal').modal('hide');
    });

    // Manejar opción sin factura
    $('#btnSinFactura').click(function() {
        var action = window.pedidoParams.idTipoPago ? 'procesarPedidoTrabajoConPago' : 'procesarPedidoTrabajo';
        
        $.ajax({
            url: '../shared/ajax_imprenta.php',
            type: "POST",
            data: {
                action: action,
                codCliente: window.pedidoParams.codCliente,
                senia: window.pedidoParams.senia,
                idTipoPago: window.pedidoParams.idTipoPago,
                idTipoMovimiento: window.pedidoParams.idTipoMovimiento,
                observaciones: window.pedidoParams.observaciones
            },
            dataType: 'json',
            success: function(response) {
                if(response && response.status === 'success') {
                    $.ajax({
                        url: '../shared/ajax_imprenta.php',
                        type: "POST",
                        data: {action: 'limpiarClienteSession'},
                        success: function() {
                            alert('Pedido generado correctamente');
                            location.reload();
                        }
                    });
                } else {
                    alert('Error al procesar el pedido: ' + (response?.message || 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                alert('Error de conexión.');
            }
        });
        
        $('#facturacionModal').modal('hide');
    });

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

// Funciones fuera del ready (se mantienen igual)
function del_trabajo_detalle(correlativo){
    var action ='delProductoDetalleTrabajo';
    $.ajax({
        url: '../shared/ajax_imprenta.php',
        type: "POST",
        async : true,
        data: {action:action,id_detalle:correlativo}, 
        success: function(response){
            if(response!='error'){
                var info = JSON.parse(response);
                $('#detalleVentaTrabajo').html(info.detalle);
                $('#detalleTotalTrabajo').html(info.totales);
            }else{
                $('#detalleVentaTrabajo').html('');
                $('#detalleTotalTrabajo').html('');
            }
        },
        error: function(error){ console.log('Error:', error); }
    });
}

function searchforDetalleTrabajo(){
    var action = 'searchforDetalleTrabajo';
    $.ajax({
        url: '../shared/ajax_imprenta.php',
        type: "POST",
        async : true,
        data: {action:action}, 
        success: function(response){
            if(response != 'error'){
                var info = JSON.parse(response);
                $('#detalleVentaTrabajo').html(info.detalle);
                $('#detalleTotalTrabajo').html(info.totales);
            }
            viewProcesar();
        },
        error: function(error){ console.log('Error:', error); }
    });
}

function viewProcesar(){
    if($('#detalleVenta tr').length > 0){ $('#btn_new_pedido').show(); }else{ $('#btn_new_pedido').hide(); }
}