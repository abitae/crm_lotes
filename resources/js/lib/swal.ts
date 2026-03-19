import Swal from 'sweetalert2';

/**
 * Muestra un cuadro de confirmación para eliminar. Retorna true si el usuario confirma.
 */
export async function confirmDelete(title: string, text?: string): Promise<boolean> {
    const result = await Swal.fire({
        title,
        text: text ?? 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    });
    return result.isConfirmed;
}

/**
 * Confirma marcar una comisión como pagada en liquidaciones Inmopro.
 */
export async function confirmCommissionMarkPaid(details: {
    advisorName: string;
    amountLabel: string;
    lotLabel: string;
    projectName: string;
}): Promise<boolean> {
    const result = await Swal.fire({
        title: '¿Marcar como pagado?',
        html: `<p style="margin:0;text-align:left;font-size:0.95rem;line-height:1.5;color:#475569">
            Vas a registrar el pago de la comisión de <strong>${escapeHtml(details.advisorName)}</strong>
            por <strong>${escapeHtml(details.amountLabel)}</strong>.<br/><br/>
            Lote <strong>${escapeHtml(details.lotLabel)}</strong> · ${escapeHtml(details.projectName)}
        </p>`,
        icon: 'question',
        showCancelButton: true,
        focusCancel: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, marcar pagado',
        cancelButtonText: 'Cancelar',
    });

    return result.isConfirmed;
}

function escapeHtml(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

export function showSuccessToast(message: string): void {
    void Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
    });
}

export { Swal };
