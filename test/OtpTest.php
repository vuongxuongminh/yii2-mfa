<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\test\unit\mfa;

use DateTimeImmutable;

use PHPUnit\Framework\Attributes\DataProvider;

use Psr\Clock\ClockInterface;

use vxm\mfa\Otp;
use vxm\mfa\SystemClock;

/**
 * Class OtpTest checks TOTP generation against a fixed PSR-20 clock using RFC 6238 test vectors.
 */
class OtpTest extends TestCase
{
    /**
     * RFC 6238 Appendix B test vectors (SHA1, 8 digits, secret "12345678901234567890").
     */
    public static function rfc6238Provider(): array
    {
        return [
            [59, '94287082'],
            [1111111109, '07081804'],
            [1111111111, '14050471'],
            [1234567890, '89005924'],
            [2000000000, '69279037'],
        ];
    }

    #[DataProvider('rfc6238Provider')]
    public function testGenerateUsesConfiguredClock(int $timestamp, string $expected)
    {
        $otp = $this->createOtp($timestamp);

        $this->assertSame($expected, $otp->generate('12345678901234567890'));
    }

    public function testValidateUsesConfiguredClock()
    {
        $otp = $this->createOtp(59);

        $this->assertTrue($otp->validate('12345678901234567890', '94287082'));
        $this->assertFalse($otp->validate('12345678901234567890', '07081804'));
    }

    public function testSystemClockReturnsCurrentTime()
    {
        $before = time();
        $now = (new SystemClock())->now()->getTimestamp();

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual(time(), $now);
    }

    private function createOtp(int $timestamp): Otp
    {
        $clock = new class($timestamp) implements ClockInterface {
            public function __construct(private int $timestamp)
            {
            }

            public function now(): DateTimeImmutable
            {
                return (new DateTimeImmutable())->setTimestamp($this->timestamp);
            }
        };

        return new Otp(['digits' => 8, 'clock' => $clock]);
    }
}
