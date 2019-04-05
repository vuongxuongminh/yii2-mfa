<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\mfa;

use Yii;

use yii\base\Behavior;
use yii\base\InvalidValueException;
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
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class MfaBehavior extends Behavior
{

    /**
     * @var User
     */
    public $owner;

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
    public function events()
    {
        return [
            User::EVENT_BEFORE_LOGIN => 'handle'
        ];
    }

    /**
     * @param UserEvent $event
     * @throws ForbiddenHttpException
     */
    public function handle(UserEvent $event)
    {
        if (!$event->isValid) {
            return;
        }

        if (!$event->identity instanceof IdentityInterface) {
            throw new InvalidValueException("An identity logged in must be implemented `" . IdentityInterface::class . "` ");
        }

        $secretKey = $event->identity->getMfaSecretKey();

        if (!empty($secretKey) && $this->owner->enableSession && !$event->cookieBased) {
            $event->isValid = false;
            $this->verifyRequired();
        }
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
