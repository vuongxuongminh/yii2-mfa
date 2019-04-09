<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\tests\unit\mfa;

use Yii;

use vxm\mfa\QrCodeWidget;

/**
 * Class QrCodeWidgetTest
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class QrCodeWidgetTest extends TestCase
{

    public function testInvalidCall()
    {
        try {
            QrCodeWidget::widget();
        } catch (\Throwable $throwable) {
            $this->assertTrue($throwable instanceof \yii\base\InvalidCallException);
        }
    }

    /**
     * @depends testInvalidCall
     */
    public function testMissingLabel()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);
        Yii::$app->user->switchIdentityLoggedIn();

        try {
            QrCodeWidget::widget();
        } catch (\Throwable $throwable) {
            $this->assertTrue($throwable instanceof \Assert\InvalidArgumentException);
        }
    }

    /**
     * @depends testMissingLabel
     */
    public function testValid()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);
        Yii::$app->user->switchIdentityLoggedIn();

        $result = QrCodeWidget::widget([
            'label' => 'vxm',
            'image' => 'https://abc.com',
            'issuer' => 'Test'
        ]);
        $this->assertNotEmpty($result);
    }

}
