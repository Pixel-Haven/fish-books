# Authentication Setup - Session-Based with Sanctum

## Overview
Fish Books uses **session-based authentication** with Laravel Sanctum for stateful authentication. This approach is ideal for Inertia.js applications where the frontend and backend are on the same domain.

## Key Components

### 1. Authentication Flow
- **Login**: User submits credentials via Inertia form → POST `/login` → AuthController validates → Sets session cookie → Redirects to `/dashboard`
- **Logout**: User clicks logout → Inertia router.post(`/logout`) → AuthController destroys session → Redirects to `/login`
- **API Requests**: Include session cookie automatically + CSRF token in headers

### 2. Backend Configuration

#### Web Routes (`routes/web.php`)
```php
// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', ...)->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::get('/crew-members', ...);
    // ...more routes
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
```

#### API Routes (`routes/api.php`)
```php
// Protected with Sanctum stateful authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('crew-members', CrewMemberController::class);
    Route::apiResource('vessels', VesselController::class)->middleware('role:OWNER');
    // ...more API routes
});
```

#### Auth Controller (`app/Http/Controllers/Auth/AuthController.php`)
- Uses `Auth::attempt()` for session authentication
- Returns Inertia responses (not JSON)
- Redirects to `/dashboard` on success
- Validates credentials and handles errors

#### Sanctum Configuration (`config/sanctum.php`)
```php
'stateful' => ['localhost', 'localhost:8000', '127.0.0.1', ...],
'guard' => ['web'], // Uses session-based web guard
```

#### Exception Handler (`bootstrap/app.php`)
```php
// API routes return JSON for 401 errors
if ($request->is('api/*') && $response->getStatusCode() === 401) {
    return response()->json(['message' => 'Unauthenticated.'], 401);
}
```

### 3. Frontend Configuration

#### CSRF Token Setup
**resources/views/app.blade.php**:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

**All fetch() requests include**:
```typescript
headers: {
  'X-CSRF-TOKEN': getCsrfToken(),
  'Accept': 'application/json',
},
credentials: 'include', // Include session cookies
```

#### Login Page (`resources/js/pages/Auth/Login.vue`)
```typescript
const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => {
      form.password = '';
    },
  });
};
```

#### Main Layout (`resources/js/layouts/MainLayout.vue`)
```typescript
// User data from Inertia shared props
const user = computed(() => page.props.auth.user);

// Logout using Inertia
const logout = () => {
  router.post('/logout');
};
```

#### Inertia Middleware (`app/Http/Middleware/HandleInertiaRequests.php`)
```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
            ] : null,
        ],
        'flash' => [
            'message' => fn () => $request->session()->get('message'),
            'error' => fn () => $request->session()->get('error'),
        ],
    ];
}
```

## Authentication States

### Unauthenticated User
1. Visits any protected route → Redirected to `/login`
2. Accesses API endpoint → Returns 401 JSON error
3. Can only access `/login` page

### Authenticated User
1. Can access all protected routes based on role
2. API requests authenticated via session cookie
3. Cannot access `/login` (redirected to `/dashboard`)
4. User data available in all Inertia pages via `page.props.auth.user`

## Security Features

1. **CSRF Protection**: All POST/PUT/DELETE requests require valid CSRF token
2. **Session Security**: Sessions encrypted and httpOnly cookies
3. **Role-Based Access**: Middleware checks user role for restricted routes
4. **XSS Protection**: Inertia escapes data by default
5. **Sanctum Stateful**: Only whitelisted domains can make authenticated requests

## Testing Authentication

### Test Credentials
- **Owner**: owner@hushiyaaru.com / owner123
- **Manager**: manager@hushiyaaru.com / manager123

### Test Flow
1. Visit `http://localhost:8000` → Should redirect to `/login`
2. Login with test credentials → Should redirect to `/dashboard`
3. Dashboard should display user name in sidebar
4. API calls should work (crew members list, stats, etc.)
5. Logout → Should redirect to `/login`
6. Try accessing `/dashboard` after logout → Should redirect to `/login`

## Common Issues & Solutions

### Issue: 401 on API requests
**Solution**: Ensure CSRF token is included in request headers and `credentials: 'include'` is set

### Issue: "The GET method is not supported for route api/login"
**Solution**: This happens when redirecting to login from API routes. Fixed by returning JSON 401 responses for API routes in exception handler.

### Issue: User not persisted across page refreshes
**Solution**: Ensure Sanctum stateful domains are configured correctly and session middleware is applied.

### Issue: Cannot access protected pages after login
**Solution**: Check that `HandleInertiaRequests` middleware is sharing `auth.user` and that `auth` middleware is applied to routes.

## Best Practices

1. **Always use Inertia forms** for authentication (login, logout)
2. **Never store tokens in localStorage** - Use session cookies instead
3. **Include CSRF tokens** in all state-changing requests
4. **Use `page.props.auth.user`** to access user data in components
5. **Use `router.post('/logout')`** instead of manual fetch for logout
6. **Add `credentials: 'include'`** to all fetch() API calls
7. **Separate web and API auth controllers** to handle different response types

## Architecture Benefits

✅ **Secure**: Session-based auth is more secure than token-based for same-domain apps
✅ **Simple**: No need to manage token storage and expiration
✅ **Scalable**: Sanctum handles both stateful (web) and stateless (mobile API) auth
✅ **Standard**: Follows Laravel and Inertia best practices
✅ **Maintainable**: Clear separation between web and API authentication
