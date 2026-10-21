/**
 * Interacción de la Página Principal de Reservas (Listado, Filtros, KPIs y Modales).

 */

document.addEventListener('DOMContentLoaded', () => {
    // Referencias al DOM - Filtros y KPIs
    const formFiltros = document.getElementById('form-filtros');
    const inputSearch = document.getElementById('filtro-search');
    const inputEstado = document.getElementById('filtro-estado');
    const inputFechaDesde = document.getElementById('filtro-fecha-desde');
    const inputFechaHasta = document.getElementById('filtro-fecha-hasta');
    const btnLimpiarFiltros = document.getElementById('btn-limpiar-filtros');
    const contadorNumero = document.getElementById('contador-numero');
    const tbodyReservas = document.getElementById('tbody-reservas');
    const loadingSpinner = document.getElementById('loading-spinner');
    const statusChips = document.querySelectorAll('.status-chip');

    // Referencias KPI
    const kpiTotal = document.getElementById('kpi-total');
    const kpiConfirmadas = document.getElementById('kpi-confirmadas');
    const kpiPendientes = document.getElementById('kpi-pendientes');
    const kpiCanceladas = document.getElementById('kpi-canceladas');
    const kpiFacturado = document.getElementById('kpi-facturado');

    // Referencias al DOM - Modal Crear
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const modalCrear = document.getElementById('modal-crear');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal');
    const btnCancelarModal = document.getElementById('btn-cancelar-modal');
    const formCrearReserva = document.getElementById('form-crear-reserva');
    const btnSubmitCrear = document.getElementById('btn-submit-crear');
    const modalAlertError = document.getElementById('modal-alert-error');

    // Referencias al DOM - Modal Cancelar
    const modalCancelar = document.getElementById('modal-cancelar');
    const btnCerrarCancelModal = document.getElementById('btn-cerrar-cancel-modal');
    const btnAbortCancel = document.getElementById('btn-abort-cancel');
    const btnConfirmCancel = document.getElementById('btn-confirm-cancel');
    const cancelModalNombre = document.getElementById('cancel-modal-nombre');
    const cancelModalId = document.getElementById('cancel-modal-id');
    const cancelMotivo = document.getElementById('cancel-motivo');

    let cancelTargetId = null;
    let searchDebounceTimeout = null;

    // ==========================================================
    // 1. FILTRADO DINÁMICO SIN RECARGA
    // ==========================================================
    function obtenerFiltrosActuales() {
        return {
            search: inputSearch.value,
            estado: inputEstado.value,
            fecha_desde: inputFechaDesde.value,
            fecha_hasta: inputFechaHasta.value
        };
    }

    async function aplicarFiltros() {
        try {
            mostrarCargando(true);
            const filters = obtenerFiltrosActuales();
            const response = await window.api.getReservas(filters);
            
            repintarTabla(response.data);
            contadorNumero.textContent = response.count;

            if (response.stats) {
                actualizarKPIs(response.stats);
            }
        } catch (error) {
            window.showToast('Error de red al aplicar filtros: ' + error.message, 'error');
        } finally {
            mostrarCargando(false);
        }
    }

    function mostrarCargando(activo) {
        if (loadingSpinner) {
            loadingSpinner.style.display = activo ? 'block' : 'none';
        }
    }

    function actualizarKPIs(stats) {
        if (kpiTotal && stats.total !== undefined) kpiTotal.textContent = stats.total;
        if (kpiConfirmadas && stats.confirmadas !== undefined) kpiConfirmadas.textContent = stats.confirmadas;
        if (kpiPendientes && stats.pendientes !== undefined) kpiPendientes.textContent = stats.pendientes;
        if (kpiCanceladas && stats.canceladas !== undefined) kpiCanceladas.textContent = stats.canceladas;
        if (kpiFacturado && stats.total_facturado !== undefined) {
            kpiFacturado.textContent = window.formatCurrency(stats.total_facturado);
        }
    }

    // Debounce para búsqueda en tiempo real
    inputSearch.addEventListener('input', () => {
        clearTimeout(searchDebounceTimeout);
        searchDebounceTimeout = setTimeout(aplicarFiltros, 280);
    });

    inputFechaDesde.addEventListener('change', aplicarFiltros);
    inputFechaHasta.addEventListener('change', aplicarFiltros);

    // Pestañas / Chips rápidos de estado
    statusChips.forEach(chip => {
        chip.addEventListener('click', () => {
            statusChips.forEach(c => {
                c.classList.remove('active');
                c.setAttribute('aria-selected', 'false');
            });
            chip.classList.add('active');
            chip.setAttribute('aria-selected', 'true');

            inputEstado.value = chip.getAttribute('data-estado');
            aplicarFiltros();
        });
    });

    btnLimpiarFiltros.addEventListener('click', () => {
        formFiltros.reset();
        inputEstado.value = '';
        statusChips.forEach(c => {
            c.classList.remove('active');
            c.setAttribute('aria-selected', 'false');
        });
        const chipTodas = document.querySelector('.status-chip[data-estado=""]');
        if (chipTodas) {
            chipTodas.classList.add('active');
            chipTodas.setAttribute('aria-selected', 'true');
        }
        aplicarFiltros();
    });

    // ==========================================================
    // 2. RENDERIZADO DINÁMICO DE LA TABLA
    // ==========================================================
    function repintarTabla(reservas) {
        tbodyReservas.innerHTML = '';

        if (!reservas || reservas.length === 0) {
            tbodyReservas.innerHTML = `
                <tr id="row-empty-state">
                    <td colspan="7" class="empty-state">
                        <div class="empty-state-card">
                            <div class="empty-icon-circle">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </div>
                            <h3 class="empty-title">No se encontraron reservas</h3>
                            <p class="empty-desc">Ninguna reserva coincide con los criterios seleccionados.</p>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('btn-limpiar-filtros').click();">
                                Restablecer filtros
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        reservas.forEach(r => {
            const tr = document.createElement('tr');
            tr.id = `reserva-row-${r.id}`;
            tr.setAttribute('data-id', r.id);
            tr.className = 'booking-row';

            const iniciales = obtenerIniciales(r.nombre_huesped);
            const esCancelada = r.estado === 'cancelada';
            const botonCancelar = !esCancelada ? `
                <button 
                    type="button" 
                    class="btn-action btn-action-cancel btn-trigger-cancel" 
                    data-id="${r.id}" 
                    data-nombre="${escapeHtml(r.nombre_huesped)}"
                    title="Cancelar reserva"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span>Cancelar</span>
                </button>
            ` : '';

            tr.innerHTML = `
                <td class="id-cell">
                    <span class="id-badge">#${r.id}</span>
                </td>
                <td class="guest-cell">
                    <div class="guest-wrapper">
                        <div class="guest-avatar" aria-hidden="true">${escapeHtml(iniciales)}</div>
                        <div class="guest-details">
                            <span class="guest-name">${escapeHtml(r.nombre_huesped)}</span>
                            <span class="guest-email">${escapeHtml(r.email_huesped)}</span>
                        </div>
                    </div>
                </td>
                <td class="hotel-cell">
                    <div class="hotel-wrapper">
                        <svg class="hotel-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span class="hotel-name">${escapeHtml(r.nombre_alojamiento)}</span>
                    </div>
                </td>
                <td class="dates-cell">
                    <div class="stay-dates">
                        <span class="date-tag">${window.formatDate(r.fecha_entrada)}</span>
                        <span class="dates-sep">→</span>
                        <span class="date-tag">${window.formatDate(r.fecha_salida)}</span>
                    </div>
                </td>
                <td class="amount-cell">
                    <span class="price-value">${window.formatCurrency(r.importe)}</span>
                </td>
                <td class="status-cell">
                    <span class="status-pill pill-${escapeHtml(r.estado)}" id="badge-estado-${r.id}">
                        <span class="pill-dot"></span>
                        ${escapeHtml(capitalize(r.estado))}
                    </span>
                </td>
                <td class="actions-cell">
                    <div class="action-buttons-group">
                        <a href="/reservas/${r.id}" class="btn-action btn-action-view" title="Ver detalle completo e historial">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <span>Detalle</span>
                        </a>
                        ${botonCancelar}
                    </div>
                </td>
            `;

            tbodyReservas.appendChild(tr);
        });
    }

    // ==========================================================
    // 3. MODAL: CREAR NUEVA RESERVA
    // ==========================================================
    function abrirModalCrear() {
        formCrearReserva.reset();
        limpiarErroresFormulario();
        modalAlertError.style.display = 'none';
        modalCrear.style.display = 'flex';
        document.getElementById('nuevo-nombre').focus();
    }

    function cerrarModalCrear() {
        modalCrear.style.display = 'none';
    }

    btnAbrirModal.addEventListener('click', abrirModalCrear);
    btnCerrarModal.addEventListener('click', cerrarModalCrear);
    btnCancelarModal.addEventListener('click', cerrarModalCrear);

    modalCrear.addEventListener('click', (e) => {
        if (e.target === modalCrear) cerrarModalCrear();
    });

    formCrearReserva.addEventListener('submit', async (e) => {
        e.preventDefault();
        limpiarErroresFormulario();

        const formData = {
            nombre_huesped: document.getElementById('nuevo-nombre').value.trim(),
            email_huesped: document.getElementById('nuevo-email').value.trim(),
            nombre_alojamiento: document.getElementById('nuevo-alojamiento').value.trim(),
            fecha_entrada: document.getElementById('nuevo-entrada').value,
            fecha_salida: document.getElementById('nuevo-salida').value,
            importe: document.getElementById('nuevo-importe').value,
            estado: document.getElementById('nuevo-estado').value,
            notas: document.getElementById('nuevo-notas').value.trim()
        };

        const erroresCliente = validarEnCliente(formData);
        if (Object.keys(erroresCliente).length > 0) {
            mostrarErroresCampos(erroresCliente);
            return;
        }

        setLoadingBotonCrear(true);

        try {
            const respuesta = await window.api.createReserva(formData);

            cerrarModalCrear();
            window.showToast(respuesta.message || 'Reserva creada exitosamente', 'success');

            // Actualizar tabla y estadísticas
            await aplicarFiltros();
        } catch (error) {
            if (error.errors && Object.keys(error.errors).length > 0) {
                mostrarErroresCampos(error.errors);
            } else {
                modalAlertError.textContent = error.message;
                modalAlertError.style.display = 'block';
            }
        } finally {
            setLoadingBotonCrear(false);
        }
    });

    function validarEnCliente(data) {
        const errores = {};

        if (!data.nombre_huesped || data.nombre_huesped.length < 3) {
            errores.nombre_huesped = 'Introduce al menos 3 caracteres.';
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!data.email_huesped || !emailRegex.test(data.email_huesped)) {
            errores.email_huesped = 'Introduce un email válido.';
        }

        if (!data.nombre_alojamiento || data.nombre_alojamiento.length < 3) {
            errores.nombre_alojamiento = 'Introduce el nombre del alojamiento.';
        }

        if (!data.fecha_entrada) {
            errores.fecha_entrada = 'Selecciona la fecha de entrada.';
        }

        if (!data.fecha_salida) {
            errores.fecha_salida = 'Selecciona la fecha de salida.';
        }

        if (data.fecha_entrada && data.fecha_salida && data.fecha_salida <= data.fecha_entrada) {
            errores.fecha_salida = 'La salida debe ser posterior a la entrada.';
        }

        if (!data.importe || isNaN(data.importe) || parseFloat(data.importe) <= 0) {
            errores.importe = 'El importe debe ser mayor a 0.';
        }

        return errores;
    }

    function mostrarErroresCampos(errores) {
        for (const [campo, mensaje] of Object.entries(errores)) {
            const elError = document.getElementById(`error-${campo}`);
            if (elError) {
                elError.textContent = mensaje;
            }
        }
    }

    function limpiarErroresFormulario() {
        document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
        modalAlertError.style.display = 'none';
        modalAlertError.textContent = '';
    }

    function setLoadingBotonCrear(loading) {
        const textSpan = btnSubmitCrear.querySelector('.btn-text');
        const spinnerSpan = btnSubmitCrear.querySelector('.btn-spinner');
        btnSubmitCrear.disabled = loading;
        textSpan.style.display = loading ? 'none' : 'inline';
        spinnerSpan.style.display = loading ? 'inline' : 'none';
    }

    // ==========================================================
    // 4. CANCELACIÓN DE RESERVAS
    // ==========================================================
    tbodyReservas.addEventListener('click', (e) => {
        const btnCancel = e.target.closest('.btn-trigger-cancel');
        if (!btnCancel) return;

        cancelTargetId = btnCancel.getAttribute('data-id');
        const nombreHuesped = btnCancel.getAttribute('data-nombre');

        cancelModalId.textContent = `#${cancelTargetId}`;
        cancelModalNombre.textContent = nombreHuesped;
        cancelMotivo.value = '';

        modalCancelar.style.display = 'flex';
    });

    function cerrarModalCancelar() {
        modalCancelar.style.display = 'none';
        cancelTargetId = null;
    }

    btnCerrarCancelModal.addEventListener('click', cerrarModalCancelar);
    btnAbortCancel.addEventListener('click', cerrarModalCancelar);
    modalCancelar.addEventListener('click', (e) => {
        if (e.target === modalCancelar) cerrarModalCancelar();
    });

    btnConfirmCancel.addEventListener('click', async () => {
        if (!cancelTargetId) return;

        const motivo = cancelMotivo.value.trim();
        const textSpan = btnConfirmCancel.querySelector('.btn-text');
        const spinnerSpan = btnConfirmCancel.querySelector('.btn-spinner');

        btnConfirmCancel.disabled = true;
        textSpan.style.display = 'none';
        spinnerSpan.style.display = 'inline';

        try {
            const respuesta = await window.api.updateEstado(cancelTargetId, 'cancelada', motivo || null);

            cerrarModalCancelar();
            window.showToast(respuesta.message || 'Reserva cancelada con éxito', 'success');

            // Refrescar lista y métricas
            await aplicarFiltros();
        } catch (error) {
            window.showToast('No se pudo cancelar: ' + error.message, 'error');
        } finally {
            btnConfirmCancel.disabled = false;
            textSpan.style.display = 'inline';
            spinnerSpan.style.display = 'none';
        }
    });

    // Tecla Escape para cerrar modales
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            cerrarModalCrear();
            cerrarModalCancelar();
        }
    });

    // Funciones auxiliares
    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    function capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function obtenerIniciales(nombre) {
        if (!nombre) return 'MP';
        const partes = nombre.trim().split(/\s+/);
        if (partes.length === 1) return partes[0].substring(0, 2).toUpperCase();
        return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase();
    }
});
