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
                        $('#txt_titulo').html(info.titulo); //paso los datos a las casillas
                        $('#txt_editorial').html(info.editorial);
                        $('#txt_precio').html(info.precio);
                        $('#txt_cantidad_libro').val('1');
                        $('#txt_precio_total').html(info.precio);

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

                        //mostrar boton agregar
                        $('#add_libro_pedido').slideUp();
                    }
                },
                error: function(error){
                    console.log('Error:', error);
                }
    
            });
        }
       

    });

    //Validar cantidad de producto antes de agregar
    $('#txt_cantidad_libro').keyup(function(e){
        e.preventDefault();

        var precio_total =$(this).val() * $('#txt_precio').html();//calculo el precio total
        $('#txt_precio_total').html(precio_total); //se lo paso al campo

        //Oculta el boton agregar si es menor que 1
        if($(this).val() < 1 || isNaN($(this).val()) ){
            $('#add_libro_pedido').slideUp();
        }else{
            $('#add_libro_pedido').slideDown();
        }
    });


});

    