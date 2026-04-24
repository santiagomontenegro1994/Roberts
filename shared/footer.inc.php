<!-- ======= Footer ======= -->
<footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>MM-Sistemas</span></strong>. All Rights Reserved
    </div>
    <div class="credits">
      <!-- All the links in the footer should remain intact. -->
      <!-- You can delete the links only if you purchased the pro version. -->
      <!-- Licensing information: https://bootstrapmade.com/license/ -->
      <!-- Purchase the pro version with working html/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
      Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
    </div>
  </footer><!-- End Footer -->


 

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- jquery-->
   
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  
  <!-- SELECT2-->

  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


  <!-- Vendor JS Files 2023-->
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Template Main JS File 2023-->
   
  <script src="../assets/js/main.js"></script>
  <script src="../assets/js/pedidos.js"></script> <!-- Incluye pedidos.js -->
  <script src="../assets/js/pedidos_imprenta.js?v=2"></script> <!-- Incluye pedidos.js -->

  <script src="../assets/js/pedidos_imprenta.js?v=2"></script> ```

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tVenta = document.getElementById('toggleGlobalVenta');
        const tPedido = document.getElementById('toggleGlobalPedido');
        const iVenta = document.getElementById('iconGlobalVenta');
        const iPedido = document.getElementById('iconGlobalPedido');

        // Leer memoria y pintar botones al cargar la página
        if (localStorage.getItem('imprimirTicketVenta') === 'true') {
            if(tVenta) { tVenta.checked = true; iVenta.classList.replace('text-secondary', 'text-primary'); }
        }
        if (localStorage.getItem('imprimirTicketPedido') === 'true') {
            if(tPedido) { tPedido.checked = true; iPedido.classList.replace('text-secondary', 'text-primary'); }
        }

        // Guardar cambios al hacer clic
        if(tVenta) {
            tVenta.addEventListener('change', function() {
                localStorage.setItem('imprimirTicketVenta', this.checked ? 'true' : 'false');
                this.checked ? iVenta.classList.replace('text-secondary', 'text-primary') : iVenta.classList.replace('text-primary', 'text-secondary');
            });
        }
        if(tPedido) {
            tPedido.addEventListener('change', function() {
                localStorage.setItem('imprimirTicketPedido', this.checked ? 'true' : 'false');
                this.checked ? iPedido.classList.replace('text-secondary', 'text-primary') : iPedido.classList.replace('text-primary', 'text-secondary');
            });
        }
        
        // Activar tooltips de Bootstrap para que al pasar el mouse diga qué es cada botón
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
  </script>

