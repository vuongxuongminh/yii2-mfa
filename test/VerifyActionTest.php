<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\test\unit\mfa;

use Yii;

use yii\web\Controller;

use vxm\mfa\VerifyAction;

/**
 * Class VerifyActionTest
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class VerifyActionTest extends TestCase
{

    public function testBeforeAction()
    {
        $controller = $this->mockController();
        $controller->runAction('verify');
        $this->assertNotNull(Yii::$app->response->headers->get('location')); // login required
    }

    /**
     * @depends testBeforeAction
     */
    public function testRenderView()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);
        $controller = $this->mockController();
        $result = $controller->runAction('verify');
        $this->assertEquals('hello world!', trim($result));
    }

    /**
     * @depends testBeforeAction
     */
    public function testInValidOtp()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);
        $controller = $this->mockController();
        $_POST['_method'] = 'POST';
        Yii::$app->request->setBodyParams([
            Yii::$app->request->csrfParam => Yii::$app->request->csrfToken,
            'OtpForm' => [
                'otp' => 'abcd',
            ]
        ]);
        $controller->runAction('verify');
        $this->assertTrue(Yii::$app->user->getIsGuest());

        // test retry
        /** @var VerifyAction $action */
        $action = $controller->createAction('verify');
        $action->retry = false;
        $action->runWithParams([]);
        $data = Yii::$app->user->getIdentityLoggedIn();
        $this->assertNull($data);
    }

    /**
     * @depends testBeforeAction
     */
    public function testValidOtp()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);
        $controller = $this->mockController();
        $_POST['_method'] = 'POST';
        Yii::$app->request->setBodyParams([
            Yii::$app->request->csrfParam => Yii::$app->request->csrfToken,
            'OtpForm' => [
                'otp' => Yii::$app->user->generateOtpByIdentityLoggedIn(),
            ]
        ]);
        $controller->runAction('verify');
        $this->assertFalse(Yii::$app->user->getIsGuest());
    }

    protected function mockController()
    {
        return new class('test', Yii::$app) extends Controller
        {
            public $layout = false;

            public function actions()
            {
                return [
                    'verify' => [
                        'class' => 'vxm\mfa\VerifyAction',
                        'viewFile' => '@vxm/test/unit/mfa/verify-action-view.php',
                        'retry' => true
                    ]
                ];
            }
        };
    }
}
