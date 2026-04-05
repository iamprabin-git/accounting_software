import { useEffect } from 'react';

export function usePrintWhenReady(enabled) {
    useEffect(() => {
        if (!enabled) {
            return undefined;
        }
        const t = window.setTimeout(() => window.print(), 250);
        return () => window.clearTimeout(t);
    }, [enabled]);
}
