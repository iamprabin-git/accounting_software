import PortalHeaderBlock from '@/Components/Portal/PortalHeaderBlock';
import PortalPageContainer from '@/Components/Portal/PortalPageContainer';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { cn } from '@/lib/utils';
import { formatDisplayDateTime } from '@/utils/dateDisplay';
import { Head, useForm } from '@inertiajs/react';

export default function Messages({ messages, company_name }) {
    const form = useForm({ body: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('portal.messages.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset('body'),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <PortalHeaderBlock
                    title="Secure messaging"
                    description={`Private channel with ${company_name}. Only your company sees this thread.`}
                />
            }
        >
            <Head title="Messages" />

            <PortalPageContainer>
                <Card className="border-slate-200/90 shadow-sm dark:border-slate-800">
                    <CardHeader className="border-b border-slate-100 dark:border-slate-800">
                        <CardTitle className="text-base">Conversation</CardTitle>
                        <CardDescription>
                            Replies from staff appear on the left; your messages
                            on the right.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4 pt-6">
                        <div
                            className="max-h-[min(28rem,55vh)] space-y-3 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/80 p-3 sm:max-h-[28rem] sm:p-4 dark:border-slate-800 dark:bg-slate-900/40"
                            role="log"
                            aria-live="polite"
                        >
                            {messages.length === 0 ? (
                                <p className="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    No messages yet. Introduce yourself below.
                                </p>
                            ) : (
                                messages.map((m) => (
                                    <div
                                        key={m.id}
                                        className={cn(
                                            'flex w-full',
                                            m.from_you ? 'justify-end' : 'justify-start',
                                        )}
                                    >
                                        <div
                                            className={cn(
                                                'max-w-[min(100%,20rem)] rounded-2xl px-4 py-2.5 text-sm shadow-sm sm:max-w-[24rem]',
                                                m.from_you
                                                    ? 'rounded-br-md bg-emerald-600 text-white dark:bg-emerald-700'
                                                    : 'rounded-bl-md border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100',
                                            )}
                                        >
                                            <p className="whitespace-pre-wrap leading-relaxed">
                                                {m.body}
                                            </p>
                                            <p
                                                className={cn(
                                                    'mt-2 text-[10px] font-medium',
                                                    m.from_you
                                                        ? 'text-emerald-100'
                                                        : 'text-slate-500 dark:text-slate-400',
                                                )}
                                            >
                                                {m.from_you
                                                    ? 'You'
                                                    : m.author_name ||
                                                      'Company'}{' '}
                                                ·{' '}
                                                {m.created_at
                                                    ? formatDisplayDateTime(
                                                          m.created_at,
                                                      )
                                                    : ''}
                                            </p>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>

                        <form onSubmit={submit} className="space-y-3">
                            <label
                                htmlFor="portal-message-body"
                                className="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                            >
                                New message
                            </label>
                            <textarea
                                id="portal-message-body"
                                value={form.data.body}
                                placeholder="Type your message…"
                                onChange={(e) =>
                                    form.setData('body', e.target.value)
                                }
                                rows={4}
                                className="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm shadow-sm transition-colors focus-visible:border-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                            />
                            {form.errors.body ? (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {form.errors.body}
                                </p>
                            ) : null}
                            <Button
                                type="submit"
                                className="w-full sm:w-auto"
                                disabled={form.processing}
                            >
                                Send message
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </PortalPageContainer>
        </AuthenticatedLayout>
    );
}
