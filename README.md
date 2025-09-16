# PHP Email Verification

## Description

PHP Email Verification is a secure system for verifying email addresses through opt-in forms. It sends verification emails with unique tokens that users click to confirm their email addresses.

## Features

### Core Functionality
- Email verification with cryptographically secure tokens
- User registration with name and email capture
- IP address logging for security auditing

### Security Features
- **CSRF Protection**: Token-based protection against Cross-Site Request Forgery attacks
- **XSS Prevention**: Comprehensive output escaping and Content Security Policy headers
- **SQL Injection Prevention**: Parameterized queries using PDO prepared statements
- **Bot Protection**: Google [ReCaptcha](https://github.com/google/recaptcha) v2 integration
- **Secure Token Generation**: 256-bit cryptographically secure tokens using `random_bytes()`
- **Security Headers**: Complete set of security headers (CSP, X-Frame-Options, HSTS, etc.)
- **No Sequential ID Exposure**: Verification uses only secure tokens, preventing user enumeration
- **Environment Variable Management**: Sensitive data stored securely via [PHP dotenv](https://github.com/vlucas/phpdotenv)
- **Secure Email Delivery**: SMTP authentication support using [PHPMailer](https://github.com/PHPMailer/PHPMailer)
- **Error Handling**: Safe error messages that don't expose system information

## Installation/usage

1. Install dependencies with composer: `composer install`
2. [Register for ReCaptcha sitekey and secret](https://www.google.com/recaptcha/admin)
3. Create a MySQL/MariaDB database
4. Copy .env.example to .env, and fill required values: `cp .env.example .env`
5. Configure your web server to point to the project directory
6. Ensure PHP 7.4+ is installed with PDO MySQL extension

### Environment Variables

Required variables in `.env`:
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` - Database connection
- `URL` - Your site's base URL (e.g., https://example.com)
- `RECAPTCHA_SITEKEY`, `RECAPTCHA_SECRET` - ReCaptcha credentials
- `EMAIL_HOST`, `EMAIL_FROM`, `EMAIL_FROM_NAME` - Email configuration
- `EMAIL_SUBJECT`, `EMAIL_BODY` - Email content
- `SMTP_AUTH` - Set to 'true' if using SMTP authentication

Optional SMTP variables (when `SMTP_AUTH=true`):
- `SMTP_SECURE` - Encryption type ('tls' or 'ssl')
- `SMTP_USERNAME`, `SMTP_PASSWORD` - SMTP credentials

Optional security variables:
- `APP_ENV` - Set to 'development' to see detailed errors (use 'production' in live environments)

## Project Structure

```
php-email-verification/
├── submit.php           # Registration form and submission handler
├── verify.php           # Email verification handler
├── db.php               # Database connection and schema
├── error_handler.php    # Error handling utilities
├── csrf_protection.php  # CSRF token management
├── security_headers.php # Security headers configuration
├── uuid_generator.php   # UUID generation utilities
├── .env.example         # Environment variables template
└── composer.json        # PHP dependencies
```

## Security Considerations

### Rate Limiting (Implementation Required)

While this project includes many security features, **rate limiting is not implemented** and should be added before production deployment. Consider these approaches:

#### Application-Level Rate Limiting

1. **Database-based throttling**:
```php
// Example: Track submission attempts
function checkRateLimit($ip, $maxAttempts = 5, $window = 300) {
    // Store attempt timestamps in database
    // Check if IP exceeded maxAttempts in last $window seconds
    // Return true if allowed, false if rate limited
}
```

2. **Redis/Memcached-based limiting**:
```php
// Example using Redis
$key = "rate_limit:register:" . $ip;
$attempts = $redis->incr($key);
if ($attempts === 1) {
    $redis->expire($key, 300); // 5 minute window
}
return $attempts <= 5;
```

3. **Session-based limiting** (less secure, easily bypassed):
```php
$_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
$_SESSION['first_attempt'] = $_SESSION['first_attempt'] ?? time();
```

#### Infrastructure-Level Rate Limiting

1. **Web Server Configuration**:
   - **Nginx**: Use `limit_req_zone` and `limit_req` directives
   - **Apache**: Use `mod_ratelimit` or `mod_evasive`

2. **CDN/WAF Solutions**:
   - Cloudflare Rate Limiting
   - AWS WAF rate-based rules
   - Fastly rate limiting

3. **Fail2ban Integration**:
   - Monitor application logs for failed attempts
   - Automatically ban IPs at firewall level

#### Recommended Limits

- **Registration endpoint** (`submit.php`):
  - 5 attempts per IP per 15 minutes
  - 20 attempts per IP per hour
  - 100 attempts per IP per day

- **Verification endpoint** (`verify.php`):
  - 10 attempts per IP per 5 minutes
  - 50 attempts per IP per hour

- **Consider implementing**:
  - Progressive delays (exponential backoff)
  - CAPTCHA challenges after threshold
  - Account lockouts for repeated failures
  - Email notification for suspicious activity

### Additional Production Recommendations

1. **HTTPS Only**: Always use HTTPS in production
2. **Database Security**:
   - Use least-privilege database user
   - Consider database connection pooling
   - Regular backups
3. **Monitoring**:
   - Log all registration attempts
   - Monitor for unusual patterns
   - Set up alerts for high failure rates
4. **Token Expiration**: Consider adding expiration times to verification tokens
5. **Email Security**:
   - Implement SPF, DKIM, and DMARC
   - Monitor email bounce rates
   - Handle email delivery failures gracefully
6. **Input Validation**: Add additional email format validation
7. **Password Protection**: If extending to full authentication, use `password_hash()` and `password_verify()`

## Database Schema

The system automatically creates a `users` table with:
- `id`: Internal auto-incrementing ID (not exposed)
- `email`: User's email address
- `name`: User's name
- `validated`: Boolean verification status
- `token`: Unique verification token (indexed)
- `ipaddress`: Registration IP address

## Dependencies

Dependencies are managed by Composer:

- **PHP 7.4+** with PDO MySQL extension
- **MySQL/MariaDB** database
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - Email sending
- [ReCaptcha](https://github.com/google/recaptcha) - Bot protection
- [PHP dotenv](https://github.com/vlucas/phpdotenv) - Environment management

## Testing

Before deployment:
1. Test with invalid tokens to ensure proper error handling
2. Verify CSRF protection by attempting cross-origin submissions
3. Check email delivery to various providers
4. Test ReCaptcha with both success and failure cases
5. Verify all security headers are properly set
6. Test with development mode off to ensure errors aren't exposed

## Contributing

1. Fork this repo
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a pull request

## Support

For issues, questions, or suggestions, please create an issue on GitHub.
