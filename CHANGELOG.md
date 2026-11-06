# Changelog

## Unreleased

- Require `spomky-labs/otphp` `^11.4.3` (fixes GHSA-2jx3-65f3-xr8r and GHSA-g7m4-839x-ch6v).
- Require PHP `>=8.1`; tested on PHP 8.4.
- Added `vxm\mfa\SystemClock` (PSR-20) and `Otp::$clock`, passed to TOTP to avoid the otphp 11.3+ deprecation.
- Added `Otp::$qrCodeUriPlaceholder`.
- Default QR code service changed to `api.qrserver.com` (Google Charts QR API was discontinued).
- Tests migrated to PHPUnit 11.
- BC break: a missing OTP label now throws `OTPHP\Exception\InvalidLabelException` (previously `Assert\InvalidArgumentException`).
