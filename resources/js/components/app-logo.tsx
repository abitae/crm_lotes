import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

type Shared = {
    name: string;
    brandingLogoUrl?: string | null;
};

export default function AppLogo() {
    const { name, brandingLogoUrl } = usePage<Shared>().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                {brandingLogoUrl ? (
                    <img src={brandingLogoUrl} alt="" className="size-full object-contain p-0.5" />
                ) : (
                    <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                )}
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">{name}</span>
            </div>
        </>
    );
}
