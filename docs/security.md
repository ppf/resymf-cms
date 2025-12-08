# Security

This guide covers security features and best practices in ReSymf-CMS.

## Authentication

### Password Hashing

Passwords are hashed with bcrypt (cost 12):

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        App\Entity\User:
            algorithm: bcrypt
            cost: 12
```

### Login Throttling

Brute force protection: 5 attempts per minute.

```yaml
firewalls:
    main:
        login_throttling:
            max_attempts: 5
            interval: '1 minute'
```

After 5 failed attempts, the user must wait 1 minute.

### Remember Me

Optional "remember me" functionality (1 week):

```yaml
remember_me:
    secret: '%kernel.secret%'
    lifetime: 604800  # 1 week
    path: /
    always_remember_me: false
```

---

## Two-Factor Authentication

ReSymf-CMS supports TOTP-based 2FA via scheb/2fa-bundle.

### Configuration

```yaml
# config/packages/scheb_2fa.yaml
scheb_two_factor:
    security_tokens:
        - Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken

    totp:
        enabled: true
        server_name: ReSymf-CMS
        issuer: ReSymf-CMS
        window: 1
        parameters:
            image: 'https://your-domain.com/logo.png'
```

### Routes

```yaml
# config/routes/scheb_2fa.yaml
2fa_login:
    path: /2fa
    defaults:
        _controller: "scheb_two_factor.form_controller::form"

2fa_login_check:
    path: /2fa_check
```

### User Entity

Users with 2FA enabled have:
- `totpSecret` - The shared secret
- `isTotpEnabled()` - Whether 2FA is active

### Setup Flow

1. User enables 2FA in settings
2. System generates TOTP secret
3. User scans QR code with authenticator app
4. User confirms with a valid code
5. 2FA is now required at login

---

## CSRF Protection

All forms and destructive actions require CSRF tokens.

### Form Login

```yaml
form_login:
    enable_csrf: true
```

### Manual CSRF Validation

In controller:
```php
if ($this->isCsrfTokenValid('delete' . $entity->getId(), $request->request->get('_token'))) {
    // Process action
}
```

In Twig:
```twig
<form method="post">
    <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ entity.id) }}">
    <button type="submit">Delete</button>
</form>
```

---

## Access Control

### Role Hierarchy

```yaml
role_hierarchy:
    ROLE_ADMIN: ROLE_USER
```

### Route Protection

```yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/api, roles: ROLE_USER }
```

### Controller Protection

```php
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
```

---

## User Impersonation

Admins can impersonate users for debugging:

```yaml
switch_user: true
```

Usage:
```
https://example.com?_switch_user=target_username
```

Exit impersonation:
```
https://example.com?_switch_user=_exit
```

---

## Security Headers

Recommended Nginx headers:

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

---

## Input Validation

### Entity Constraints

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotBlank]
#[Assert\Length(min: 2, max: 100)]
private string $name;

#[Assert\Email]
private string $email;

#[Assert\Regex(pattern: '/^[a-z0-9-]+$/')]
private string $slug;
```

### Form Validation

Forms automatically validate against entity constraints.

---

## SQL Injection Prevention

Always use parameterized queries:

```php
// Good - parameterized
$this->createQueryBuilder('u')
    ->andWhere('u.username = :username')
    ->setParameter('username', $username);

// Bad - string concatenation
// $query = "SELECT * FROM users WHERE username = '$username'";
```

---

## XSS Prevention

Twig automatically escapes output:

```twig
{{ user.name }}              {# Auto-escaped #}
{{ user.html|raw }}          {# Use raw only when necessary #}
```

---

## Sensitive Data

### Environment Variables

Never commit secrets. Use `.env.local`:

```dotenv
APP_SECRET=your-secret-key
DATABASE_URL=mysql://user:password@localhost/db
```

### .gitignore

```gitignore
.env.local
.env.*.local
*.pem
*.key
```

---

## Security Scanning

### Composer Audit

```bash
composer audit
```

### CI Security Scan

The `security-scan.yml` workflow runs weekly:
- Composer vulnerability check
- OWASP dependency check
- Psalm taint analysis

---

## Checklist

Security best practices:

1. [ ] Strong APP_SECRET (32+ random bytes)
2. [ ] Database credentials not in code
3. [ ] CSRF tokens on all forms
4. [ ] Input validation on all user input
5. [ ] Parameterized database queries
6. [ ] Rate limiting on login
7. [ ] 2FA available for sensitive accounts
8. [ ] Regular security updates
9. [ ] HTTPS in production
10. [ ] Proper file permissions
