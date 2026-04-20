<style>
    /* Additional custom styles to fine-tune center-content & subtle animations */
    .center-content {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }

    /* Smooth fade-in for the card */
    .auth-card {
        animation: fadeSlideUp 0.4s ease-out forwards;
    }

    @keyframes fadeSlideUp {
        0% {
            opacity: 0;
            transform: translateY(18px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Optional hover effect on login button */
    .btn-login {
        transition: all 0.2s ease;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.15);
    }
</style>

<!-- AUTH SECTION: exactly matching the semantic container structure but fully styled with Tailwind -->
<div id="auth-container" class="container center-content px-4 sm:px-6">
    <div class="auth-card max-w-md w-full bg-gray-50 rounded-2xl shadow-xl border border-gray-100 overflow-hidden"
        style="margin: 10px;">

        <!-- Decorative top accent line (just a nice touch) -->
        <div class="h-1.5 bg-purple-500 w-full"></div>

        <!-- Inner content with proper spacing, icon area -->
        <div class="p-8 sm:p-10 text-center">
            <!-- Favicon / Logo placeholder - replace src with your actual asset path -->
            <div class="flex justify-center mb-5">
                <img src="/assets/favicon.png" alt="App Logo" class="h-16 w-auto object-contain drop-shadow-sm"
                    onerror="this.onerror=null; this.src='https://placehold.co/80x80?text=Logo'; this.classList.add('opacity-80');">
            </div>

            <!-- Main heading with icon indicator (lock/timeout) -->
            <div class="flex justify-center items-center gap-2 text-purple-600 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-800">Session Expired</h1>
            </div>

            <!-- Descriptive paragraph -->
            <p class="text-gray-600 text-base sm:text-lg mt-2 leading-relaxed">
                Your session has timed out due to inactivity.<br>
                Please login again to continue securely.
            </p>

            <!-- Extra info: small tip or helpful message (optional but user-friendly) -->
            <div class="mt-4 text-sm text-purple-700 bg-purple-50 rounded-lg p-2 inline-block px-4">
                <span class="inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    For security reasons, we logged you out
                </span>
            </div>

            <br><br>

            <!-- Action button: Redirect to login or show interactive login flow -->
            <div class="mt-2">
                <button id="login-redirect-btn"
                    class="btn-login w-full sm:w-auto bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold py-3 px-8 rounded-xl shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 inline-flex items-center justify-center gap-2 text-base">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Login Again
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Simple JavaScript to demonstrate session deactivation behavior & simulate redirection -->
<script>
    (function () {
        // This script adds realistic session expired handling:
        // 1. Clear any leftover session/local storage tokens (demo)
        // 2. Provide redirect actions for login, home, support.

        // Simulate cleaning session data on "session expired" page
        // Usually this page would be shown after detecting 401 or expired token.
        // We'll clear dummy items from localStorage / sessionStorage to mimic logout.
        const removeStoredSession = () => {
            // Remove typical auth tokens from storage (demo keys)
            const authKeys = ['authToken', 'accessToken', 'userSession', 'refreshToken', 'session_id'];
            authKeys.forEach(key => {
                localStorage.removeItem(key);
                sessionStorage.removeItem(key);
            });
            // Also clear any custom app-specific flags
            localStorage.removeItem('user_profile');
            sessionStorage.removeItem('last_activity');

            // For demonstration, log to console (not intrusive)
            if (window.console && console.info) {
                console.info('Session deactivated: stored authentication data cleared.');
            }
        };

        // Perform session cleanup as soon as this page loads (like real session expiry screen)
        removeStoredSession();

        // Helper to simulate navigation (in real app use actual window.location)
        // For demonstration we use real redirects, but also provide alerts for demo context if needed.
        // We'll build actual redirects with configurable paths — you can replace with your login URL.

        // Get button and link elements
        const loginBtn = document.getElementById('login-redirect-btn');
        const supportLink = document.getElementById('support-link');
        const homeLink = document.getElementById('home-link');

        // Default redirect targets (you can modify according to your app structure)
        const LOGIN_URL = '/';          // Change to your actual login route
        const HOME_URL = '/';                // Or dashboard / landing
        const SUPPORT_URL = '/support';      // Or contact page

        // Function to redirect preserving any 'redirect_uri' optional query param? Not necessary but nice.
        const redirectTo = (url, isExternal = false) => {
            if (!url) return;
            // For local demo we can show a small toast / but better just redirect.
            // In real app, window.location.href = url;
            // However, for demonstration purpose in this snippet, we'll use window.location.
            // To avoid confusion with file:// or static preview, we'll show a confirm? No, better do actual redirect simulation.
            // But if the link doesn't exist in the current environment, we can add a small overlay message for demo.
            // However typical Tailwind page design: we assume the developer will integrate real routing.
            // For interactive preview, I'll also show a friendly modal alternative (optional) but user expects to "login again".
            // To align with session deactivated behavior, we'll implement redirects, but if the current environment is static HTML,
            // we also offer a 'demo notification' that redirect would happen, BUT not to break user expectation.
            // Let's make it flexible: if it's a real web app, we redirect. For embedded preview we can show an info.
            // Because the user might be viewing on CodePen / local file, the redirect might cause 404. To be user-friendly,
            // we'll present a small overlay message explaining action, and also attempt redirect but add a console warning.
            // Better yet: we use window.location but if URL is not defined or relative, it's fine.
            window.location.href = url;
        };

        // Add robust click handlers with optional demo note if login page is missing? but not necessary, final product uses correct paths.
        if (loginBtn) {
            loginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                // Additional optional: show a brief "Redirecting to login..." feedback
                loginBtn.disabled = true;
                loginBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Redirecting...
          `;
                setTimeout(() => {
                    redirectTo(LOGIN_URL);
                }, 150);
            });
        }

        if (homeLink) {
            homeLink.addEventListener('click', (e) => {
                e.preventDefault();
                // If you want to clear any residual state before home, optionally clear again
                removeStoredSession();
                redirectTo(HOME_URL);
            });
        }

        if (supportLink) {
            supportLink.addEventListener('click', (e) => {
                e.preventDefault();
                redirectTo(SUPPORT_URL);
            });
        }

        // Additionally, if any "back" button or browser navigation attempts to restore session? Not needed.
        // Also simulate that any previous history state is cleared (not mandatory).
        // Provide a subtle way to detect if someone tries to use 'go back' - push new state to prevent re-login confusion.
        if (window.history && window.history.pushState) {
            // Replace current state to avoid an immediate back-button into stale authenticated view (UX improvement)
            window.history.replaceState(null, document.title, window.location.href);
        }

        // Optional: display a dynamic timestamp or show when session expired (just cosmetic)
        const addTimestampInfo = () => {
            const cardParagraph = document.querySelector('.auth-card p');
            if (cardParagraph && !document.querySelector('.session-time-hint')) {
                const timeSpan = document.createElement('span');
                timeSpan.className = 'session-time-hint block text-xs text-gray-400 mt-2';
                const now = new Date();
                const formattedTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                timeSpan.innerHTML = `⏱️ Session ended at ${formattedTime}`;
                // Insert after paragraph inside same parent? The paragraph has no direct sibling structure, but we can append within parent container of p.
                const pParent = cardParagraph.parentNode;
                if (pParent && !pParent.querySelector('.session-time-hint')) {
                    pParent.appendChild(timeSpan);
                }
            }
        };
        addTimestampInfo();

        // For completeness: clear any interval/timer that could keep session alive in background (not needed but great)
        // This page stands as fully deactivated.

        // Additional: when the user clicks "login again", we could also store a flag that session was expired.
        // all good.
    })();
</script>

<!-- Optional fallback: ensure that the page gracefully handles missing favicon and style -->
<noscript>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-center max-w-md mx-auto mt-10">
        <strong>JavaScript recommended!</strong> Please enable JavaScript to be redirected to the login page.
    </div>
</noscript>