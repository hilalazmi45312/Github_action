<?php

class SplashController
{
    public static function header()
    {
?>

        <style id="nav-loading-overlay-css">
            :root {
                --overlay-bg: #f4f4f4;
                /* page wash (very light) */
                --box-bg: #3a3a3a;
                /* dark square */
                --ring: rgba(255, 255, 255, .55);
                /* gray ring */
                --accent: #e53935;
                /* red arc */
                --box-size: 76px;
                /* square size */
                --ring-size: 44px;
                /* svg size */
                --ring-width: 4;
                /* stroke width in px (number only) */
                --radius: 16;
                /* svg circle radius (number only) */
                --corner: 12px;
                /* square radius */
            }

            .nav-loading-overlay {
                position: fixed;
                inset: 0;
                background: var(--overlay-bg);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 999999;
            }

            .nav-loading-overlay[aria-hidden="false"] {
                display: flex;
            }

            /* dark rounded square “card” */
            .nav-loading-card {
                width: var(--box-size);
                height: var(--box-size);
                background: var(--box-bg);
                border-radius: var(--corner);
                display: grid;
                place-items: center;
                box-shadow: 0 6px 18px rgba(0, 0, 0, .25);
            }

            /* spinner built with SVG: a gray track + short red arc that rotates */
            .nav-spinner-svg {
                width: var(--ring-size);
                height: var(--ring-size);
                animation: navspin 0.85s linear infinite;
                display: block;
            }

            .nav-spinner-track {
                stroke: var(--ring);
                stroke-width: var(--ring-width);
            }

            .nav-spinner-arc {
                stroke: var(--accent);
                stroke-width: var(--ring-width);
                stroke-linecap: round;
                /* small arc: length 20, rest gap 200; tuned to the 2πr of radius 16 ≈ 100.5*? —
       values are generous to make a short segment */
                stroke-dasharray: 22 200;
            }

            @keyframes navspin {
                to {
                    transform: rotate(360deg);
                }
            }

            /* Optional: block clicks while visible */
            body.nav-loading {
                pointer-events: none;
            }
        </style>

    <?php
    }

    public static function footer()
    {
    ?>
        <div class="nav-loading-overlay" id="navLoadingOverlay" aria-hidden="true" aria-live="polite" role="status">
            <div class="nav-loading-card" aria-label="Loading">
                <svg class="nav-spinner-svg" viewBox="0 0 44 44" aria-hidden="true" focusable="false">
                    <circle class="nav-spinner-track" cx="22" cy="22" r="16" fill="none" />
                    <circle class="nav-spinner-arc" cx="22" cy="22" r="16" fill="none" />
                </svg>
            </div>
        </div>

        <script id="nav-loading-overlay-js">
            (function() {
                var overlay = document.getElementById('navLoadingOverlay');
                if (!overlay) return;

                function showOverlay() {
                    if (overlay.getAttribute('aria-hidden') === 'false') return;
                    overlay.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('nav-loading');
                    document.body.setAttribute('aria-busy', 'true');
                }

                function hideOverlay() {
                    if (overlay.getAttribute('aria-hidden') === 'true') return;
                    overlay.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('nav-loading');
                    document.body.removeAttribute('aria-busy');
                }

                var intendsToNavigate = false;
                var lastHref = null;
                var isOverlayActive = false;

                function isDeeplink(href) {
                    if (!href) return false;
                    if (/^(mailto:|tel:|intent:|[a-zA-Z]+:\/\/)/.test(href) && !/^https?:/i.test(href)) {
                        return true;
                    }
                    // if (/^\/appRedirect::\//.test(href)) {
                    //     return true;
                    // }
                    return false;
                }

                function shouldIgnoreAnchor(el, e) {
                    if (!el) return true;
                    if (e && (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1)) return true;

                    var href = el.getAttribute('href') || '';
                    var target = (el.getAttribute('target') || '').toLowerCase();

                    if (el.hasAttribute('data-no-overlay') || el.getAttribute('rel') === 'nooverlay') return true;
                    if (href && href.charAt(0) === '#') return true;
                    if (target === '_blank' || el.hasAttribute('download')) return true;
                    if (isDeeplink(href)) return false;
                    if (href && !/^https?:/i.test(href) && href.indexOf('/') !== 0) return true;

                    return false;
                }

                // Handle anchor clicks
                document.addEventListener('click', function(e) {
                    var el = e.target;
                    while (el && el.tagName !== 'A' && el !== document) el = el.parentNode;
                    if (!el || el.tagName !== 'A') return;

                    var href = el.getAttribute('href') || '';

                    if (isDeeplink(href)) {
                        e.preventDefault();
                        intendsToNavigate = false;
                        lastHref = null;
                        hideOverlay();

                        if (window.flutter_inappwebview) {
                            window.flutter_inappwebview.callHandler("deeplink", href);
                        }
                        return;
                    }

                    if (!shouldIgnoreAnchor(el, e)) {
                        intendsToNavigate = true;
                        lastHref = href;
                        setTimeout(function() {
                            intendsToNavigate = false;
                            lastHref = null;
                        }, 100); // Increased timeout to ensure cleanup
                    }
                }, true);

                // Handle form submits
                document.addEventListener('submit', function(e) {
                    var form = e.target;
                    if (!form) return;
                    if (form.hasAttribute('data-no-overlay')) return;
                    var target = (form.getAttribute('target') || '').toLowerCase();
                    if (target === '_blank') return;

                    intendsToNavigate = true;
                    lastHref = null;
                    setTimeout(function() {
                        intendsToNavigate = false;
                    }, 100);
                }, true);

                // Beforeunload: show overlay only on real navigation
                window.addEventListener('beforeunload', function() {
                    if (document.cookie.indexOf('is_app=1') === -1) return;
                    if (intendsToNavigate && lastHref && isDeeplink(lastHref)) return;
                    if (intendsToNavigate) {
                        showOverlay();
                        isOverlayActive = true;
                    }
                });

                // Bfcache restore
                window.addEventListener('pageshow', function(e) {
                    if (e.persisted || isOverlayActive) {
                        hideOverlay();
                        isOverlayActive = false;
                    }
                });

                // Handle WebView visibility changes
                document.addEventListener("visibilitychange", function() {
                    if (document.visibilityState === "visible") {
                        hideOverlay();
                        isOverlayActive = false;
                    }
                });

                // Ensure overlay is hidden on initial load
                hideOverlay();
                isOverlayActive = false;

                // Optional: Listen for Flutter WebView resume
                if (window.flutter_inappwebview) {
                    window.flutter_inappwebview.callHandler('getWebViewState').then(function() {
                        hideOverlay();
                        isOverlayActive = false;
                    });
                }
            })();
        </script>
<?php
    }
}
