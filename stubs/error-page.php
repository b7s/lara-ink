---
title: Error
meta:
  robots: noindex, nofollow
---

<div class="lara-ink-error-page" x-data="errorPage()">
    <div class="lara-ink-error-card">
        <div class="lara-ink-error-icon" :class="'lara-ink-error-' + errorCode">
            <svg x-show="errorCode === '404'" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M16 16s-1.5-2-4-2-4 2-4 2"></path>
                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                <line x1="15" y1="9" x2="15.01" y2="9"></line>
            </svg>

            <svg x-show="errorCode === '401' || errorCode === '403'" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>

            <svg x-show="errorCode === '500' || errorCode === '503'" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>

            <svg x-show="!['404', '401', '403', '500', '503'].includes(errorCode)" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>

        <div class="lara-ink-error-code" x-text="errorCode"></div>
        <h1 class="lara-ink-error-title" x-text="errorTitle"></h1>
        <p class="lara-ink-error-message" x-text="errorMessage"></p>

        <div class="lara-ink-error-actions">
            <a href="/" class="lara-ink-button">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Go Home
            </a>
            <button @click="goBack" class="lara-ink-button lara-ink-button-secondary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Go Back
            </button>
        </div>

        <div class="lara-ink-error-footer">
            <p>If you believe this is a mistake, please contact support.</p>
        </div>
    </div>
</div>

<script>
    function errorPage() {
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code') || '404';

        const errorMessages = {
            '400': {
                title: 'Bad Request',
                message: 'The request could not be understood by the server.'
            },
            '401': {
                title: 'Unauthorized',
                message: 'You need to be authenticated to access this resource. Please log in and try again.'
            },
            '403': {
                title: 'Access Denied',
                message: 'You don\'t have permission to access this resource. Please contact an administrator if you believe this is an error.'
            },
            '404': {
                title: 'Page Not Found',
                message: 'The page you\'re looking for doesn\'t exist. It might have been moved or deleted.'
            },
            '419': {
                title: 'Page Expired',
                message: 'Your session has expired. Please refresh the page and try again.'
            },
            '429': {
                title: 'Too Many Requests',
                message: 'You\'ve made too many requests. Please wait a moment and try again.'
            },
            '500': {
                title: 'Internal Server Error',
                message: 'Something went wrong on our end. We\'re working to fix it. Please try again later.'
            },
            '503': {
                title: 'Service Unavailable',
                message: 'The service is temporarily unavailable. Please try again in a few moments.'
            }
        };

        const error = errorMessages[code] || {
            title: 'Error',
            message: 'An unexpected error occurred. Please try again or contact support if the problem persists.'
        };

        return {
            errorCode: code,
            errorTitle: error.title,
            errorMessage: error.message,

            goBack() {
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.location.href = '/';
                }
            }
        };
    }
</script>
