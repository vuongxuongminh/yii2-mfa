<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\mfa;

use Yii;

use yii\base\Model;

/**
 * Class OtpForm
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class OtpForm extends Model
{

    use EnsureUserBehaviorAttachedTrait;

    /**
     * @var string an otp submit from end user
     */
    public $otp;

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $this->ensureUserBehaviorAttached();

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [['otp'], 'required']
        ];
    }

    /**
     * Verify an otp is valid with current logged in user
     *
     * @return bool weather an otp property is valid.
     */
    public function verify()
    {
        if (!$this->user->validateOtpByIdentityLoggedIn($this->otp)) {
            $msg = Yii::t('app', '{attribute} is invalid!', [
                'attribute' => $this->getAttributeLabel('otp'),
            ]);
            $this->addError('otp', $msg);
            return false;
        }
        return true;
    }

    public function attributeLabels(): array
    {
        return [
            'otp' => Yii::t('app', 'OTP'),
        ];
    }
}
