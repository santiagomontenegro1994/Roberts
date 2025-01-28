  /**
   * Funciones de Agregar
   */

$(document).ready(function() { //Se asegura que el DOM este cargado 

    //Activa campos para agregar cliente
    $('.btn_new_cliente').click(function(e){
        e.preventDefault();
        $('#nom_cliente').removeAttr('disabled');
        $('#ape_cliente').removeAttr('disabled');
        $('#dir_cliente').removeAttr('disabled');
        $('#tel_cliente').removeAttr('disabled');

        $('#div_registro_cliente').slideDown();

    });


    //Buscar clientes
    $('#dni_cliente').keyup(function(e){ //cada vez que teclean un valor se activa
        e.preventDefault(); //evito que se recargue

        var cl = $(this).val(); //capturo lo que se teclea en cl
        var action = 'searchCliente';

        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async : true,
            data: {action:action,cliente:cl},

            success: function(response)
            {
                if(response == 0){
                    $('#idCliente').val('');
                    $('#nom_cliente').val('');
                    $('#ape_cliente').val('');
                    $('#dir_cliente').val('');
                    $('#tel_cliente').val('');
                    //mostrar boton agregar
                    $('.btn_new_cliente').slideDown();
                }else{
                    var data = $.parseJSON(response);
                    $('#idCliente').val(data.idCliente);
                    $('#nom_cliente').val(data.nombre);
                    $('#ape_cliente').val(data.apellido);
                    $('#dir_cliente').val(data.direccion);
                    $('#tel_cliente').val(data.telefono);
                    //Ocultar boton agregar
                    $('.btn_new_cliente').slideUp();

                    //Bloquea campos
                    $('#nom_cliente').attr('disabled','disabled');
                    $('#ape_cliente').attr('disabled','disabled');
                    $('#tel_cliente').attr('disabled','disabled');
                    $('#dir_cliente').attr('disabled','disabled');

                    //Oculta boton guardar
                    $('#div_registro_cliente').slideUp();

                }
            },
            error: function(error){
                console.log('Error:', error);
            }

        });

    });

    //Crear clientes
    $('#formularioClientePedido').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: 'ajax.php',
            type: "POST",
            async : true,
            data: $('#formularioClientePedido').serialize(), //le paso todos los elementos del formulario

            success: function(response)
            {
                if(response != 'error'){
                    //Agregar id al input hiden
                    $('#idCliente').val(response);
                    //Bloquea campos
                    $('#nom_cliente').attr('disabled','disabled');
                    $('#ape_cliente').attr('disabled','disabled');
                    $('#tel_cliente').attr('disabled','disabled');
                    $('#dir_cliente').attr('disabled','disabled');

                    //Ocultar boton agregar
                    $('.btn_new_cliente').slideUp();

                    //Ocultar boton guardar
                    $('#div_registro_cliente').slideUp();
                }
            },
            error: function(error){
                console.log('Error:', error);
            }

        });

    });

    //Buscar Libro
    $('#txtIdLibro').keyup(function(e){
        e.preventDefault();

        var lb  = $(this).val(); //capturo lo que se teclea en libro
        var action = 'infoLibro';

        if(lb!= ''){ //si la variable es diferente de vacio ejecuto el ajax

            $.ajax({
                url: 'ajax.php',
                type: "POST",
                async : true,
                data: {action:action,libro:lb}, 
    
                success: function(response){
                    if(response!='error'){ //valido que la respuesta no sea error
                        var info = JSON.parse(response);//guardo la informacion en info
                        var precioConDosDecimales = parseFloat(info.precio).toFixed(2);
                        $('#txt_titulo').html(info.titulo); //paso los datos a las casillas
                        $('#txt_editorial').html(info.editorial);
                        $('#txt_precio').html(precioConDosDecimales);
                        $('#txt_cantidad_libro').val('1');
                        $('#txt_precio_total').html(precioConDosDecimales);

                        //activar Cantidad
                        $('#txt_cantidad_libro').removeAttr('disabled');

                        //mostrar boton agregar
                        $('#add_libro_pedido').slideDown();
                    }else{
                        $('#txt_titulo').html('-'); 
                        $('#txt_editorial').html('-');
                        $('#txt_precio').html('0.00');
                        $('#txt_cantidad_libro').val('0');
                        $('#txt_precio_total').html('0.00');

                        //bloquear Cantidad
                        $('#txt_cantidad_libro').attr('disabled','disabled');

                        //ocultar boton agregar
                        $('#add_libro_pedido').slideUp();
                    }
                },
                error: function(error){
                    console.log('Error:', error);
                }
    
            });
        }
       

    });

    //Validar cantidad de libro antes de agregar
    $('#txt_cantidad_libro').keyup(function(e){
        e.preventDefault();

        var precio_total =$(this).val() * $('#txt_precio').html();//calculo el precio total
        var precioConDosDecimales = parseFloat(precio_total).toFixed(2);
        $('#txt_precio_total').html(precioConDosDecimales); //se lo paso al campo

        //Oculta el boton agregar si es menor que 1
        if($(this).val() < 1 || isNaN($(this).val()) ){
            $('#add_libro_pedido').slideUp();
        }else{
            $('#add_libro_pedido').slideDown();
        }
    });

    // Evento keyup para recalcular el total restando la seña
    $(document).on('keyup', '#seniaPedido', function (e) {
        e.preventDefault();
        
        var descuento = $('#descuentoPedido').val();

        if (descuento !== null && !isNaN(descuento) && parseFloat(descuento) > 0) {

            var descuentoCalculado = $('#total_pedido_original').html() * (descuento / 100);
            var totalConDescuento = $('#total_pedido_original').html() - descuentoCalculado;

            var precio_total = totalConDescuento - $(this).val();//calculo el precio total

            // Actualizar el total restante en el DOM
            $('#total_pedido').text(precio_total.toFixed(2));
    
            // Mostrar u ocultar el botón según el total restante
            if (precio_total < 0) {
                $('#btn_new_pedido').hide();
            } else {
                $('#btn_new_pedido').show();
            }
        }else{
            var precio_total =$('#total_pedido_original').html() - $(this).val();//calculo el precio total
            console.log("entre");
            console.log(descuento);
            // Actualizar el total restante en el DOM
            $('#total_pedido').text(precio_total.toFixed(2));
    
            // Mostrar u ocultar el botón según el total restante
            if (precio_total < 0) {
                $('#btn_new_pedido').hide();
            } else {
                $('#btn_new_pedido').show();
            }
        }
     
    });

     // Evento keyup para recalcular el total restando el descuento
     $(document).on('keyup', '#descuentoPedido', function (e) {
        e.preventDefault();
        
        var senia = $('#seniaPedido').val();

        if (senia !== null && !isNaN(senia) && parseFloat(senia) > 0) {

            var descuentoCalculado = $('#total_pedido_original').html() * ($(this).val() / 100);
            var totalConDescuento = $('#total_pedido_original').html() - descuentoCalculado;

            var precio_total = totalConDescuento - senia;//calculo el precio total

            // Actualizar el total restante en el DOM
            $('#total_pedido').text(precio_total.toFixed(2));
    
            // Mostrar u ocultar el botón según el total restante
            if (precio_total < 0) {
                $('#btn_new_pedido').hide();
            } else {
                $('#btn_new_pedido').show();
            }
        }else{

            var descuentoCalculado = $('#total_pedido_original').html() * ($(this).val() / 100);
            var totalConDescuento = $('#total_pedido_original').html() - descuentoCalculado;
            // Actualizar el total restante en el DOM
            $('#total_pedido').text(totalConDescuento.toFixed(2));
    
            // Mostrar u ocultar el botón según el total restante
            if (totalConDescuento < 0) {
                $('#btn_new_pedido').hide();
            } else {
                $('#btn_new_pedido').show();
            }
        }
     
    });

    //Agregar producto al detalle temporal
    $('#add_libro_pedido').click(function(e){
        e.preventDefault();
        if($('#txt_cantidad_libro').val() > 0){

            var codlibro = $('#txtIdLibro').val();
            var cantidad = $('#txt_cantidad_libro').val();
            var action = 'agregarLibroDetalle';

            $.ajax({
                url: 'ajax.php',
                type: "POST",
                async : true,
                data: {action:action,producto:codlibro,cantidad:cantidad}, 
    
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
    });

    //Anular pedido
    $('#btn_anular_pedido').click(function(e){
        e.preventDefault();

        var rows =$('#detalleVenta tr').length;//cuantas filas tiene detalle venta

        if(rows > 0){// si hay productos en el detalle                                                                                                                                  
            var action = 'anularVenta';

            $.ajax({
                url: 'ajax.php',
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

    //Confirmar pedido
    $('#btn_new_pedido').click(function(e){
        e.preventDefault();
        
        var rows =$('#detalleVenta tr').length;//cuantas filas tiene detalle venta

        if(rows > 0){// si hay productos en el detalle                                                                                                                                  
            var action = 'procesarVenta';
            var codCliente = $('#idCliente').val();
            var senia = $('#seniaPedido').val();
            var descuento = $('#descuentoPedido').val();

            
            if(codCliente == null || codCliente == ''){
                alert('Falta agregar cliente');
            }else{

                $.ajax({
                    url: 'ajax.php',
                    type: "POST",
                    async : true,
                    data: {action:action,codCliente:codCliente,senia:senia,descuento:descuento}, 
        
                    success: function(response){
                        
                        if(response!='error'){// si se genero el pedido

                            // var info = JSON.parse(response);
                            // console.log(info);
                            alert('Pedido generado correctamente');
                            location.reload();//refresca toda la pagina
                        }else{
                            console.log('no data');
                        }
                    },
                    error: function(error){

                    }
                });    

            }

        }

    });

    

});

//Agrega libro a pedido desde la lista de libros(fuera del ready)
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

//funcion para eliminar el detalle del pedido(fuera del ready)
function del_libro_detalle(correlativo){
    var action ='delProductoDetalle';
    var id_detalle =correlativo;

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async : true,
        data: {action:action,id_detalle:id_detalle}, 

        success: function(response){
            if(response!='error'){
                var info = JSON.parse(response);
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

            }else{//si trae un error colocamos todo en blanco
                $('#detalleVenta').html('');
                $('#detalleTotal').html('');
            }
            viewProcesar();//llamo la funcion para ver si oculto el boton
        },
        error: function(error){
            console.log('Error:', error);
        }

    });

}

//funcion para mostrar u ocultar boton de registrar pedido(fuera del ready)
function viewProcesar(){
    if($('#detalleVenta tr').length > 0){
        $('#btn_new_pedido').show();
    }else{
        $('#btn_new_pedido').hide();
    }

}

//funcion para mostrar siempre el detalle del pedido(fuera del ready)
function searchforDetalle(){
    var action = 'searchforDetalle';

    $.ajax({
        url: 'ajax.php',
        type: "POST",
        async : true,
        data: {action:action}, 

        success: function(response){

            if(response != 'error'){//validamos que la respuesta no sea error
                var info = JSON.parse(response);//convertimos en JSON a un objeto
                $('#detalleVenta').html(info.detalle);//pasamos el codigo a #detalle_venta y totales
                $('#detalleTotal').html(info.totales);

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



    