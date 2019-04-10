<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\mfa;

use yii\web\IdentityInterface as BaseIdentityInterface;

/**
 * IdentityInterface is the interface that should be implemented by a class providing identity support mfa.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
interface IdentityInterface extends BaseIdentityInterface
{
    /**
     * Returns an mfa secret key that will be use to generate and validate otp.
     *
     * @return string|null
     */
    public function getMfaSecretKey();

}
