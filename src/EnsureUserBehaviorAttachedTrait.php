<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\mfa;

use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\web\User;

/**
 * EnsureUserBehaviorAttachedTrait to ensure an user object had attached `\vxm\mfa\Behavior`
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
trait EnsureUserBehaviorAttachedTrait
{

    /**
     * @var array|string|User an user component id or an user instance need to verify otp of identity.
     */
    public $user = 'user';

    /**
     * Ensure user object had been attach `vxm\mfa\Behavior`
     *
     * @throws InvalidConfigException
     */
    protected function ensureUserBehaviorAttached()
    {
        $this->user = Instance::ensure($this->user, 'yii\web\User');

        foreach ($this->user->getBehaviors() as $behavior) {
            if ($behavior instanceof Behavior) {
                return;
            }
        }

        throw new InvalidConfigException('An user instance must be attach `\vxm\mfa\Behavior`');
    }

}
