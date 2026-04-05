/**
 * Company letterhead — visible only when printing (browser print / Ctrl+P).
 */
export default function PrintLetterhead({ letterhead }) {
    if (!letterhead?.name) {
        return null;
    }

    return (
        <header className="mb-6 hidden print:block">
            <div className="flex flex-col gap-4 pb-1 sm:flex-row sm:items-start">
                {letterhead.logo_url ? (
                    <img
                        src={letterhead.logo_url}
                        alt=""
                        className="h-16 max-h-24 w-auto max-w-[180px] shrink-0 object-contain object-left"
                    />
                ) : null}
                <div className="min-w-0 flex-1 items-center">
                    <h1 className="text-xl font-bold tracking-tight text-black text-center">
                        {letterhead.name}
                    </h1>
                    {letterhead.address ? (
                        <p className="mt-1 whitespace-pre-line text-sm leading-relaxed text-gray-900 text-center">
                            {letterhead.address}
                        </p>
                    ) : null}
                    {letterhead.phone ? (
                        <p className="mt-1 text-sm text-gray-900 text-center">
                            <span className="font-semibold">phone:</span>{' '}
                            {letterhead.phone}
                        </p>
                    ) : null}
                </div>
            </div>
        </header>
    );
}
