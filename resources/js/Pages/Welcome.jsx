import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import GoogleSignInButton from '@/Components/GoogleSignInButton';
import InputError from '@/Components/InputError';
import ThemeLanguageControls from '@/Components/ThemeLanguageControls';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Check,
    Mail,
    Shield,
    Sparkles,
    Star,
} from 'lucide-react';
import { useEffect, useMemo, useRef } from 'react';
import { useTranslation } from 'react-i18next';

const navLink =
    'text-sm font-medium text-muted-foreground transition hover:text-foreground';

const reviews = [
    {
        quote:
            'We replaced spreadsheets in a week. Our month-end close is half the time.',
        name: 'Priya N.',
        role: 'CFO, Northwind Logistics',
        rating: 5,
    },
    {
        quote:
            'Clean double-entry UI without the enterprise bloat. Exactly what we needed.',
        name: 'Marcus T.',
        role: 'Founder, Cedar Studio',
        rating: 5,
    },
    {
        quote:
            'Support actually understands accounting. Rare for SaaS at this price.',
        name: 'Elena V.',
        role: 'Controller, Harbor Clinics',
        rating: 5,
    },
];

export default function Welcome({
    auth,
    canLogin,
    canRegister,
    googleAuthEnabled,
    contactSuccess,
}) {
    const { t } = useTranslation();
    const contactRef = useRef(null);
    const { data, setData, post, processing, errors, reset, wasSuccessful } =
        useForm({
            name: '',
            email: '',
            message: '',
        });

    useEffect(() => {
        if (contactSuccess || wasSuccessful) {
            contactRef.current?.scrollIntoView({ behavior: 'smooth' });
        }
    }, [contactSuccess, wasSuccessful]);

    const submitContact = (e) => {
        e.preventDefault();
        post(route('contact.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const pricing = useMemo(
        () => [
            {
                name: t('welcome.starterName'),
                price: t('welcome.starterPrice'),
                period: t('welcome.starterPeriod'),
                description: t('welcome.starterDesc'),
                features: [
                    t('welcome.starterF1'),
                    t('welcome.starterF2'),
                    t('welcome.starterF3'),
                    t('welcome.starterF4'),
                ],
                cta: t('welcome.starterCta'),
                highlighted: false,
            },
            {
                name: t('welcome.proName'),
                price: t('welcome.proPrice'),
                period: t('welcome.proPeriod'),
                description: t('welcome.proDesc'),
                features: [
                    t('welcome.proF1'),
                    t('welcome.proF2'),
                    t('welcome.proF3'),
                    t('welcome.proF4'),
                    t('welcome.proF5'),
                ],
                cta: t('welcome.proCta'),
                highlighted: true,
            },
            {
                name: t('welcome.enterpriseName'),
                price: t('welcome.enterprisePrice'),
                period: '',
                description: t('welcome.enterpriseDesc'),
                features: [
                    t('welcome.entF1'),
                    t('welcome.entF2'),
                    t('welcome.entF3'),
                    t('welcome.entF4'),
                ],
                cta: t('welcome.entCta'),
                highlighted: false,
            },
        ],
        [t],
    );

    return (
        <>
            <Head title="Ledger — Modern accounting software" />

            <div className="min-h-screen bg-background text-foreground">
                <header className="sticky top-0 z-50 border-b border-border/80 bg-background/95 backdrop-blur">
                    <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
                        <Link
                            href={route('home')}
                            className="flex items-center gap-2 font-semibold tracking-tight"
                        >
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <BarChart3 className="h-5 w-5" />
                            </span>
                            Accounting System
                        </Link>
                        <nav className="flex flex-wrap items-center gap-4 sm:gap-6">
                            <a href="#pricing" className={navLink}>
                                {t('welcome.pricing')}
                            </a>
                            <a href="#about" className={navLink}>
                                {t('welcome.about')}
                            </a>
                            <a href="#reviews" className={navLink}>
                                {t('welcome.reviews')}
                            </a>
                            <a href="#contact" className={navLink}>
                                {t('welcome.contact')}
                            </a>
                            <a
                                href="/admin/login"
                                className={navLink}
                                target="_blank"
                                rel="noreferrer"
                            >
                                {t('welcome.staffAdmin')}
                            </a>
                        </nav>
                        <div className="flex flex-wrap items-center gap-2">
                            <ThemeLanguageControls />
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={route('dashboard')}>
                                        {t('nav.dashboard')}
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    {canLogin && (
                                        <Button variant="ghost" asChild>
                                            <Link href={route('login')}>
                                                {t('welcome.signIn')}
                                            </Link>
                                        </Button>
                                    )}
                                    {canRegister && (
                                        <Button asChild>
                                            <Link href={route('register')}>
                                                {t('welcome.signUp')}
                                            </Link>
                                        </Button>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <main>
                    <section className="border-b border-border/60 bg-gradient-to-b from-emerald-500/5 via-background to-background px-4 py-20 sm:px-6 sm:py-28">
                        <div className="mx-auto max-w-3xl text-center">
                            <p className="mb-4 inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1 text-xs font-medium text-muted-foreground">
                                <Sparkles className="h-3.5 w-3.5 text-emerald-600" />
                                {t('welcome.heroBadge')}
                            </p>
                            <h1 className="text-balance text-4xl font-bold tracking-tight sm:text-5xl">
                                {t('welcome.heroTitle')}
                            </h1>
                            <p className="mt-6 text-pretty text-lg text-muted-foreground sm:text-xl">
                                {t('welcome.heroSubtitle')}
                            </p>
                            <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                {canRegister && (
                                    <Button size="lg" asChild>
                                        <Link
                                            href={route('register')}
                                            className="gap-2"
                                        >
                                            {t('welcome.createFreeAccount')}
                                            <ArrowRight className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                )}
                                {googleAuthEnabled && !auth.user && (
                                    <GoogleSignInButton className="sm:w-auto sm:min-w-[220px]" />
                                )}
                            </div>
                        </div>
                    </section>

                    <section
                        id="pricing"
                        className="scroll-mt-20 px-4 py-20 sm:px-6"
                    >
                        <div className="mx-auto max-w-6xl">
                            <div className="mx-auto max-w-2xl text-center">
                                <h2 className="text-3xl font-bold tracking-tight">
                                    {t('welcome.pricingTitle')}
                                </h2>
                                <p className="mt-3 text-muted-foreground">
                                    {t('welcome.pricingSubtitle')}
                                </p>
                            </div>
                            <div className="mt-12 grid gap-6 lg:grid-cols-3">
                                {pricing.map((tier) => (
                                    <Card
                                        key={tier.name}
                                        className={
                                            tier.highlighted
                                                ? 'border-emerald-500/60 shadow-md ring-1 ring-emerald-500/20'
                                                : ''
                                        }
                                    >
                                        <CardHeader>
                                            <CardTitle className="text-lg">
                                                {tier.name}
                                            </CardTitle>
                                            <CardDescription>
                                                {tier.description}
                                            </CardDescription>
                                            <div className="pt-4">
                                                <span className="text-4xl font-bold">
                                                    {tier.price}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {tier.period}
                                                </span>
                                            </div>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            <ul className="space-y-2 text-sm">
                                                {tier.features.map((f) => (
                                                    <li
                                                        key={f}
                                                        className="flex gap-2"
                                                    >
                                                        <Check className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                                                        {f}
                                                    </li>
                                                ))}
                                            </ul>
                                            <Button
                                                className="w-full"
                                                variant={
                                                    tier.highlighted
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                asChild
                                            >
                                                <a href="#contact">
                                                    {tier.cta}
                                                </a>
                                            </Button>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section
                        id="about"
                        className="scroll-mt-20 border-y border-border/60 bg-muted/30 px-4 py-20 sm:px-6"
                    >
                        <div className="mx-auto grid max-w-6xl gap-12 lg:grid-cols-2 lg:items-center">
                            <div>
                                <h2 className="text-3xl font-bold tracking-tight">
                                    About us
                                </h2>
                                <p className="mt-4 text-muted-foreground">
                                    Ledger exists because finance teams deserve
                                    tools that respect accounting fundamentals
                                    without burying them in complexity. We focus
                                    on double-entry integrity, fast data entry
                                    for bookkeepers, and a product roadmap
                                    shaped by real controllers and CPAs.
                                </p>
                                <p className="mt-4 text-muted-foreground">
                                    Whether you run a small agency or a
                                    multi-entity group, we are building the
                                    calm place where your numbers stay
                                    trustworthy.
                                </p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Card>
                                    <CardHeader className="pb-2">
                                        <Shield className="h-8 w-8 text-emerald-600" />
                                        <CardTitle className="text-base">
                                            Trust and control
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="text-sm text-muted-foreground">
                                        Session-based auth, optional Google
                                        OAuth, and staff tools separated in
                                        Filament admin.
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardHeader className="pb-2">
                                        <BarChart3 className="h-8 w-8 text-emerald-600" />
                                        <CardTitle className="text-base">
                                            Built for growth
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="text-sm text-muted-foreground">
                                        Chart of accounts and journals designed
                                        to scale as you add entities and users.
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </section>

                    <section
                        id="reviews"
                        className="scroll-mt-20 px-4 py-20 sm:px-6"
                    >
                        <div className="mx-auto max-w-6xl">
                            <h2 className="text-center text-3xl font-bold tracking-tight">
                                What teams say
                            </h2>
                            <p className="mx-auto mt-3 max-w-xl text-center text-muted-foreground">
                                Early adopters use Ledger to keep books tight
                                without enterprise overhead.
                            </p>
                            <div className="mt-12 grid gap-6 md:grid-cols-3">
                                {reviews.map((r) => (
                                    <Card key={r.name}>
                                        <CardHeader>
                                            <div className="flex gap-0.5">
                                                {Array.from({
                                                    length: r.rating,
                                                }).map((_, i) => (
                                                    <Star
                                                        key={i}
                                                        className="h-4 w-4 fill-amber-400 text-amber-400"
                                                    />
                                                ))}
                                            </div>
                                            <CardDescription className="text-base leading-relaxed text-foreground">
                                                “{r.quote}”
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <p className="font-medium">
                                                {r.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {r.role}
                                            </p>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section
                        id="contact"
                        ref={contactRef}
                        className="scroll-mt-20 border-t border-border/60 bg-muted/20 px-4 py-20 sm:px-6"
                    >
                        <div className="mx-auto max-w-xl">
                            <div className="text-center">
                                <Mail className="mx-auto h-10 w-10 text-emerald-600" />
                                <h2 className="mt-4 text-3xl font-bold tracking-tight">
                                    Contact
                                </h2>
                                <p className="mt-2 text-muted-foreground">
                                    Questions about pricing, security, or a demo?
                                    Send a note—we reply within one business day.
                                </p>
                            </div>

                            {(contactSuccess || wasSuccessful) && (
                                <div
                                    className="mt-6 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-center text-sm text-emerald-800 dark:text-emerald-200"
                                    role="status"
                                >
                                    Thanks—your message is on its way.
                                </div>
                            )}

                            <form
                                onSubmit={submitContact}
                                className="mt-8 space-y-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                            >
                                <div>
                                    <label
                                        htmlFor="contact-name"
                                        className="text-sm font-medium"
                                    >
                                        Name
                                    </label>
                                    <input
                                        id="contact-name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        className="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        required
                                    />
                                    <InputError
                                        message={errors.name}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="contact-email"
                                        className="text-sm font-medium"
                                    >
                                        Email
                                    </label>
                                    <input
                                        id="contact-email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                        className="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        required
                                    />
                                    <InputError
                                        message={errors.email}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="contact-message"
                                        className="text-sm font-medium"
                                    >
                                        Message
                                    </label>
                                    <textarea
                                        id="contact-message"
                                        rows={5}
                                        value={data.message}
                                        onChange={(e) =>
                                            setData('message', e.target.value)
                                        }
                                        className="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        required
                                    />
                                    <InputError
                                        message={errors.message}
                                        className="mt-1"
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    Send message
                                </Button>
                            </form>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border px-4 py-10 sm:px-6">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 text-center text-sm text-muted-foreground sm:flex-row sm:text-left">
                        <p>© {new Date().getFullYear()} Ledger. All rights reserved.</p>
                        <div className="flex gap-6">
                            <a href="#pricing" className="hover:text-foreground">
                                Pricing
                            </a>
                            <Link
                                href={route('login')}
                                className="hover:text-foreground"
                            >
                                Log in
                            </Link>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
