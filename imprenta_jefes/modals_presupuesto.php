<div class="modal fade" id="modalOpcionesAgregar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Agregar al Presupuesto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-3">
                    <button class="btn btn-light text-start p-3 rounded-4 border shadow-sm" onclick="abrirModalManual()" data-bs-dismiss="modal">
                        <i class="bi bi-pencil-square text-primary fs-4 me-2 align-middle"></i> 
                        <span class="fw-bold fs-5 align-middle">Ítem Manual libre</span>
                    </button>
                    <button class="btn btn-light text-start p-3 rounded-4 border shadow-sm" onclick="abrirModalCatalogo()" data-bs-dismiss="modal">
                        <i class="bi bi-tags text-purple fs-4 me-2 align-middle" style="color:#6f42c1;"></i> 
                        <span class="fw-bold fs-5 align-middle">Catálogo Rápido</span>
                    </button>
                    <button class="btn btn-light text-start p-3 rounded-4 border shadow-sm" onclick="abrirModalCotizador()" data-bs-dismiss="modal">
                        <i class="bi bi-calculator text-success fs-4 me-2 align-middle"></i> 
                        <span class="fw-bold fs-5 align-middle">Cotizador Inteligente</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalItemManual" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Ítem Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Descripción del trabajo</label>
                    <textarea id="manualDesc" class="form-control form-control-lg bg-light" rows="2" placeholder="Ej: Diseño de Logo corporativo"></textarea>
                </div>
                <div class="row">
                    <div class="col-4">
                        <label class="form-label small fw-bold text-muted">Cant.</label>
                        <input type="number" id="manualCant" class="form-control form-control-lg bg-light" value="1" min="1">
                    </div>
                    <div class="col-8">
                        <label class="form-label small fw-bold text-muted">Precio Unitario ($)</label>
                        <input type="number" id="manualPrecio" class="form-control form-control-lg bg-light" placeholder="0.00">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary w-100 btn-lg rounded-pill" onclick="agregarItemManual()">Agregar al Carrito</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCatalogo" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 d-flex align-items-center">
                <button type="button" class="btn btn-light rounded-circle me-2" data-bs-toggle="modal" data-bs-target="#modalOpcionesAgregar">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <h5 class="modal-title fw-bold m-0">Catálogo</h5>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="input-group mb-3 shadow-sm rounded-pill overflow-hidden bg-white">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="buscadorCatalogo" class="form-control border-0 py-2 shadow-none" placeholder="Buscar producto..." onkeyup="filtrarCatalogo()">
                </div>
                <div id="listaCatalogo" class="list-group shadow-sm" style="border-radius: 15px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCotizador" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius: 20px; background-color: #f0f2f5;">
            <div class="modal-header bg-white border-0 pb-3 shadow-sm" style="border-radius: 20px 20px 0 0;">
                <button type="button" class="btn btn-light rounded-circle me-2" data-bs-toggle="modal" data-bs-target="#modalOpcionesAgregar">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <div class="w-100 pe-2">
                    <select id="selectorCategoriaCotizador" class="form-select form-select-lg fw-bold border-0 shadow-sm" style="background-color: #e7f1ff; color: #0d6efd;" onchange="cambiarSeccionCotizador()">
                        <option value="apunte">Armar Libro / Apunte</option>
                        <option value="sueltas">Impresiones Sueltas</option>
                        <option value="lonas">Lonas y Vinilos</option>
                        <option value="talonarios">Talonarios</option>
                        <option value="rollos">Plotter y DTF (Rollos)</option>
                        <option value="paquetes">Tarjetas y Volantes</option>
                        <option value="planchas">Rígidos y Stickers</option>
                        <option value="resmas">Impresión por Resmas</option>
                    </select>
                </div>
            </div>
            <div class="modal-body pb-5">
                
                <div id="sec_apunte" class="cotizador-seccion">
                    <div class="seccion-armador" style="border-left-color: #333;">
                        <h6>Tamaño del Apunte</h6>
                        <select id="ap_tamano" class="form-select mb-2" onchange="actualizarListasDinamicas()">
                            <option value="a4">A4</option><option value="oficio">Oficio</option><option value="a3">A3</option>
                        </select>
                    </div>
                    <div class="seccion-armador" style="border-left-color: #6c757d;">
                        <h6>Interior B&N</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input type="number" id="ap_bn_cant" class="form-control" placeholder="Carillas" min="0"></div>
                            <div class="col-6"><select id="ap_bn_modo" class="form-select"><option value="sf">Simple</option><option value="df">Doble</option></select></div>
                            <div class="col-12"><select id="ap_bn_papel" class="form-select"></select></div>
                        </div>
                    </div>
                    <div class="seccion-armador" style="border-left-color: #ff007f;">
                        <h6>Interior a Color</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input type="number" id="ap_color_cant" class="form-control" placeholder="Carillas" min="0"></div>
                            <div class="col-6"><select id="ap_color_modo" class="form-select"><option value="sf">Simple</option><option value="df">Doble</option></select></div>
                            <div class="col-12"><select id="ap_color_papel" class="form-select"></select></div>
                        </div>
                    </div>
                    <div class="seccion-armador" style="border-left-color: #ffc107;">
                        <h6>Tapas / Portadas</h6>
                        <div class="row g-2"><div class="col-6"><input type="number" id="ap_tapas_cant" class="form-control" placeholder="Carillas Tapas" min="0"></div><div class="col-6"><select id="ap_tapas_papel" class="form-select"></select></div></div>
                    </div>
                    <div class="seccion-armador" style="border-left-color: #28a745;">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="ap_anillado"><label class="form-check-label fw-bold">Agregar Anillado</label></div>
                    </div>
                </div>

                <div id="sec_sueltas" class="cotizador-seccion d-none">
                    <div class="seccion-armador">
                        <h6>Configuración</h6>
                        <input type="number" id="hs_cant" class="form-control mb-2" value="1" min="1" placeholder="Cantidad">
                        <select id="hs_modo" class="form-select mb-2"><option value="sf">Simple Faz</option><option value="df">Doble Faz</option></select>
                        <select id="hs_tipo" class="form-select"></select>
                    </div>
                </div>

                <div id="sec_lonas" class="cotizador-seccion d-none">
                    <div class="alert alert-info small py-2">Ancho máx 150cm. Superado esto requiere soldadura.</div>
                    <div class="seccion-armador">
                        <select id="lv_material" class="form-select mb-2" onchange="toggleLonaOpciones()"></select>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input type="number" id="lv_ancho" class="form-control" placeholder="Ancho (cm)" min="0"></div>
                            <div class="col-6"><input type="number" id="lv_alto" class="form-control" placeholder="Alto (cm)" min="0"></div>
                        </div>
                        <select id="lv_bordes" class="form-select" onchange="toggleLonaOpciones()"><option value="ras">Al ras</option><option value="blanco">Excedente blanco</option><option value="costura" id="opt_costura">Costura perimetral</option></select>
                        
                        <div id="panel_costura" class="d-none mt-3 p-2 bg-white rounded border">
                            <label class="small fw-bold">Lados con costura:</label>
                            <div class="d-flex gap-2 mb-2">
                                <label><input type="checkbox" id="cost_arriba" checked> Arr</label>
                                <label><input type="checkbox" id="cost_abajo" checked> Abj</label>
                                <label><input type="checkbox" id="cost_izq"> Izq</label>
                                <label><input type="checkbox" id="cost_der"> Der</label>
                            </div>
                            <input type="number" id="lv_argollas_cant" class="form-control" placeholder="Cantidad de Argollas" min="0">
                        </div>
                    </div>
                </div>

                <div id="sec_talonarios" class="cotizador-seccion d-none">
                    <div class="seccion-armador" style="border-left-color: #6c757d;">
                        <h6>Principal</h6>
                        <select id="tal_tipo" class="form-select mb-2"><option value="Talonarios">Talonarios</option><option value="Recetarios (RP)">Recetarios (RP)</option><option value="Anotadores">Anotadores</option><option value="Entradas">Entradas</option></select>
                        <select id="tal_tamano" class="form-select mb-2"><option value="medio">Medio Oficio</option><option value="entero">A4/Oficio</option><option value="cuarto">1/4 Oficio</option><option value="octavo">1/8 Oficio</option></select>
                        <input type="number" id="tal_cant" class="form-control" placeholder="Cant. Blocks" min="1">
                    </div>
                    <div class="seccion-armador" style="border-left-color: #17a2b8;">
                        <h6>Formato y Terminación</h6>
                        <select id="tal_formato" class="form-select mb-2"><option value="original">100 Originales</option><option value="duplicado">50 Orig. + 50 Dupl.</option><option value="triplicado">25 Orig. + Dupl. + Trip.</option></select>
                        <select id="tal_terminacion" class="form-select mb-2"><option value="Engomado">Engomado</option><option value="Abrochado y Troquelado">Abrochado y Troquelado</option><option value="Suelto">Suelto (Sin encuadernar)</option></select>
                        <select id="tal_numerado" class="form-select"><option value="Sin Numerar">Sin numerar</option><option value="Numerado">Sí (Numerado)</option></select>
                    </div>
                </div>

                <div id="sec_rollos" class="cotizador-seccion d-none">
                    <div class="seccion-armador" style="border-left-color: #e83e8c;">
                        <div class="alert alert-info small py-2">Rollo ancho 60cm. Mínimo 30 cm lineales.</div>
                        <select id="rl_material" class="form-select mb-2"></select>
                        <input type="number" id="rl_largo" class="form-control" placeholder="Largo a imprimir (cm)" min="1">
                    </div>
                </div>

                <div id="sec_paquetes" class="cotizador-seccion d-none">
                    <div class="seccion-armador" style="border-left-color: #6f42c1;">
                        <select id="pq_tipo" class="form-select mb-2" onchange="togglePaquetes()"><option value="tarjetas">Tarjetas Personales</option><option value="volantes">Volantes (10x15cm)</option></select>
                        <select id="pq_tarjeta_sel" class="form-select mb-2" onchange="toggleRedondeado()"></select>
                        <select id="pq_volante_sel" class="form-select mb-2 d-none"></select>
                        
                        <div class="form-check form-switch mb-3 mt-2">
                            <input class="form-check-input" type="checkbox" id="pq_tarj_redondeado" disabled>
                            <label class="form-check-label fw-bold small text-muted" id="lbl_redondeado">Redondeado (Solo x1000)</label>
                        </div>
                        <input type="number" id="pq_cant" class="form-control" placeholder="Cant. de Paquetes" value="1" min="1">
                    </div>
                </div>

                <div id="sec_planchas" class="cotizador-seccion d-none">
                    <div class="seccion-armador" style="border-left-color: #fd7e14;">
                        <select id="pl_categoria" class="form-select mb-2" onchange="togglePlanchas()"><option value="rigidos">Cartelería Rígida</option><option value="imanes">Imanes</option><option value="stickers">Stickers</option></select>
                        <select id="pl_sel_rigidos" class="form-select mb-2"></select>
                        <select id="pl_sel_imanes" class="form-select mb-2 d-none"></select>
                        <select id="pl_sel_stickers" class="form-select mb-2 d-none"></select>
                        <input type="number" id="pl_cant" class="form-control" value="1" min="1" placeholder="Cantidad">
                    </div>
                </div>

                <div id="sec_resmas" class="cotizador-seccion d-none">
                    <div class="seccion-armador" style="border-left-color: #dc3545;">
                        <select id="rs_chapa" class="form-select mb-2"><option value="exist">Con Chapa Existente</option><option value="nueva">Con Chapa Nueva</option></select>
                        <select id="rs_papel" class="form-select mb-2"><option value="blanca">Hoja Blanca</option><option value="pintado">Fondo Pintado</option><option value="color">Hoja de Color</option></select>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><input type="number" id="rs_cant" class="form-control" placeholder="Resmas" min="1"></div>
                            <div class="col-6"><input type="number" id="rs_colores" class="form-control" value="1" min="1" placeholder="Colores"></div>
                        </div>
                        <div class="form-check form-switch mb-1 mt-3"><input class="form-check-input" type="checkbox" id="rs_emblocado"><label class="form-check-label fw-bold">Emblocado / Troquel</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="rs_numerado"><label class="form-check-label fw-bold">Numerado</label></div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white border-0 shadow-sm" style="border-radius: 0 0 20px 20px;">
                <button type="button" class="btn btn-success w-100 btn-lg rounded-pill fw-bold" onclick="ejecutarCotizadorMobile()">
                    Añadir al Presupuesto <i class="bi bi-check-circle ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>