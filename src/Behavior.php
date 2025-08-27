<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\mfa;

use Yii;

use yii\base\Behavior as BaseBehavior;
use yii\base\InvalidValueException;
use yii\di\Instance;
use yii\web\User;
use yii\web\UserEvent;
use yii\web\ForbiddenHttpException;


/**
 * Class MfaBehavior automatically redirect to verify mfa url when identity enabled it and verify digits given.
 *
 * To use MfaBehavior, configure the [[User::$identityClass]] property which should specify class implemented [[\vxm\mfa\IdentityInterface]].
 *
 * For example,
 *
 * ```php
 *
 * use yii\db\ActiveRecord;
 *
 * use vxm\mfa\IdentityInterface;
 *
 * class Identity extends ActiveRecord implements IdentityInterface {
 *
 *          public function getMfaSecretKey()
 *          {
 *              return $this->mfa_secret;
 *          }
 *
 * }
 * ```
 *
 * And attach this behavior to the [[User]] component of an application config.
 *
 * For example,
 *
 * ```php
 *
 * 'user' => [
 *      'as mfa' => [
 *           'class' => 'vxm\mfa\MfaBehavior',
 *           'verifyUrl' => 'site/mfa-verify',
 *      ]
 *
 * ]
 * ```
 *
 * Note: it only work when an owner [[User::$enableSession]] is true.
 *
 * @property Otp $otp use to validate, generating an otp digits.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class Behavior extends BaseBehavior
{

    /**
     * @var User
     */
    public $owner;

    /**
     * @var callable|bool weather enabling this behavior. This property use in special case you need to disable it in runtime environment.
     * When it is callable this object instance will be parse to first parameter.
     *
     * Example:
     * ```php
     * function(\vxm\mfa\Behavior $behavior) {
     *
     *
     * }
     * ```
     */
    public $enable = true;

    /**
     * @var string|array the URL for login when [[verifyRequired()]] is called.
     * If an array is given, [[\yii\web\UrlManager::createUrl()]] will be called to create the corresponding URL.
     * The first element of the array should be the route to the verify action, and the rest of
     * the name-value pairs are GET parameters used to construct the verify URL. For example,
     *
     * ```php
     * 'site/mfa-verify'
     * ```
     *
     * If this property is `null`, a 403 HTTP exception will be raised when [[verifyRequired()]] is called.
     */
    public $verifyUrl;

    /**
     * @var string the session variable name used to store values of an identity logged in.
     */
    public $mfaParam = '__mfa';

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (is_callable($this->enable)) {
            $this->enable = call_user_func($this->enable, $this);
        }

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function events()
    {
        if ($this->enable) {
            return [
                User::EVENT_BEFORE_LOGIN => 'beforeLogin'
            ];
        } else {
            return [];
        }
    }

    /**
     * Event trigger when before user log in to system. It will be require an user verify otp digits except when user logged in via cookie base.
     *
     * @param UserEvent $event an event triggered
     * @throws ForbiddenHttpException
     */
    public function beforeLogin(UserEvent $event)
    {
        if (!$event->isValid || !$this->enable) {
            return;
        }

        if (!$event->identity instanceof IdentityInterface) {
            throw new InvalidValueException("{$this->owner->identityClass}::findIdentity() must return an object implementing \\vxm\\mfa\\IdentityInterface.");
        }

        $isEnabled = $event->identity->getIsMfaEnabled();
        $secretKey = $event->identity->getMfaSecretKey();

        if ($isEnabled && !empty($secretKey) && $this->owner->enableSession && !$event->cookieBased) {
            $event->isValid = false;
            $this->saveIdentityLoggedIn($event->identity, $event->duration);
            $this->verifyRequired();
        }
    }

    /**
     * Switches to a logged in identity for the current user.
     *
     * @see \yii\web\User::switchIdentity()
     */
    public function switchIdentityLoggedIn()
    {
        $data = $this->getIdentityLoggedIn();

        if ($data === null) {
            return;
        }

        list($identity, $duration) = $data;
        $this->owner->switchIdentity($identity, $duration);
    }

    /**
     * Save the user identity logged in object when an identity need to verify.
     *
     * @param IdentityInterface|null $identity the identity object associated with the currently logged user.
     * @param int $duration number of seconds that the user can remain in logged-in status.
     */
    public function saveIdentityLoggedIn(IdentityInterface $identity, int $duration)
    {
        Yii::$app->getSession()->set($this->mfaParam, [$identity->getId(), $duration]);
    }

    /**
     * Get an identity logged in.
     *
     * @return array|null Returns an array of 'identity' and 'duration' if valid, otherwise null.
     * @see saveIdentityLoggedIn()
     */
    public function getIdentityLoggedIn()
    {
        $data = Yii::$app->getSession()->get($this->mfaParam);

        if ($data === null) {
            return null;
        }

        if (is_array($data) && count($data) == 2) {
            list($id, $duration) = $data;
            /* @var $class IdentityInterface */
            $class = $this->owner->identityClass;
            $identity = $class::findIdentity($id);

            if ($identity !== null) {
                if (!$identity instanceof IdentityInterface) {
                    throw new InvalidValueException("$class::findIdentity() must return an object implementing \\vxm\\mfa\\IdentityInterface.");
                } else {
                    return [$identity, $duration];
                }
            }
        }

        $this->removeIdentityLoggedIn();

        return null;
    }

    /**
     * Removes the identity logged in.
     */
    public function removeIdentityLoggedIn()
    {
        Yii::$app->getSession()->remove($this->mfaParam);
    }

    /**
     * @var Otp|null an otp instance use to generate and validate otp.
     */
    private $_otp;

    /**
     * Get an otp instance.
     *
     * @return Otp|null an otp instance use to generate and validate otp.
     * @throws \yii\base\InvalidConfigException
     */
    public function getOtp()
    {
        if ($this->_otp === null) {
            $this->setOtp(Otp::class);
        }

        return $this->_otp;
    }

    /**
     * Set an otp instance use to generate and validate otp.
     *
     * @param array|string|Otp $otp object instance
     * @throws \yii\base\InvalidConfigException
     */
    public function setOtp($otp)
    {
        if (is_array($otp) && !isset($otp['class'])) {
            $otp['class'] = Otp::class;
        }

        $this->_otp = Instance::ensure($otp, Otp::class);
    }

    /**
     * Generate an otp by current user logged in
     *
     * @return string|null an otp of current user logged in.
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     */
    public function generateOtpByIdentityLoggedIn()
    {
        $data = $this->getIdentityLoggedIn();

        if (is_array($data)) {

            /** @var IdentityInterface $identity */
            $identity = $data[0];
            $secretKey = $identity->getMfaSecretKey();

            if (!empty($secretKey)) {
                return $this->getOtp()->generate($secretKey);
            }
        }

        return null;
    }

    /**
     * Validate an otp by current user logged in
     *
     * @param string $otp need to be validate
     * @return bool weather an otp given is valid with identity logged in
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     */
    public function validateOtpByIdentityLoggedIn(string $otp)
    {
        $data = $this->getIdentityLoggedIn();

        if (is_array($data)) {
            /** @var IdentityInterface $identity */
            $identity = $data[0];
            $secretKey = $identity->getMfaSecretKey();

            if (!empty($secretKey)) {
                return $this->getOtp()->validate($secretKey, $otp);
            }
        }

        return false;
    }

    /**
     * Return a qr code uri of current user
     *
     * @param array $params list of information use to show on an authenticator app.
     *
     * Example:
     * ```php
     * ['issuer' => 'VXM', 'label' => 'vuongxuongminh@gmail.com', 'image' => 'https://google.com']
     * ```
     *
     * @return string|null qr code uri. If `null`, it means the user is a guest or not enable mfa.
     * @throws \Throwable
     */
    public function getQrCodeUri(array $params)
    {
        if ($identity = $this->owner->getIdentity()) {
            if (!$identity instanceof IdentityInterface) {
                throw new InvalidValueException("{$this->owner->identityClass}::findIdentity() must return an object implementing \\vxm\\mfa\\IdentityInterface.");
            } else {
                $secretKey = $identity->getMfaSecretKey();

                if (!empty($secretKey)) {
                    return $this->getOtp()->getQrCodeUri($secretKey, $params);
                }
            }
        }

        return null;
    }

    /**
     * Redirects the user browser to the mfa verify page..
     *
     * Make sure you set [[verifyUrl]] so that the user browser can be redirected to the specified verify URL after
     * calling this method.
     *
     * Note that when [[verifyUrl]] is set, calling this method will NOT terminate the application execution.
     *
     * @return \yii\web\Response the redirection response if [[verifyUrl]] is set
     * @throws ForbiddenHttpException
     */
    protected function verifyRequired()
    {
        if ($this->verifyUrl !== null) {
            $verifyUrl = (array)$this->verifyUrl;

            if ($verifyUrl[0] !== Yii::$app->requestedRoute) {
                return Yii::$app->getResponse()->redirect($this->verifyUrl);
            }
        }

        throw new ForbiddenHttpException(Yii::t('app', 'Mfa verify required!'));
    }

}
