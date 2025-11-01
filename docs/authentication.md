# Authentication

LaraInk uses Laravel Sanctum for secure Bearer Token authentication between your SPA and Laravel backend.

## Setup

### 1. Install Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\ServiceProvider"
php artisan migrate
```

### 2. Configure Sanctum

Add to `config/sanctum.php`:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
```

### 3. Add API Routes

LaraInk automatically registers these routes:

- `POST /api/ink/login` - User login
- `POST /api/ink/logout` - User logout
- `GET /api/ink/is-authenticated` - Check authentication status

## Protecting Pages

### Require Authentication

Use `->auth(true)` in your page configuration:

```php
<?php
ink_make()
    ->title('Dashboard')
    ->auth(true);
?>

<div>
    <h1>Welcome to your dashboard!</h1>
</div>
```

When a user tries to access this page:
1. LaraInk checks for a valid token
2. If no token or expired → redirect to login
3. If valid → page loads normally

### Custom Middleware

Apply custom middleware for role-based access:

```php
<?php
ink_make()
    ->title('Admin Panel')
    ->auth(true)
    ->middleware('admin');
?>
```

## Login Flow

### Create Login Page

File: `pages/login.php`

```php
<?php
ink_make()
    ->title('Login')
    ->layout('auth');
?>

<div x-data="loginForm()">
    <form @submit.prevent="login">
        <h1>Login</h1>
        
        <template x-if="error">
            <div class="alert-error" x-text="error"></div>
        </template>
        
        <div>
            <label>Email</label>
            <input type="email" x-model="email" required>
        </div>
        
        <div>
            <label>Password</label>
            <input type="password" x-model="password" required>
        </div>
        
        <button type="submit" :disabled="loading">
            <span x-show="!loading">Login</span>
            <span x-show="loading">Logging in...</span>
        </button>
    </form>
</div>

<script>
function loginForm() {
    return {
        email: '',
        password: '',
        error: null,
        loading: false,
        
        async login() {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await fetch(lara_ink.api_base_url + '/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: this.email,
                        password: this.password
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    this.error = data.message || 'Login failed';
                    return;
                }
                
                // Store token
                lara_ink.token = data.token;
                localStorage.setItem('lara_ink_token', data.token);
                
                // Redirect to dashboard
                window.location.href = '/dashboard';
                
            } catch (error) {
                this.error = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
```

### Backend Login Controller

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }
        
        $user = Auth::user();
        $token = $user->createToken('lara-ink')->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }
}
```

Register the route in `routes/api.php`:

```php
Route::post('/login', [LoginController::class, 'login']);
```

## Logout

### Logout Button

```php
<button @click="logout()">Logout</button>

<script>
async function logout() {
    await lara_ink.logout();
    window.location.href = '/login';
}
</script>
```

The `lara_ink.logout()` method:
1. Calls `/api/ink/logout` to revoke the token
2. Clears local storage
3. Resets the token

## Checking Authentication

### In Pages

```php
<div x-data="{ isAuthenticated: false }" x-init="isAuthenticated = await lara_ink.is_authenticated()">
    <template x-if="isAuthenticated">
        <div>You are logged in!</div>
    </template>
    
    <template x-if="!isAuthenticated">
        <div>Please <a href="/login">log in</a></div>
    </template>
</div>
```

### In API Calls

All calls via `lara_ink.newReq()` automatically include the Bearer token:

```php
<script>
async function fetchUserData() {
    // Token is automatically included
    const response = await lara_ink.newReq('/api/user');
    
    if (response.status === 401) {
        // Token expired or invalid
        window.location.href = '/login';
        return;
    }
    
    const user = await response.json();
    return user;
}
</script>
```

## Token Management

### Automatic Token Refresh

LaraInk automatically checks token validity:

```javascript
// Checks every 60 seconds
if (lastCheck && (now - parseInt(lastCheck)) < 60000) {
    return true;
}
```

### Manual Token Refresh

```php
<script>
async function refreshToken() {
    const response = await lara_ink.newReq('/api/ink/is-authenticated');
    
    if (response.ok) {
        const data = await response.json();
        if (data.token) {
            lara_ink.token = data.token;
            localStorage.setItem('lara_ink_token', data.token);
        }
    }
}
</script>
```

## Complete Example: Protected Dashboard

```php
<?php
ink_make()
    ->title('Dashboard')
    ->auth(true)
    ->cache(false); // Don't cache authenticated pages
?>

<div x-data="dashboard()">
    <header>
        <h1>Dashboard</h1>
        <div>
            <span x-text="user.name"></span>
            <button @click="logout()">Logout</button>
        </div>
    </header>
    
    <main>
        <template x-if="loading">
            <div>Loading...</div>
        </template>
        
        <template x-if="!loading">
            <div>
                <h2>Your Stats</h2>
                <div class="stats">
                    <div>
                        <span>Posts</span>
                        <strong x-text="stats.posts"></strong>
                    </div>
                    <div>
                        <span>Comments</span>
                        <strong x-text="stats.comments"></strong>
                    </div>
                    <div>
                        <span>Likes</span>
                        <strong x-text="stats.likes"></strong>
                    </div>
                </div>
            </div>
        </template>
    </main>
</div>

<script>
function dashboard() {
    return {
        user: {},
        stats: {},
        loading: true,
        
        async init() {
            // Check authentication
            const isAuth = await lara_ink.is_authenticated();
            
            if (!isAuth) {
                window.location.href = '/login';
                return;
            }
            
            // Load user data
            await this.loadData();
        },
        
        async loadData() {
            try {
                const response = await lara_ink.newReq('/api/dashboard');
                
                if (!response.ok) {
                    throw new Error('Failed to load dashboard');
                }
                
                const data = await response.json();
                this.user = data.user;
                this.stats = data.stats;
                
            } catch (error) {
                console.error('Dashboard error:', error);
                alert('Failed to load dashboard data');
            } finally {
                this.loading = false;
            }
        },
        
        async logout() {
            await lara_ink.logout();
            window.location.href = '/login';
        }
    }
}
</script>
```

## Configuration

Edit `config/lara-ink.php`:

```php
'auth' => [
    'route' => [
        'prefix' => '/api/ink',
        'login' => '/login',              // Redirect here if not authenticated
        'unauthorized' => '/unauthorized', // Redirect here if unauthorized
    ],
    'token_ttl' => 900, // Token lifetime in seconds (15 minutes)
],
```

## Best Practices

### 1. Never Cache Authenticated Pages

```php
<?php
ink_make()
    ->auth(true)
    ->cache(false); // Important!
?>
```

### 2. Handle Token Expiration

```php
async function apiCall() {
    const response = await lara_ink.newReq('/api/data');
    
    if (response.status === 401) {
        // Token expired
        await lara_ink.logout();
        window.location.href = '/login';
        return;
    }
    
    return await response.json();
}
```

### 3. Secure Sensitive Operations

```php
async function deleteAccount() {
    if (!confirm('Are you sure?')) return;
    
    const response = await lara_ink.newReq('/api/account', {
        method: 'DELETE'
    });
    
    if (response.ok) {
        await lara_ink.logout();
        window.location.href = '/';
    }
}
```

## Next Steps

- [API Integration](api-integration.md) - Make authenticated API calls
- [Routing](routing.md) - Protect specific routes
- [Deployment](deployment.md) - Deploy your authenticated SPA
