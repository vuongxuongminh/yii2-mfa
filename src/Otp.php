<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\mfa;

use yii\base\BaseObject;
use yii\base\NotSupportedException;

use OTPHP\HOTP;
use OTPHP\TOTP;

use ParagonIE\ConstantTime\Base32;

/**
 * Class Otp support generate and validate by given secret key.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Otp extends BaseObject
{
    /**
     * TOTP is a time based one-time password. It lives only for a few seconds (the period). You just have to be sure that the clock of your server and your device are synchronized. This is the most common OTP.
     */
    const TOTP = 0;

    /**
     * HOTP is a counter based one-time password. Every time a password is used, the counter is updated. You have to verify that the server and the device are synchronized.
     */
    const HOTP = 1;

    /**
     * @var string type of otp
     *
     * @see self::HOTP
     * @see self::TOTP
     */
    public $type = self::TOTP;

    /**
     * @var int otp digits. Default the number is 6
     */
    public $digits = 6;

    /**
     * @var string digest algorithm. Default is 'sha1' you can use any algorithm listed by hash_algos(). Note that most applications only support md5, sha1, sha256 and sha512.
     */
    public $digest = 'sha1';

    /**
     * @var string the template of qr code uri server. Please note that this URI MUST contain a placeholder {PROVISIONING_URI} for the OTP Provisioning URI.
     */
    public $qrCodeUriTemplate = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl={PROVISIONING_URI}';

    /**
     * @var string the placeholder of qr code uri server.
     */
    public $qrCodeUriPlaceholder = '{PROVISIONING_URI}';

    /**
     * Generate an otp digits.
     *
     * @param string $secretKey the secret key use to generate an otp
     * @return string an otp generated by secret key given.
     *
     * @throws NotSupportedException
     */
    public function generate(string $secretKey)
    {
        return $this->createInstance($secretKey)->now();
    }

    /**
     * Validate an otp digits.
     *
     * @param string $secretKey the secret key use to validate an otp.
     * @param string $otp need to verify
     * @return bool weather an otp given is valid
     *
     * @throws NotSupportedException
     */
    public function validate(string $secretKey, string $otp)
    {
        return $this->createInstance($secretKey)->verify($otp);
    }

    /**
     * Get qr code for authenticator like google authenticator.
     *
     * @param string $secretKey the secret key an authenticator use to generating an otp.
     * @param array $params list of information use to show on an authenticator app.
     *
     * Example:
     * ```php
     * ['issuer' => 'VXM', 'label' => 'vuongxuongminh@gmail.com', 'image' => 'https://google.com']
     * ```
     * @return string the qr code uri
     * @throws NotSupportedException
     */
    public function getQrCodeUri(string $secretKey, array $params)
    {
        $instance = $this->createInstance($secretKey);

        foreach ($params as $param => $value) {
            $instance->setParameter($param, $value);
        }

        return $instance->getQrCodeUri($this->qrCodeUriTemplate, $this->qrCodeUriPlaceholder);
    }

    /**
     * Create an otp instance.
     *
     * @param string $secretKey the secret key use to create an otp instance.
     *
     * @return HOTP|TOTP an object instance for generate and validate otp.
     * @throws NotSupportedException
     */
    protected function createInstance(string $secretKey)
    {
        $secretKey = Base32::encodeUpper($secretKey);

        if ($this->type === self::TOTP) {
            return TOTP::create($secretKey, 30, $this->digest, $this->digits);
        } elseif ($this->type === self::HOTP) {
            return HOTP::create($secretKey, 0, $this->digest, $this->digits);
        } else {
            throw new NotSupportedException("An otp type: `{$this->type}` is not supported!");
        }
    }

}
