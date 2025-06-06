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
                
                // Resetear campos
                $('.form-control').val('');
                $('.preview-field').html('-');
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

        var rows =$('#detalleVentaTrabajo tr').length;//cuantas filas tiene detalle venta

        if(rows > 0){// si hay productos en el detalle                                                                                                                                  
            var action = 'anularPedidoTrabajo';

            $.ajax({
                url: '../shared/ajax_imprenta.php',
                type: "POST",
                async : true,
                data: {action:action}, 
    
                success: function(response){
                    if(response!='error'){// si elimino todo el detalle
                        location.reload();//refresca toda la pagina
                    }
                },
                error: function(error){

                }
            });    

        }

    });

    //Confirmar pedido trabajo
    $('#btn_new_pedido_trabajo').click(function(e){
        e.preventDefault();
        
        var rows = $('#detalleVentaTrabajo tr').length; //cuantas filas tiene detalle venta

        if(rows <= 0) { // Si NO hay trabajos en el detalle
            alert('No hay trabajos agregados en el pedido');
            return false; // Detenemos la ejecución
        }
        
        // Si hay productos en el detalle                                                                                                                                  
        var action = 'procesarPedidoTrabajo';
        var codCliente = $('#idCliente_imprenta').val();
        var senia = $('#seniaPedidoImprenta').val();
        
        if(codCliente == null || codCliente == ''){
            alert('Falta agregar cliente');
        } else {
            $.ajax({
                url: '../shared/ajax_imprenta.php',
                type: "POST",
                async: true,
                data: {action: action, codCliente: codCliente, senia: senia}, 
        
                success: function(response){
                    if(response != 'error'){ // si se genero el pedido
                        alert('Pedido generado correctamente');
                        location.reload(); // refresca toda la pagina
                    } else {
                        console.log('no data');
                    }
                },
                error: function(error){
                    console.log('Error:', error);
                }
            });    
        }
    });

});

//Agrega libro a pedido desde la lista de libros(fuera del ready)------------------------
function agregarAPedido(idLibro) {
    // Solicitar la cantidad
    var cantidad = prompt("Ingrese la cantidad:");

    // Verificar que se haya ingresado un valor
    if (cantidad > 0 && cantidad !== null && cantidad !== "" && !isNaN(cantidad)) {
        // Confirmar la acción
        var confirmar = confirm("¿Está seguro que desea agregar " + cantidad + " unidades al pedido?");
        if (confirmar) {
            // Redirigir a la página con los parámetros necesarios
            //window.location.href = "modificar_libros.php?ID_LIBRO=" + idLibro + "&CANTIDAD=" + cantidad;

            var action = 'agregarLibroDetalle';

            $.ajax({
                url: 'ajax.php',
                type: "POST",
                async : true,
                data: {action:action,producto:idLibro,cantidad:cantidad}, 
    
                success: function(response){
                    if(response != 'error'){//validamos que la respuesta no sea error
                        var info = JSON.parse(response);//convertimos en JSON a un objeto
                        $('#detalleVenta').html(info.detalle);//pasamos el codigo a #detalle_venta y totales
                        $('#detalleTotal').html(info.totales);

                        //ponemos todos los valores por defecto
                        $('#txtIdLibro').val('');
                        $('#txt_titulo').html('-'); 
                        $('#txt_editorial').html('-');
                        $('#txt_precio').html('0.00');
                        $('#txt_cantidad_libro').val('0');
                        $('#txt_precio_total').html('0.00');

                        //bloquear Cantidad
                        $('#txt_cantidad_libro').attr('disabled','disabled');

                        //ocultar boton agregar
                        $('#add_libro_pedido').slideUp();
                        alert('Libro agregado al pedido!');
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
    } else {
        alert("Por favor, ingrese una cantidad válida.");
    }
}

//funcion para eliminar el detalle del pedido Trabajo(fuera del ready)
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





    