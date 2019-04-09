<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\tests\unit\mfa;

use Yii;

use vxm\mfa\IdentityInterface;

/**
 * Class BehaviorTest
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class BehaviorTest extends TestCase
{

    public function testLoggedIn()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);
        $data = Yii::$app->user->getIdentityLoggedIn();

        $this->assertNotEmpty($data);
        $this->assertTrue($data[0] instanceof IdentityInterface);
        $this->assertEquals(0, $data[1]);
        $this->assertNotNull(Yii::$app->response->headers->get('location')); // verify required
    }

    /**
     * @depends testLoggedIn
     */
    public function testSwitchIdentityLoggedIn()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);
        Yii::$app->user->switchIdentityLoggedIn();

        $this->assertFalse(Yii::$app->user->getIsGuest());
    }

    /**
     * @depends testSwitchIdentityLoggedIn
     */
    public function testQRCodeUri()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);
        Yii::$app->user->switchIdentityLoggedIn();

        $this->assertNotEmpty(Yii::$app->user->getQRCodeUri([
            'label' => 'vxm'
        ]));
    }

    /**
     * @depends testLoggedIn
     */
    public function testGenerateOtpByIdentityLoggedIn()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);

        $otp = Yii::$app->user->generateOtpByIdentityLoggedIn();
        $this->assertNotEmpty($otp);
        $this->assertTrue(strlen($otp) === Yii::$app->user->otp->digits);
    }

    /**
     * @depends testLoggedIn
     */
    public function testValidateOtpByIdentityLoggedIn()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);

        $otp = Yii::$app->user->generateOtpByIdentityLoggedIn();
        $this->assertTrue(Yii::$app->user->validateOtpByIdentityLoggedIn($otp));
        $this->assertFalse(Yii::$app->user->validateOtpByIdentityLoggedIn('abcd'));
    }

    /**
     * @depends testGenerateOtpByIdentityLoggedIn
     * @depends testValidateOtpByIdentityLoggedIn
     */
    public function testSetOtp()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);
        Yii::$app->user->setOtp([
            'digits' => 8
        ]);
        $otp = Yii::$app->user->generateOtpByIdentityLoggedIn();
        $this->assertTrue(strlen($otp) === 8);
    }
}
