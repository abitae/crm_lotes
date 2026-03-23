import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

type Shared = {
    name: string;
    brandingLogoUrl?: string | null;
    brandingTagline?: string | null;
    brandingPrimaryColor?: string;
};

export default function AppLogo() {
    const { name, brandingLogoUrl, brandingTagline, brandingPrimaryColor } = usePage<Shared>().props;
    const accent = brandingPrimaryColor ?? '#059669';

    return (
        <>
            <div
                className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md text-white dark:text-black"
                style={{ backgroundColor: accent }}
            >
                {brandingLogoUrl ? (
                    <img src={brandingLogoUrl} alt="" className="size-full bg-white object-contain p-0.5 dark:bg-slate-950" />
                ) : (
                    <AppLogoIcon className="size-5 fill-current" />
                )}
            </div>
            <div className="ml-1 grid min-w-0 flex-1 text-left text-sm">
                <span className="truncate leading-tight font-semibold">{name}</span>
                {brandingTagline ? (
                    <span className="truncate text-xs text-muted-foreground">{brandingTagline}</span>
                ) : null}
            </div>
        </>
    );
}
