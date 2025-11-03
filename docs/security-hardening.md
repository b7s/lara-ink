# Security Hardening Guide

This guide describes concrete practices to protect LaraInk-powered applications. Adopt these recommendations in addition to Laravel's security baseline.

## 1. Transport Layer Security

- Force HTTPS everywhere (HSTS enabled) and block plain HTTP traffic.
- Terminate TLS using modern ciphers (TLS 1.2+) and rotate certificates regularly.
- If you sit behind a proxy (Cloudflare, AWS ALB), ensure `X-Forwarded-Proto` is honoured by Laravel via `TrustedProxies` configuration.

## 2. Authentication Token Safety

### 2.1 Rotate and Revoke
- On each successful login or token refresh, revoke the previous token: `request()->user()?->currentAccessToken()?->delete();`
- Keep the TTL short by tuning `auth.token_ttl` (config/lara-ink.php) to a few minutes.

### 2.2 Bind Token to Device
- Store the request IP and User-Agent alongside the token record in `personal_access_tokens` and reject mismatches using a middleware executed after `auth:sanctum`.

```php
$token = $user->createToken('lara-ink-token', ['*'], $expiresAt);
$token->accessToken->forceFill([
    'ip_address' => $request->ip(),
    'user_agent' => substr((string) $request->userAgent(), 0, 255),
])->save();
```

### 2.3 Protect Frontend Storage
- Prefer HttpOnly cookies with `SameSite=strict`. If you must keep the token in JavaScript (`window.lara_ink.token`), disable inline scripts and third-party injections using CSP headers.
- Never log tokens to console or persistent logs.

## 3. Session and CSRF Hygiene

- Sanctum uses the session cookie. Keep `SESSION_COOKIE` scoped to the main domain and mark it `secure` and `http_only`.
- Enable CSRF middleware on any Laravel endpoints the SPA interacts with when using cookie-based auth.

## 4. Route Protection

- Always combine `->auth(true)` with specific `->middleware([...])` rules.
- Use role or permission-based middleware (`role:admin`, `can:edit,post`, custom guards) for fine-grained control.
- Add rate limiting (`throttle:60,1`) to sensitive endpoints, especially authentication and password reset flows.

## 5. Input Validation & Output Encoding

- Sanitize request payloads on the backend with Laravel form requests.
- When inserting user content into the DSL, escape or whitelist content before build time. LaraInk converts DSL to Alpine.js; still, avoid trusting unvalidated PHP variables.

## 6. Configuration Hardening

- Keep `.env` secrets out of version control and rotate them periodically.
- Configure `APP_ENV=production` and `APP_DEBUG=false` in production builds.
- Use different API base URLs (`lara-ink.api_base_url`) per environment to avoid leaking staging servers.

## 7. Build and Deployment Hygiene

- Run `php artisan config:clear`, `route:clear`, and cache configurations after deployment.
- Ensure the `public/pages` and `public/build` directories are regenerated and not writable by the web server end user beyond what is necessary.

## 8. Monitoring and Incident Response

- Log authentication attempts, token creation and revocation events.
- Trigger alerts on anomalous patterns (multiple IPs for the same token, repeated 401/403 responses).
- Provide a manual “force logout” capability by deleting tokens: `$user->tokens()->delete();`

## 9. Content Security Policy (CSP)

- Adopt a strict CSP forbidding inline scripts (`script-src 'self'` plus hash/nonce if needed).
- Allow only the domains serving your compiled assets (`public/build`) and API endpoints.

## 10. Testing Checklist

- ✅ Run automated security tests (OWASP ZAP, Laravel Security Checker) in CI.
- ✅ Pen-test token reuse by attempting requests from different IPs/agents.
- ✅ Verify CSP and HSTS headers with tools like securityheaders.com.

Following this checklist minimizes attack surface and keeps LaraInk deployments aligned with Laravel security best practices.
