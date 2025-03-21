<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item">
        <a class="nav-link " href="index.php">
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
            <a href="agregar_clientes.php">
              <i class="bi bi-circle"></i><span>Agregar</span>
            </a>
          </li>
          <li>
            <a href="listados_clientes.php">
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
            <a href="agregar_proveedores.php">
              <i class="bi bi-circle"></i><span>Agregar</span>
            </a>
          </li>
          <li>
            <a href="listados_proveedores.php">
              <i class="bi bi-circle"></i><span>Listados</span>
            </a>
          </li>
        </ul>
      </li><!-- End Proveedores Nav -->

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
      </li><!-- End Libros Nav -->

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
      </li><!-- End Pedido de Libros Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#ventas-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-cash-stack"></i><span>Ventas</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="ventas-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li>
            <a href="agregar_venta.php">
              <i class="bi bi-circle"></i><span>Agregar</span>
            </a>
          </li>
          <li>
            <a href="planilla_caja.php">
              <i class="bi bi-circle"></i><span>Planilla de Caja</span>
            </a>
          </li>
          <li>
            <a href="listados_ventas.php">
              <i class="bi bi-circle"></i><span>Listados</span>
            </a>
          </li>
        </ul>
      </li><!-- End Caja Nav -->

    </ul>

  </aside><!-- End Sidebar-->
