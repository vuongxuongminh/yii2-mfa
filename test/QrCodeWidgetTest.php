<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\test\unit\mfa;

use Yii;

use PHPUnit\Framework\Attributes\Depends;

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
        $this->expectException(\yii\base\InvalidCallException::class);

        QrCodeWidget::widget();
    }

    #[Depends('testInvalidCall')]
    public function testMissingLabel()
    {
        $identity = Identity::findIdentity('user1');
        Yii::$app->user->login($identity);
        Yii::$app->user->switchIdentityLoggedIn();

        $this->expectException(\OTPHP\Exception\InvalidLabelException::class);

        QrCodeWidget::widget();
    }

    #[Depends('testMissingLabel')]
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
