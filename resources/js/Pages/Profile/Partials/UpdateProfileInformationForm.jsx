import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}) {
    const user = usePage().props.auth.user;
    const [photoPreview, setPhotoPreview] = useState(null);

    const form = useForm({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        profile_photo: null,
        remove_profile_photo: false,
    });

    useEffect(() => {
        if (!form.data.profile_photo) {
            setPhotoPreview(null);
            return;
        }
        const url = URL.createObjectURL(form.data.profile_photo);
        setPhotoPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [form.data.profile_photo]);

    const displayPhoto =
        photoPreview ||
        (form.data.remove_profile_photo ? null : user.avatar_display_url);

    const submit = (e) => {
        e.preventDefault();
        // Do not spread full form data: null profile_photo and '' remove flag break Laravel validation.
        form.transform((d) => {
            const payload = {
                _method: 'patch',
                name: d.name,
                email: d.email,
                phone: d.phone ?? '',
            };
            if (d.remove_profile_photo) {
                payload.remove_profile_photo = true;
            }
            if (d.profile_photo instanceof File) {
                payload.profile_photo = d.profile_photo;
            }
            return payload;
        });
        form.post(route('profile.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.setData('profile_photo', null);
                form.setData('remove_profile_photo', false);
            },
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Profile information
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    Update your name, email, phone, and profile photo. Your photo
                    appears in the header when signed in.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <div className="shrink-0">
                        <InputLabel value="Profile photo" />
                        <div className="mt-2 flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-gray-300 bg-gray-50 text-gray-400 dark:border-border dark:bg-muted">
                            {displayPhoto ? (
                                <img
                                    src={displayPhoto}
                                    alt=""
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <span className="text-xs font-medium">
                                    No photo
                                </span>
                            )}
                        </div>
                        <div className="mt-2 flex flex-wrap gap-2">
                            <label className="cursor-pointer">
                                <span className="inline-flex rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-border dark:bg-background dark:text-foreground dark:hover:bg-muted">
                                    Choose image
                                </span>
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    className="sr-only"
                                    onChange={(e) => {
                                        const f = e.target.files?.[0];
                                        form.setData('remove_profile_photo', false);
                                        form.setData('profile_photo', f || null);
                                    }}
                                />
                            </label>
                            {(user.profile_photo_url || photoPreview) && (
                                <button
                                    type="button"
                                    className="rounded-md px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                                    onClick={() => {
                                        form.setData('profile_photo', null);
                                        form.setData(
                                            'remove_profile_photo',
                                            true,
                                        );
                                    }}
                                >
                                    Remove photo
                                </button>
                            )}
                        </div>
                        <p className="mt-1 text-xs text-gray-500">
                            JPEG, PNG, GIF or WebP. Max 2 MB.
                        </p>
                        <InputError
                            className="mt-2"
                            message={form.errors.profile_photo}
                        />
                    </div>
                </div>

                <div>
                    <InputLabel htmlFor="name" value="Name" />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />

                    <InputError className="mt-2" message={form.errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />

                    <InputError className="mt-2" message={form.errors.email} />
                </div>

                <div>
                    <InputLabel htmlFor="phone" value="Contact number" />
                    <TextInput
                        id="phone"
                        type="tel"
                        className="mt-1 block w-full"
                        value={form.data.phone}
                        onChange={(e) => form.setData('phone', e.target.value)}
                        autoComplete="tel"
                        placeholder="Optional"
                    />
                    <InputError className="mt-2" message={form.errors.phone} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800">
                            Your email address is unverified.
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your
                                email address.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={form.processing}>Save</PrimaryButton>

                    <Transition
                        show={form.recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">Saved.</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
