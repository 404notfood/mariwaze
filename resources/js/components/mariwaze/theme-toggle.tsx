import Moon from 'lucide-react/icons/moon';
import Sun from 'lucide-react/icons/sun';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/hooks/use-appearance';

export default function ThemeToggle() {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';

    return (
        <Button
            variant="ghost"
            size="icon"
            className="size-8 text-sidebar-foreground/70 hover:text-sidebar-foreground"
            onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
            title={isDark ? 'Mode clair' : 'Mode sombre nautique'}
        >
            {isDark ? <Sun className="size-4" /> : <Moon className="size-4" />}
        </Button>
    );
}
