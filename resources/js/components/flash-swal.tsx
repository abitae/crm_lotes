import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { Swal } from '@/lib/swal';

type Flash = { success?: string; error?: string };

export default function FlashSwal() {
    const { props } = usePage<{ flash?: Flash }>();
    const flash = props.flash ?? {};
    const shown = useRef<string | null>(null);

    useEffect(() => {
        const key = flash.success ?? flash.error ?? null;
        if (!key) {
            shown.current = null;
            return;
        }
        if (shown.current === key) return;
        shown.current = key;

        if (flash.success) {
            Swal.fire({
                icon: 'success',
                title: 'Listo',
                text: flash.success,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
            });
        } else if (flash.error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: flash.error,
                confirmButtonColor: '#64748b',
            });
        }
    }, [flash.success, flash.error]);

    return null;
}
