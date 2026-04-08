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
    Bot,
    Check,
    Clock3,
    Mail,
    MapPin,
    MessageCircle,

    Phone,
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
        name: 'Rabin Bahadur Shahi',
        role: 'Proprietor, Bless Auto Entreprises',
        rating: 5,
    },

];

const featureMatrixRows = [
    {
        feature: 'Accounting core (chart of accounts, journals, reports)',
        starter: true,
        pro: true,
        enterprise: true,
    },
    {
        feature: 'Inventory management',
        starter: false,
        pro: true,
        enterprise: true,
    },
    {
        feature: 'CRM (accounts, contacts, opportunities, activities)',
        starter: false,
        pro: true,
        enterprise: true,
    },
    {
        feature: 'Debtors and creditors',
        starter: false,
        pro: false,
        enterprise: true,
    },
    {
        feature: 'CBS members and groups',
        starter: false,
        pro: false,
        enterprise: true,
    },
    {
        feature: 'CBS finance suite (loan/savings products, positions)',
        starter: false,
        pro: false,
        enterprise: true,
    },
    {
        feature: 'Core banking operations hub',
        starter: false,
        pro: false,
        enterprise: true,
    },
    {
        feature: 'Integrations and API access',
        starter: false,
        pro: false,
        enterprise: true,
    },
];

const contactChannels = [
    {
        title: 'Sales inquiries',
        detail: 'sales@lelesastogharjagga.com.np',
        helper: 'Product tours, pricing, and package guidance.',
        icon: Mail,
    },
    {
        title: 'Whatsapp support',
        detail: '+977-9765726294',
        helper: 'Platform support for active customers.',
        icon: Phone,
    },
    {
        title: 'Head office',
        detail: 'Godawari Municipality-5, Lalitpur, Nepal',
        helper: 'physical mail and in-person visits by appointment.',
        icon: MapPin,
    },
    {
        title: 'Business hours',
        detail: 'Mon - Fri, 8:30 AM - 6:00 PM',
        helper: 'Kathmandu Time (GMT+5:45)',
        icon: Clock3,
    },
];

const footerColumns = [
    {
        title: 'Product',
        links: [
            { label: 'Pricing', href: '#pricing' },
            { label: 'Feature Matrix', href: '#feature-matrix' },
            { label: 'Reviews', href: '#reviews' },
        ],
    },
    {
        title: 'Company',
        links: [
            { label: 'About', href: '#about' },
            { label: 'Contact', href: '#contact' },
            { label: 'Admin Portal', href: '/admin/login' },
        ],
    },
    {
        title: 'Resources',
        links: [
            { label: 'Security', href: '#about' },
            { label: 'Support', href: '#contact' },
            { label: 'Status', href: '#contact' },
        ],
    },
];

const liveChatChannels = [
    {
        label: 'WhatsApp Chat',
        href: 'https://wa.me/+9779765726294',
        helper: 'Fast support on WhatsApp',
        icon: MessageCircle,
    },

];

function MatrixCell({ enabled }) {
    return enabled ? (
        <span className="inline-flex items-center justify-center rounded-full bg-emerald-500/15 px-2 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
            Included
        </span>
    ) : (
        <span className="inline-flex items-center justify-center rounded-full bg-slate-500/10 px-2 py-1 text-xs font-medium text-slate-500 dark:text-slate-400">
            —
        </span>
    );
}

export default function Welcome({
    auth,
    canLogin,
    canRegister,
    googleAuthEnabled,
    contactSuccess,
    reviewSuccess,
    approvedReviews = [],
}) {
    const { t } = useTranslation();
    const contactRef = useRef(null);
    const { data, setData, post, processing, errors, reset, wasSuccessful } =
        useForm({
            name: '',
            email: '',
            message: '',
        });
    const {
        data: reviewData,
        setData: setReviewData,
        post: postReview,
        processing: reviewProcessing,
        errors: reviewErrors,
        reset: resetReview,
        wasSuccessful: reviewWasSuccessful,
    } = useForm({
        title: '',
        body: '',
        rating: 5,
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

    const submitReview = (e) => {
        e.preventDefault();
        postReview(route('reviews.store'), {
            preserveScroll: true,
            onSuccess: () => resetReview('title', 'body'),
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
                            <a href="#feature-matrix" className={navLink}>
                                Feature Matrix
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
                        id="feature-matrix"
                        className="scroll-mt-20 border-b border-border/60 bg-background px-4 py-20 sm:px-6"
                    >
                        <div className="mx-auto max-w-6xl">
                            <div className="mx-auto max-w-2xl text-center">
                                <h2 className="text-3xl font-bold tracking-tight">
                                    Feature matrix
                                </h2>
                                <p className="mt-3 text-muted-foreground">
                                    Choose the plan that matches your growth stage,
                                    from accounting essentials to full CBS + CRM +
                                    inventory operations.
                                </p>
                            </div>
                            <div className="mt-10 overflow-x-auto rounded-2xl border border-border bg-card/80 shadow-sm">
                                <table className="min-w-full border-collapse text-sm">
                                    <thead className="bg-muted/50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-semibold">
                                                Feature
                                            </th>
                                            <th className="px-4 py-3 text-center font-semibold">
                                                Starter
                                            </th>
                                            <th className="px-4 py-3 text-center font-semibold">
                                                Professional
                                            </th>
                                            <th className="px-4 py-3 text-center font-semibold">
                                                Enterprise
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {featureMatrixRows.map((row) => (
                                            <tr
                                                key={row.feature}
                                                className="border-t border-border/70"
                                            >
                                                <td className="px-4 py-3 font-medium text-foreground">
                                                    {row.feature}
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <MatrixCell enabled={row.starter} />
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <MatrixCell enabled={row.pro} />
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <MatrixCell enabled={row.enterprise} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
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
                                {(approvedReviews.length > 0
                                    ? approvedReviews
                                    : reviews
                                ).map((r) => (
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
                            <div className="mx-auto mt-10 max-w-2xl rounded-2xl border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 className="text-lg font-semibold">
                                            Share your review
                                        </h3>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Your review is submitted to your company
                                            for manual approval, then shown on the
                                            homepage.
                                        </p>
                                    </div>
                                    {auth.user?.role === 'company' ? (
                                        <Button variant="outline" asChild>
                                            <Link href={route('company.reviews.index')}>
                                                Review queue
                                            </Link>
                                        </Button>
                                    ) : null}
                                </div>
                                {(reviewSuccess || reviewWasSuccessful) && (
                                    <div
                                        className="mt-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200"
                                        role="status"
                                    >
                                        Review submitted. It will appear after
                                        company approval.
                                    </div>
                                )}
                                {auth.user ? (
                                    Number(auth.user.company_id || 0) > 0 ? (
                                    <form
                                        onSubmit={submitReview}
                                        className="mt-4 space-y-3"
                                    >
                                        <div>
                                            <label
                                                htmlFor="review-title"
                                                className="text-sm font-medium"
                                            >
                                                Role or title
                                            </label>
                                            <input
                                                id="review-title"
                                                value={reviewData.title}
                                                onChange={(e) =>
                                                    setReviewData(
                                                        'title',
                                                        e.target.value,
                                                    )
                                                }
                                                className="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                                placeholder="e.g. Finance Manager"
                                            />
                                            <InputError
                                                message={reviewErrors.title}
                                                className="mt-1"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                htmlFor="review-body"
                                                className="text-sm font-medium"
                                            >
                                                Review
                                            </label>
                                            <textarea
                                                id="review-body"
                                                rows={4}
                                                value={reviewData.body}
                                                onChange={(e) =>
                                                    setReviewData(
                                                        'body',
                                                        e.target.value,
                                                    )
                                                }
                                                className="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                                required
                                            />
                                            <InputError
                                                message={reviewErrors.body}
                                                className="mt-1"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                htmlFor="review-rating"
                                                className="text-sm font-medium"
                                            >
                                                Rating
                                            </label>
                                            <select
                                                id="review-rating"
                                                value={reviewData.rating}
                                                onChange={(e) =>
                                                    setReviewData(
                                                        'rating',
                                                        Number(e.target.value),
                                                    )
                                                }
                                                className="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                            >
                                                {[5, 4, 3, 2, 1].map((v) => (
                                                    <option key={v} value={v}>
                                                        {v} star{v > 1 ? 's' : ''}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={reviewErrors.rating}
                                                className="mt-1"
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={reviewProcessing}
                                        >
                                            Submit review
                                        </Button>
                                    </form>
                                    ) : (
                                        <div className="mt-4 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-900 dark:text-amber-200">
                                            Your account is not linked to a company
                                            yet, so review submission is currently
                                            unavailable.
                                        </div>
                                    )
                                ) : (
                                    <div className="mt-4">
                                        <Button asChild>
                                            <Link href={route('login')}>
                                                Login to submit review
                                            </Link>
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>

                    <section
                        id="contact"
                        ref={contactRef}
                        className="scroll-mt-20 border-t border-border/60 bg-muted/20 px-4 py-20 sm:px-6"
                    >
                        <div className="mx-auto max-w-6xl">
                            <div className="mx-auto max-w-2xl text-center">
                                <Mail className="mx-auto h-10 w-10 text-emerald-600" />
                                <h2 className="mt-4 text-3xl font-bold tracking-tight">
                                    Contact Us
                                </h2>
                                <p className="mt-2 text-muted-foreground">
                                    Talk with our team about implementation, pricing,
                                    or support. We respond within one business day.
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

                            <div className="mt-10 grid gap-6 lg:grid-cols-[1.1fr_1fr]">
                                <form
                                    onSubmit={submitContact}
                                    className="space-y-4 rounded-2xl border border-border bg-card p-6 shadow-sm"
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

                                <div className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                                    <h3 className="text-lg font-semibold">
                                        Reach our team directly
                                    </h3>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Prefer direct contact? Use one of the
                                        channels below and our team will assist you
                                        quickly.
                                    </p>
                                    <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                                        {contactChannels.map((channel) => {
                                            const Icon = channel.icon;
                                            return (
                                                <div
                                                    key={channel.title}
                                                    className="rounded-xl border border-border/70 bg-background/80 p-4"
                                                >
                                                    <div className="flex items-start gap-3">
                                                        <span className="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                                                            <Icon className="h-4 w-4" />
                                                        </span>
                                                        <div>
                                                            <p className="text-sm font-semibold">
                                                                {channel.title}
                                                            </p>
                                                            <p className="text-sm">
                                                                {channel.detail}
                                                            </p>
                                                            <p className="mt-1 text-xs text-muted-foreground">
                                                                {channel.helper}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    <div className="mt-6 rounded-xl border border-border/70 bg-background/70 p-4">
                                        <h4 className="text-sm font-semibold">
                                            Chat with us
                                        </h4>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Choose your preferred channel for quick
                                            conversations.
                                        </p>
                                        <div className="mt-3 grid gap-2">
                                            {liveChatChannels.map((chat) => {
                                                const Icon = chat.icon;
                                                return (
                                                    <a
                                                        key={chat.label}
                                                        href={chat.href}
                                                        target={
                                                            chat.href.startsWith('http')
                                                                ? '_blank'
                                                                : undefined
                                                        }
                                                        rel={
                                                            chat.href.startsWith('http')
                                                                ? 'noreferrer'
                                                                : undefined
                                                        }
                                                        className="flex items-center justify-between rounded-lg border border-border bg-card px-3 py-2 transition hover:border-emerald-500/50 hover:bg-emerald-500/5"
                                                    >
                                                        <span className="flex items-center gap-2 text-sm font-medium">
                                                            <Icon className="h-4 w-4 text-emerald-600" />
                                                            {chat.label}
                                                        </span>
                                                        <span className="text-xs text-muted-foreground">
                                                            {chat.helper}
                                                        </span>
                                                    </a>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border bg-card/50 px-4 py-12 sm:px-6">
                    <div className="mx-auto max-w-6xl">
                        <div className="grid gap-10 border-b border-border pb-10 md:grid-cols-[1.2fr_1fr_1fr_1fr]">
                            <div>
                                <div className="flex items-center gap-2 font-semibold tracking-tight">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                        <BarChart3 className="h-5 w-5" />
                                    </span>
                                    Accounting System
                                </div>
                                <p className="mt-3 max-w-sm text-sm text-muted-foreground">
                                    Reliable accounting and operations software
                                    for growing teams that need speed, control, and
                                    audit-ready financial reporting.
                                </p>
                                <div className="mt-4 text-sm text-muted-foreground">
                                    <p className="font-medium text-foreground/90">
                                        Contact
                                    </p>
                                    <p className="mt-1">sales@lelesastogharjagga.com.np</p>
                                    <p>+977-9765726294</p>
                                </div>
                            </div>

                            {footerColumns.map((column) => (
                                <div key={column.title}>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-foreground/90">
                                        {column.title}
                                    </h3>
                                    <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                                        {column.links.map((item) => (
                                            <li key={item.label}>
                                                <a
                                                    href={item.href}
                                                    className="transition hover:text-foreground"
                                                >
                                                    {item.label}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>

                        <div className="flex flex-col items-start justify-between gap-4 pt-6 text-sm text-muted-foreground sm:flex-row sm:items-center">
                            <p>
                                © {new Date().getFullYear()} Accounting System. All rights
                                reserved.
                            </p>
                            <div className="flex flex-wrap items-center gap-5">
                                Developed by :{' '}
                                <a
                                    href="https://dangolprabin.com.np"
                                    className="transition hover:text-foreground"
                                >
                                   <span className='text-blue-600'>Prabin Dangol</span>
                                </a>

                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
