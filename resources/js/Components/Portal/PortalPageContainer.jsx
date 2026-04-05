import { cn } from '@/lib/utils';

/**
 * Consistent horizontal padding, max width, and bottom safe area for mobile tab bar.
 */
export default function PortalPageContainer({ children, className }) {
    return (
        <div
            className={cn(
                'mx-auto w-full max-w-4xl px-4 pb-24 pt-6 sm:px-6 md:pb-10 md:pt-8 lg:px-8 print:pb-6 print:pt-4',
                className,
            )}
        >
            {children}
        </div>
    );
}
