import MariwazeLogoIcon from '@/components/mariwaze/mariwaze-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-sm border-[1.5px] border-sidebar-primary text-sidebar-primary">
                <MariwazeLogoIcon className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-base leading-tight font-semibold tracking-[0.08em] uppercase">
                    MariWaze
                </span>
                <span className="pw-label truncate !text-sidebar-foreground/60">
                    Copilote de croisière
                </span>
            </div>
        </>
    );
}
