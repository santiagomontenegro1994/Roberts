<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item">
        <a class="nav-link " href="../core/index.php">
          <i class="bi bi-grid"></i>
          <span>Menu</span>
        </a>
      </li><!-- End Menu Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#clientes-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-person-bounding-box"></i><span>Clientes</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="clientes-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="../imprenta_clientes/agregar_clientes.php">
              <i class="bi bi-circle"></i><span>Agregar</span>
            </a>
          </li>
          <li>
            <a href="../imprenta_clientes/listados_clientes.php">
              <i class="bi bi-circle"></i><span>Listados</span>
            </a>
          </li>
        </ul>
      </li><!-- End Clientes Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#proveedores-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-person-bounding-box"></i><span>Proveedores</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="proveedores-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="../imprenta_proveedores/agregar_proveedores.php">
              <i class="bi bi-circle"></i><span>Agregar</span>
            </a>
          </li>
          <li>
            <a href="../imprenta_proveedores/listados_proveedores.php">
              <i class="bi bi-circle"></i><span>Listados</span>
            </a>
          </li>
        </ul>
      </li><!-- End Proveedores Nav -->

      <!--
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#libros-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-journal-text"></i><span>Libros</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="libros-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="agregar_libros.php">
              <i class="bi bi-circle"></i><span>Agregar</span>
            </a>
          </li>
          <li>
            <a href="listados_libros.php">
              <i class="bi bi-circle"></i><span>Listados</span>
            </a>
          </li>
        </ul>
      </li> Libros Nav -->

      <!--
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#pedidos-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-bag"></i><span>Pedidos de Libros</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="pedidos-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="agregar_pedido.php">
              <i class="bi bi-circle"></i><span>Agregar</span>
            </a>
          </li>
          <li>
            <a href="listados_pedidos.php">
              <i class="bi bi-circle"></i><span>Listados</span>
            </a>
          </li>
        </ul>
      </li> Pedido de Libros Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#pedidos-trabajos-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-bag"></i><span>Pedidos de Trabajos</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="pedidos-trabajos-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="../imprenta_trabajos/agregar_pedido_trabajo.php">
              <i class="bi bi-circle"></i><span>Agregar</span>
            </a>
          </li>
          <li>
            <a href="../imprenta_trabajos/listados_pedidos_trabajos.php">
              <i class="bi bi-circle"></i><span>Listados</span>
            </a>
          </li>
        </ul>
      </li> <!-- End Pedido de Trabajos Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#ventas-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-cash-stack"></i><span>Ventas</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="ventas-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li>
            <a href="../imprenta_caja/agregar_venta.php">
              <i class="bi bi-circle"></i><span>Agregar</span>
            </a>
          </li>
          <li>
            <a href="../imprenta_caja/retirar_caja.php">
              <i class="bi bi-circle"></i><span>Retirar</span>
            </a>
          </li>
          <li>
            <a href="../imprenta_caja/planilla_caja.php">
              <i class="bi bi-circle"></i><span>Planilla de Caja Actual</span>
            </a>
          </li>
          <li>
            <a href="../imprenta_caja/listados_caja.php">
              <i class="bi bi-circle"></i><span>Listados de cajas</span>
            </a>
          </li>
        </ul>
      </li><!-- End Caja Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#cta_cte-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-cash-stack"></i><span>Cuenta corriente</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="cta_cte-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li>
          <a href="../imprenta_cta_cte/cta_cte.php">
            <i class="bi bi-circle"></i><span>Cuenta Corriente </span>
          </a>
        </li>
        </ul>
      </li><!-- End Cuenta Corriente -->
    
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#contables-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-cash-stack"></i><span>Movimiento Contable</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="contables-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li>
          <a href="../imprenta_contables/movimientos_contables.php">
            <i class="bi bi-circle"></i><span>Listados Movimientos</span>
          </a>
        </li>
                <li>
          <a href="../imprenta_contables/agregar_contables.php">
            <i class="bi bi-circle"></i><span>Agregar</span>
          </a>
        </li>
        <li>
          <a href="../imprenta_contables/retirar_contables.php">
            <i class="bi bi-circle"></i><span>Retiro</span>
          </a>
        </li>
        </ul>
      </li><!-- End Contables -->



    </ul>

  </aside><!-- End Sidebar-->
