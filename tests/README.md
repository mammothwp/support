# Tests

Run with `vendor/bin/phpunit`.

## Never commit key-shaped literals

Do not put anything that looks like a real credential in source, docs or
fixtures: Stripe, GitHub, AWS, Slack and Google API keys, JWTs, PEM private
key blocks and similar. Secret scanners flag them even when fake.

Build such values at runtime so no contiguous key-shaped string exists in the
file, for example a Stripe-style key joined from its prefix parts with `implode()`. The redactor tests
still exercise the real shape.
