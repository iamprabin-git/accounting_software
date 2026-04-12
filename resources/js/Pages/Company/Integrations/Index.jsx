import CompanyWorkspaceSidebar from '@/Components/CompanyWorkspaceSidebar';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function Index({
    tokenAbilities = {},
    webhookEvents = {},
    tokens = [],
    webhooks = [],
    flash = {},
    integrationContext = null,
}) {
    const page = usePage();
    const status = page.props.flash?.status;

    const tokenForm = useForm({
        name: '',
        scopes: ['banking:read'],
    });

    const webhookForm = useForm({
        name: '',
        url: '',
        events: ['journal.posted'],
    });

    const abilityEntries = useMemo(
        () => Object.entries(tokenAbilities),
        [tokenAbilities],
    );
    const eventEntries = useMemo(
        () => Object.entries(webhookEvents),
        [webhookEvents],
    );

    const [copied, setCopied] = useState(false);

    const copyText = async (text) => {
        if (!text) return;
        try {
            await navigator.clipboard.writeText(text);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            /* ignore */
        }
    };

    const toggleScope = (key, checked) => {
        const next = new Set(tokenForm.data.scopes);
        if (checked) {
            next.add(key);
        } else {
            next.delete(key);
        }
        if (next.size === 0) {
            next.add('banking:read');
        }
        tokenForm.setData('scopes', [...next]);
    };

    const toggleEvent = (key, checked) => {
        const next = new Set(webhookForm.data.events);
        if (checked) {
            next.add(key);
        } else {
            next.delete(key);
        }
        if (next.size === 0) {
            next.add('journal.posted');
        }
        webhookForm.setData('events', [...next]);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <h2 className="min-w-0 text-xl font-semibold leading-tight text-gray-800">
                        Integrations &amp; API
                    </h2>
                    {integrationContext?.is_platform_admin ? (
                        <Link
                            href={route('dashboard')}
                            className="text-sm text-gray-600 underline hover:text-gray-900"
                        >
                            Back to dashboard
                        </Link>
                    ) : (
                        <Link
                            href={route('profile.edit')}
                            className="text-sm text-gray-600 underline hover:text-gray-900"
                        >
                            Your account
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Integrations" />

            <div className="py-8 sm:py-10">
                <div className="mx-auto flex w-full min-w-0 max-w-6xl flex-col gap-6 px-4 sm:gap-8 sm:px-6 lg:flex-row lg:gap-10 lg:px-8">
                    {integrationContext?.is_platform_admin ? null : (
                        <CompanyWorkspaceSidebar />
                    )}
                    <div className="min-w-0 flex-1 space-y-8">
                    {status ? (
                        <p className="text-sm text-green-700">{status}</p>
                    ) : null}

                    {integrationContext?.is_platform_admin &&
                    integrationContext?.company ? (
                        <div className="rounded-lg border border-indigo-200 bg-indigo-50/90 p-4 text-sm text-indigo-950">
                            <p className="font-medium">Platform admin</p>
                            <p className="mt-1 text-indigo-900/90">
                                Webhooks below belong to{' '}
                                <strong>{integrationContext.company.name}</strong>{' '}
                                (organization ID {integrationContext.company.id}
                                ). That matches your current workspace company
                                context — same as journals and Core banking. Open
                                this page with{' '}
                                <code className="rounded bg-white/80 px-1">
                                    ?company_id=
                                    {integrationContext.company.id}
                                </code>{' '}
                                or switch context from another finance screen to
                                change it.
                            </p>
                            <p className="mt-2 text-indigo-900/90">
                                Personal access tokens are yours: use{' '}
                                <code className="rounded bg-white/80 px-1">
                                    company_id
                                </code>{' '}
                                (or{' '}
                                <code className="rounded bg-white/80 px-1">
                                    X-Company-Id
                                </code>
                                ) on every banking API call.
                            </p>
                        </div>
                    ) : null}

                    <details className="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow">
                        <summary className="cursor-pointer font-medium text-gray-900">
                            Integration API (read / write by scope)
                        </summary>
                        <div className="mt-3 space-y-3 border-t border-gray-100 pt-3">
                            <p>
                                Issue a token with{' '}
                                <code className="rounded bg-gray-100 px-1">POST /api/v1/auth/token</code>{' '}
                                (JSON: <code className="rounded bg-gray-100 px-1">email</code>,{' '}
                                <code className="rounded bg-gray-100 px-1">password</code>, optional{' '}
                                <code className="rounded bg-gray-100 px-1">scopes[]</code>,{' '}
                                <code className="rounded bg-gray-100 px-1">device_name</code>
                                ). Then call endpoints with{' '}
                                <code className="rounded bg-gray-100 px-1">
                                    Authorization: Bearer &lt;token&gt;
                                </code>
                                .
                            </p>
                            <p className="text-amber-900">
                                <span className="font-medium">Platform admins</span> must send{' '}
                                <code className="rounded bg-amber-100 px-1">company_id</code> on every
                                banking request (query string, JSON body, or{' '}
                                <code className="rounded bg-amber-100 px-1">X-Company-Id</code>{' '}
                                header). Company owners use their own organization automatically.
                            </p>
                            <ul className="list-inside list-disc space-y-1 font-mono text-xs text-gray-800">
                                <li>DELETE /api/v1/auth/tokens/current — revoke token used on this request</li>
                                <li>GET /api/v1/banking/summary</li>
                                <li>GET /api/v1/banking/members</li>
                                <li>GET /api/v1/banking/positions?category=loan|savings</li>
                                <li>POST /api/v1/banking/journals/two-line — scope banking:journal</li>
                                <li>POST /api/v1/banking/transfers — scope banking:journal</li>
                                <li>GET /api/v1/banking/webhooks — scope banking:webhooks:manage</li>
                                <li>POST /api/v1/banking/webhooks — scope banking:webhooks:manage</li>
                                <li>DELETE /api/v1/banking/webhooks/&#123;id&#125; — scope banking:webhooks:manage</li>
                            </ul>
                            <p>
                                Scopes:{' '}
                                <code className="rounded bg-gray-100 px-1">banking:read</code>,{' '}
                                <code className="rounded bg-gray-100 px-1">banking:journal</code>,{' '}
                                <code className="rounded bg-gray-100 px-1">banking:webhooks:manage</code>.
                            </p>
                        </div>
                    </details>

                    {(flash.new_token || flash.new_webhook_secret) && (
                        <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                            <p className="font-semibold">Copy now — shown once</p>
                            {flash.new_token ? (
                                <div className="mt-2">
                                    <p className="text-xs text-amber-800">API token</p>
                                    <pre className="mt-1 overflow-x-auto rounded bg-white p-2 font-mono text-xs">
                                        {flash.new_token}
                                    </pre>
                                    <button
                                        type="button"
                                        onClick={() => copyText(flash.new_token)}
                                        className="mt-2 text-xs font-medium text-amber-900 underline"
                                    >
                                        {copied ? 'Copied' : 'Copy to clipboard'}
                                    </button>
                                </div>
                            ) : null}
                            {flash.new_webhook_secret ? (
                                <div className="mt-3">
                                    <p className="text-xs text-amber-800">Webhook signing secret</p>
                                    <pre className="mt-1 overflow-x-auto rounded bg-white p-2 font-mono text-xs">
                                        {flash.new_webhook_secret}
                                    </pre>
                                    <button
                                        type="button"
                                        onClick={() => copyText(flash.new_webhook_secret)}
                                        className="mt-2 text-xs font-medium text-amber-900 underline"
                                    >
                                        Copy secret
                                    </button>
                                </div>
                            ) : null}
                        </div>
                    )}

                    <section className="rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-medium text-gray-900">
                            Personal access tokens
                        </h3>
                        <p className="mt-1 text-sm text-gray-600">
                            OAuth-style scopes (Sanctum). Use{' '}
                            <code className="rounded bg-gray-100 px-1">Authorization: Bearer</code>{' '}
                            on <code className="rounded bg-gray-100 px-1">/api/v1/…</code>. This is
                            not a full OAuth2 authorization server — integrations use PATs with
                            selected scopes.
                        </p>

                        <form
                            className="mt-4 space-y-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                tokenForm.post(route('company.integrations.tokens.store'), {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            <div>
                                <InputLabel htmlFor="tok_name" value="Token name" />
                                <TextInput
                                    id="tok_name"
                                    className="mt-1 block w-full"
                                    value={tokenForm.data.name}
                                    onChange={(e) => tokenForm.setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={tokenForm.errors.name} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Scopes" />
                                <ul className="mt-2 space-y-2">
                                    {abilityEntries.map(([key, label]) => (
                                        <li key={key} className="flex items-start gap-2">
                                            <input
                                                type="checkbox"
                                                id={`scope-${key}`}
                                                checked={tokenForm.data.scopes.includes(key)}
                                                onChange={(e) =>
                                                    toggleScope(key, e.target.checked)
                                                }
                                                className="mt-1 rounded border-gray-300"
                                            />
                                            <label htmlFor={`scope-${key}`} className="text-sm">
                                                <span className="font-mono text-gray-800">
                                                    {key}
                                                </span>
                                                <span className="block text-gray-600">{label}</span>
                                            </label>
                                        </li>
                                    ))}
                                </ul>
                                <InputError message={tokenForm.errors.scopes} className="mt-1" />
                            </div>
                            <PrimaryButton disabled={tokenForm.processing}>
                                Create token
                            </PrimaryButton>
                        </form>

                        <ul className="mt-6 divide-y divide-gray-200 text-sm">
                            {tokens.length === 0 ? (
                                <li className="py-3 text-gray-500">No tokens yet.</li>
                            ) : (
                                tokens.map((t) => (
                                    <li
                                        key={t.id}
                                        className="flex flex-wrap items-center justify-between gap-2 py-3"
                                    >
                                        <div>
                                            <p className="font-medium text-gray-900">{t.name}</p>
                                            <p className="font-mono text-xs text-gray-500">
                                                {(t.abilities || []).join(', ') || '—'}
                                            </p>
                                            <p className="text-xs text-gray-400">
                                                Last used: {t.last_used_at || 'never'}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (
                                                    confirm(
                                                        'Revoke this token? Integrations using it will fail.',
                                                    )
                                                ) {
                                                    router.delete(
                                                        route(
                                                            'company.integrations.tokens.destroy',
                                                            t.id,
                                                        ),
                                                    );
                                                }
                                            }}
                                            className="text-sm text-red-600 hover:text-red-800"
                                        >
                                            Revoke
                                        </button>
                                    </li>
                                ))
                            )}
                        </ul>
                    </section>

                    <section className="rounded-lg bg-white p-6 shadow">
                        <h3 className="text-lg font-medium text-gray-900">Webhooks</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            HTTPS endpoints receive signed JSON (
                            <code className="rounded bg-gray-100 px-1">X-Banking-Signature</code>{' '}
                            HMAC-SHA256 of body).
                        </p>

                        <form
                            className="mt-4 space-y-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                webhookForm.post(route('company.integrations.webhooks.store'), {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            <div>
                                <InputLabel htmlFor="wh_name" value="Name" />
                                <TextInput
                                    id="wh_name"
                                    className="mt-1 block w-full"
                                    value={webhookForm.data.name}
                                    onChange={(e) =>
                                        webhookForm.setData('name', e.target.value)
                                    }
                                    required
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="wh_url" value="URL (https)" />
                                <TextInput
                                    id="wh_url"
                                    type="url"
                                    className="mt-1 block w-full"
                                    value={webhookForm.data.url}
                                    onChange={(e) =>
                                        webhookForm.setData('url', e.target.value)
                                    }
                                    required
                                />
                                <InputError message={webhookForm.errors.url} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Events" />
                                <ul className="mt-2 space-y-2">
                                    {eventEntries.map(([key, label]) => (
                                        <li key={key} className="flex items-start gap-2">
                                            <input
                                                type="checkbox"
                                                id={`ev-${key}`}
                                                checked={webhookForm.data.events.includes(key)}
                                                onChange={(e) =>
                                                    toggleEvent(key, e.target.checked)
                                                }
                                                className="mt-1 rounded border-gray-300"
                                            />
                                            <label htmlFor={`ev-${key}`} className="text-sm">
                                                <span className="font-mono text-gray-800">
                                                    {key}
                                                </span>
                                                <span className="block text-gray-600">{label}</span>
                                            </label>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <PrimaryButton disabled={webhookForm.processing}>
                                Add webhook
                            </PrimaryButton>
                        </form>

                        <ul className="mt-6 divide-y divide-gray-200 text-sm">
                            {webhooks.length === 0 ? (
                                <li className="py-3 text-gray-500">No webhooks.</li>
                            ) : (
                                webhooks.map((w) => (
                                    <li
                                        key={w.id}
                                        className="flex flex-wrap items-center justify-between gap-2 py-3"
                                    >
                                        <div>
                                            <p className="font-medium text-gray-900">{w.name}</p>
                                            <p className="break-all text-xs text-gray-600">
                                                {w.url}
                                            </p>
                                            <p className="font-mono text-xs text-gray-500">
                                                {(w.events || []).join(', ')}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (confirm('Remove this webhook?')) {
                                                    router.delete(
                                                        route(
                                                            'company.integrations.webhooks.destroy',
                                                            w.id,
                                                        ),
                                                    );
                                                }
                                            }}
                                            className="text-sm text-red-600 hover:text-red-800"
                                        >
                                            Remove
                                        </button>
                                    </li>
                                ))
                            )}
                        </ul>
                    </section>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
