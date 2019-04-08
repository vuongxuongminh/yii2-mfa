<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\mfa;

use Yii;

use yii\base\Action;
use yii\base\InvalidConfigException;

/**
 * Class VerifyAction
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class VerifyAction extends Action
{

    use EnsureUserBehaviorAttachedTrait;

    /**
     * @var string the name of view file if not set an id of this action will be use.
     */
    public $viewFile;

    /**
     * @var string the name of variable in view refer to an object of `vxm\mfa\OtpForm`.
     */
    public $formVar = 'mfaForm';

    /**
     * @var callable|null when an identity had been verified it will be call. If not set, [[\yii\web\Controller::goBack()]] will be call.
     * This action will be parse at first param and `vxm\mfa\OtpForm` is second param
     * Example:
     *
     * ```php
     * 'successCallback' => function(\vxm\mfa\VerifyAction $action, \vxm\mfa\OtpForm $otp) {
     *
     *      return $action->controller->redirect(['site/dash-board']);
     * }
     *
     * ```
     */

    public $successCallback;

    /**
     * @var callable|null when an user submit wrong otp it will be call, if not set, [[yii\web\User::loginRequired()]] will be call.
     * This action will be parse at first param and `vxm\mfa\OtpForm` is second param
     * Example:
     *
     * ```php
     * 'invalidCallback' => function(\vxm\mfa\VerifyAction $action, \vxm\mfa\OtpForm $otp) {
     *      Yii::$app->session->setFlash('Otp is not valid');
     *
     *      return $action->controller->redirect(['site/login']);
     * }
     *
     * ```
     */
    public $invalidCallback;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        $this->ensureUserBehaviorAttached();
        $this->viewFile = $this->viewFile ?? $this->id;

        parent::init();
    }


    /**
     * @inheritDoc
     */
    public function beforeRun()
    {
        $data = $this->user->getIdentityLoggedIn();

        if ($data === null) {
            $this->user->loginRequired();

            return false;
        }

        return parent::beforeRun();
    }

    /**
     * @return mixed|string|\yii\web\Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function run()
    {
        $form = new OtpForm(['user' => $this->user]);

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            if ($form->verify()) {
                $this->user->switchIdentityLoggedIn();

                if ($this->successCallback) {
                    return call_user_func($this->successCallback, $this, $form);
                } else {
                    return $this->controller->goBack();
                }
            } else {
                $this->user->removeIdentityLoggedIn();

                if ($this->invalidCallback) {
                    return call_user_func($this->invalidCallback, $this, $form);
                } else {
                    return $this->user->loginRequired();
                }
            }
        }

        return $this->controller->render($this->viewFile, [$this->formVar => $form]);
    }


}
