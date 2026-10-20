/**
 * Cliente de API REST y Utilidades Globales para MisterPlan Reservas.
 * Encapsula peticiones Fetch, manejo de errores de red y sistema de Toasts.
 */

const API_BASE = '/api/reservas';

window.api = {
    /**
     * Consulta el listado de reservas con filtros combinados.
     */
    async getReservas(filters = {}) {
        const queryParams = new URLSearchParams();

        if (filters.search) queryParams.set('search', filters.search.trim());
        if (filters.estado) queryParams.set('estado', filters.estado);
        if (filters.fecha_desde) queryParams.set('fecha_desde', filters.fecha_desde);
        if (filters.fecha_hasta) queryParams.set('fecha_hasta', filters.fecha_hasta);

        const url = `${API_BASE}?${queryParams.toString()}`;
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });

        const json = await response.json();
        if (!response.ok) {
            throw new Error(json.message || 'Error al obtener reservas');
        }
        return json;
    },

    /**
     * Obtiene el detalle y eventos de una reserva específica.
     */
    async getReserva(id) {
        const response = await fetch(`${API_BASE}/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        const json = await response.json();
        if (!response.ok) {
            throw new Error(json.message || 'Error al obtener la reserva');
        }
        return json;
    },

    /**
     * Registra una nueva reserva enviando un payload JSON.
     */
    async createReserva(data) {
        const response = await fetch(API_BASE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const json = await response.json();
        if (!response.ok) {
            const error = new Error(json.message || 'Error al crear la reserva');
            error.errors = json.errors || {};
            error.status = response.status;
            throw error;
        }
        return json;
    },

    /**
     * Modifica el estado de una reserva (confirmar o cancelar).
     */
    async updateEstado(id, nuevoEstado, motivo = null) {
        const response = await fetch(`${API_BASE}/${id}/estado`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                estado: nuevoEstado,
                motivo: motivo
            })
        });

        const json = await response.json();
        if (!response.ok) {
            const error = new Error(json.message || 'Error al actualizar el estado de la reserva');
            error.errors = json.errors || {};
            error.status = response.status;
            throw error;
        }
        return json;
    }
};

/**
 * Sistema de notificaciones Toast no intrusivas.
 */
window.showToast = function (message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icon = type === 'success' ? '✓' : '⚠';
    toast.innerHTML = `<span style="font-weight:bold;">${icon}</span> <span>${message}</span>`;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};

/**
 * Función auxiliar para formatear fechas a formato DD/MM/AAAA.
 */
window.formatDate = function (dateString) {
    if (!dateString) return '';
    const parts = dateString.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateString;
};

/**
 * Función auxiliar para formatear números a moneda española (1.250,00 €).
 */
window.formatCurrency = function (amount) {
    const num = parseFloat(amount);
    if (isNaN(num)) return '0,00 €';
    return num.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
};
