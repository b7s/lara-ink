---
title: Login
meta:
  robots: noindex, nofollow
---

<div class="lara-ink-auth">
    <div class="lara-ink-auth-card">
        <div class="lara-ink-auth-header">
            <h1 class="lara-ink-auth-title">{{ config('app.name', 'LaraInk') }}</h1>
            <p class="lara-ink-auth-subtitle">Sign in to your account</p>
        </div>

        <form id="loginForm" class="lara-ink-auth-form" x-data="loginForm()">
            <div class="lara-ink-field" :class="{ 'lara-ink-field-error': errors.email }">
                <label for="email" class="lara-ink-field-label">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                    x-model="email"
                    placeholder="your@email.com"
                    required
                    autocomplete="email"
                    class="lara-ink-input"
                >
                <span class="lara-ink-field-error" x-show="errors.email" x-text="errors.email"></span>
            </div>

            <div class="lara-ink-field" :class="{ 'lara-ink-field-error': errors.password }">
                <label for="password" class="lara-ink-field-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                    x-model="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                    class="lara-ink-input"
                >
                <span class="lara-ink-field-error" x-show="errors.password" x-text="errors.password"></span>
            </div>

            <div class="lara-ink-checkbox-group">
                <label class="lara-ink-checkbox-label">
                    <input type="checkbox" class="lara-ink-checkbox" x-model="remember">
                    <span>Remember me</span>
                </label>
                <a href="/forgot-password" class="lara-ink-link">Forgot password?</a>
            </div>

            <div class="lara-ink-alert lara-ink-alert-error" x-show="errors.general" x-text="errors.general"></div>

            <button 
                type="submit"
                class="lara-ink-button"
                :disabled="loading"
                @click.prevent="handleLogin"
            >
                <span x-show="!loading">Sign In</span>
                <span x-show="loading" class="lara-ink-auth-loader">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                    Signing in...
                </span>
            </button>
        </form>

        <div class="lara-ink-auth-footer">
            <a href="/forgot-password" class="lara-ink-link">Forgot your password?</a>
        </div>
    </div>
</div>

<script>
    function loginForm() {
        return {
            email: '',
            password: '',
            remember: false,
            loading: false,
            errors: {},

            async handleLogin() {
                this.errors = {};
                this.loading = true;

                // Basic validation
                if (!this.email) {
                    this.errors.email = 'Email is required';
                    this.loading = false;
                    return;
                }

                if (!this.password) {
                    this.errors.password = 'Password is required';
                    this.loading = false;
                    return;
                }

                try {
                    const response = await window.lara_ink.newReq('/login', {
                        method: 'POST',
                        body: JSON.stringify({
                            email: this.email,
                            password: this.password,
                            remember: this.remember
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.token) {
                        // Store the bearer token
                        window.lara_ink.token = data.token;
                        localStorage.setItem('lara_ink_token', data.token);

                        // Redirect to intended page or home
                        const redirectTo = new URLSearchParams(window.location.search).get('redirect') || '/';
                        window.location.href = redirectTo;
                    } else {
                        // Handle validation errors
                        if (data.errors) {
                            this.errors = data.errors;
                        } else {
                            this.errors.general = data.message || 'Invalid credentials. Please try again.';
                        }
                    }
                } catch (error) {
                    console.error('Login error:', error);
                    this.errors.general = 'An error occurred. Please try again later.';
                } finally {
                    this.loading = false;
                }
            }
        };
    }
</script>
