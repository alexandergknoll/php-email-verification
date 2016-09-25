# PHP Email Validator

## Description

PHP Email Validator is a set of simple scripts to validate email addresses.  Upon completion of an opt-in form, an email is produced with a 'validation' link.

## Features

- Secret management via [PHP dotenv](https://github.com/vlucas/phpdotenv) environment variables
- Google [ReCaptcha](https://github.com/google/recaptcha) spam filtering
- Secure SMTP authentication for emails using [PHPMailer](https://github.com/PHPMailer/PHPMailer)
- Prepared statements to prevent SQL injection attacks

## Installation/usage

1. Install dependencies with composer: `composer install`
2. [Register for ReCaptcha sitekey and secret](https://www.google.com/recaptcha/admin)
3. Create an empty database
4. Copy .env.example to .env, and fill required values: `cp .env.example .env`

## Dependencies

Dependencies are managed by Composer:

- MySQL-compatible database
- [PHPMailer](https://github.com/PHPMailer/PHPMailer)
- [ReCaptcha](https://github.com/google/recaptcha)
- [PHP dotenv](https://github.com/vlucas/phpdotenv)

## Contributing

1. Fork this repo
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a pull request
