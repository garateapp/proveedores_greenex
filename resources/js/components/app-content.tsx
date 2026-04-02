import { SidebarInset } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import * as React from 'react';

interface AppContentProps extends React.ComponentProps<'main'> {
    variant?: 'header' | 'sidebar';
}

export function AppContent({
    variant = 'header',
    children,
    className,
    ...props
}: AppContentProps) {
    if (variant === 'sidebar') {
        return (
            <SidebarInset
                className={cn('portal-main-content', className)}
                {...props}
            >
                {children}
            </SidebarInset>
        );
    }

    return (
        <main
            className={cn(
                'portal-main-content mx-auto my-4 flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl px-3 py-3 sm:px-4 sm:py-4',
                className,
            )}
            {...props}
        >
            {children}
        </main>
    );
}
