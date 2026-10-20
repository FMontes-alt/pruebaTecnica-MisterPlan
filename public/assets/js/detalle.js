/**
 * Interacción de la Página de Detalle de Reserva (Confirmación, Cancelación y Timeline Dinámico).
 * JavaScript Vanilla sin frameworks.
 */

document.addEventListener('DOMContentLoaded', () => {
    const btnConfirmar = document.getElementById('btn-confirmar-reserva');
    const btnCancelarDetalle = document.getElementById('btn-cancelar-detalle');
    const modalCancelarDetalle = document.getElementById('modal-cancelar-detalle');
    const btnCerrarCancelDetalle = document.getElementById('btn-cerrar-cancel-detalle');
    const btnAbortCancelDetalle = document.getElementById('btn-abort-cancel-detalle');
    const btnConfirmCancelDetalle = document.getElementById('btn-confirm-cancel-detalle');
    const cancelMotivoDetalle = document.getElementById('cancel-motivo-detalle');
    const badgeEstado = document.getElementById('detalle-badge-estado');
    const timeline = document.getElementById('eventos-timeline');
    const actionContainer = document.getElementById('detalle-action-container');

    // ==========================================================
    // 1. CONFIRMAR RESERVA
    // ==========================================================
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', async () => {
            const reservaId = btnConfirmar.getAttribute('data-id');
            const textSpan = btnConfirmar.querySelector('.btn-text');
            const spinnerSpan = btnConfirmar.querySelector('.btn-spinner');

            btnConfirmar.disabled = true;
            textSpan.style.display = 'none';
            spinnerSpan.style.display = 'inline';

            try {
                const respuesta = await window.api.updateEstado(reservaId, 'confirmada', 'Confirmada manualmente desde la ficha de reserva.');

                window.showToast(respuesta.message || 'Reserva confirmada con éxito', 'success');

                // Actualizar badge
                if (badgeEstado) {
                    badgeEstado.className = 'badge badge-confirmada';
                    badgeEstado.textContent = 'Confirmada';
                }

                // Quitar botón de confirmar (ya está confirmada)
                btnConfirmar.remove();

                // Añadir evento al timeline en pantalla sin recargar
                agregarEventoAlTimeline('CONFIRMACION', 'Cambio de estado: \'pendiente\' → \'confirmada\'. Confirmada manualmente desde la ficha de reserva.');

            } catch (error) {
                window.showToast('No se pudo confirmar la reserva: ' + error.message, 'error');
                btnConfirmar.disabled = false;
                textSpan.style.display = 'inline';
                spinnerSpan.style.display = 'none';
            }
        });
    }

    // ==========================================================
    // 2. CANCELAR RESERVA CON MODAL
    // ==========================================================
    if (btnCancelarDetalle) {
        btnCancelarDetalle.addEventListener('click', () => {
            cancelMotivoDetalle.value = '';
            modalCancelarDetalle.style.display = 'flex';
        });
    }

    function cerrarModalCancelarDetalle() {
        if (modalCancelarDetalle) {
            modalCancelarDetalle.style.display = 'none';
        }
    }

    if (btnCerrarCancelDetalle) btnCerrarCancelDetalle.addEventListener('click', cerrarModalCancelarDetalle);
    if (btnAbortCancelDetalle) btnAbortCancelDetalle.addEventListener('click', cerrarModalCancelarDetalle);
    if (modalCancelarDetalle) {
        modalCancelarDetalle.addEventListener('click', (e) => {
            if (e.target === modalCancelarDetalle) cerrarModalCancelarDetalle();
        });
    }

    if (btnConfirmCancelDetalle) {
        btnConfirmCancelDetalle.addEventListener('click', async () => {
            const reservaId = btnConfirmCancelDetalle.getAttribute('data-id');
            const motivo = cancelMotivoDetalle.value.trim();
            const textSpan = btnConfirmCancelDetalle.querySelector('.btn-text');
            const spinnerSpan = btnConfirmCancelDetalle.querySelector('.btn-spinner');

            btnConfirmCancelDetalle.disabled = true;
            textSpan.style.display = 'none';
            spinnerSpan.style.display = 'inline';

            try {
                const respuesta = await window.api.updateEstado(reservaId, 'cancelada', motivo || null);

                cerrarModalCancelarDetalle();
                window.showToast(respuesta.message || 'Reserva cancelada exitosamente', 'success');

                // Actualizar badge
                if (badgeEstado) {
                    badgeEstado.className = 'badge badge-cancelada';
                    badgeEstado.textContent = 'Cancelada';
                }

                // Al estar cancelada, ocultamos todas las acciones posibles
                if (actionContainer) {
                    actionContainer.innerHTML = '';
                }

                // Añadir evento al timeline
                const detalleMotivo = motivo ? ` Motivo / Detalle: ${motivo}` : '';
                agregarEventoAlTimeline('CANCELACION', `Cambio de estado: a 'cancelada'.${detalleMotivo}`);

            } catch (error) {
                window.showToast('Error al cancelar la reserva: ' + error.message, 'error');
            } finally {
                btnConfirmCancelDetalle.disabled = false;
                textSpan.style.display = 'inline';
                spinnerSpan.style.display = 'none';
            }
        });
    }

    // ==========================================================
    // 3. AUXILIAR: AGREGAR EVENTO AL TIMELINE EN VIVO
    // ==========================================================
    function agregarEventoAlTimeline(tipo, descripcion) {
        if (!timeline) return;

        const item = document.createElement('div');
        item.className = `timeline-item timeline-${tipo.toLowerCase()}`;

        const ahora = new Date();
        const fechaFormateada = ahora.toLocaleDateString('es-ES') + ' ' + ahora.toLocaleTimeString('es-ES');

        item.innerHTML = `
            <div class="timeline-marker"></div>
            <div class="timeline-content">
                <div class="timeline-header">
                    <span class="timeline-tag tag-${tipo.toLowerCase()}">
                        ${tipo}
                    </span>
                    <time class="timeline-date">
                        ${fechaFormateada}
                    </time>
                </div>
                <p class="timeline-description">
                    ${escapeHtml(descripcion)}
                </p>
            </div>
        `;

        timeline.appendChild(item);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
});
