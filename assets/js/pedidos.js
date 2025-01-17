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
                console.log(response);
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
                    $('#idCliente').val(data.id);
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

});

    