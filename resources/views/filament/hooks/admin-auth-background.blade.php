@if (request()->routeIs('filament.admin.auth.login'))
    <style>
        .fi-simple-layout {
            position: relative;
            isolation: isolate;
            background-image: url('/images/auth/login-abstract.svg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .fi-simple-layout::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                rgba(248, 250, 252, 0.78) 0%,
                rgba(241, 245, 249, 0.72) 45%,
                rgba(226, 232, 240, 0.68) 100%
            );
            z-index: 0;
        }

        .fi-simple-layout::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            background-image:
                radial-gradient(circle at 8% 18%, rgba(255, 255, 255, 0.9) 1px, transparent 2px),
                radial-gradient(circle at 21% 62%, rgba(255, 255, 255, 0.7) 1px, transparent 2px),
                radial-gradient(circle at 34% 24%, rgba(255, 255, 255, 0.85) 1px, transparent 2px),
                radial-gradient(circle at 49% 72%, rgba(255, 255, 255, 0.7) 1px, transparent 2px),
                radial-gradient(circle at 58% 38%, rgba(255, 255, 255, 0.8) 1px, transparent 2px),
                radial-gradient(circle at 73% 54%, rgba(255, 255, 255, 0.9) 1px, transparent 2px),
                radial-gradient(circle at 84% 22%, rgba(255, 255, 255, 0.85) 1px, transparent 2px),
                radial-gradient(circle at 92% 70%, rgba(255, 255, 255, 0.75) 1px, transparent 2px);
            animation: login-stars-twinkle 2.6s ease-in-out infinite alternate;
            filter: drop-shadow(0 0 2px rgba(16, 185, 129, 0.25));
            pointer-events: none;
        }

        .dark .fi-simple-layout::before {
            background: linear-gradient(
                135deg,
                rgba(2, 6, 23, 0.82) 0%,
                rgba(15, 23, 42, 0.8) 50%,
                rgba(30, 41, 59, 0.76) 100%
            );
        }

        .dark .fi-simple-layout::after {
            filter: drop-shadow(0 0 3px rgba(16, 185, 129, 0.35));
        }

        .fi-simple-main {
            position: relative;
            z-index: 1;
            backdrop-filter: blur(2px);
        }

        @keyframes login-stars-twinkle {
            0% {
                opacity: 0.35;
                filter: brightness(0.95);
            }
            100% {
                opacity: 0.8;
                filter: brightness(1.2);
            }
        }
    </style>
@endif
