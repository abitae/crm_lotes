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

export { Swal };
