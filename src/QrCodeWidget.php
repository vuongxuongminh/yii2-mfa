<?php
/**
 * @link https://github.com/vuongxuongminh/yii2-mfa
 * @copyright Copyright (c) 2019 Vuong Xuong Minh
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace vxm\mfa;

use yii\base\InvalidCallException;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Class QrCodeWidget provide a qr code for authenticator like google authenticator of current logged in user.
 *
 * @author Vuong Minh <vuongxuongminh@gmail.com>
 * @since 1.0.0
 */
class QrCodeWidget extends Widget
{

    use EnsureUserBehaviorAttachedTrait;

    /**
     * @var array HTML img tag attributes.
     */
    public $options = [];

    /**
     * @var string an issuer will show in authenticator application. If not set an application name will be use to set by default.
     */
    public $issuer;

    /**
     * @var string a label will show in authenticator application.
     */
    public $label;

    /**
     * @var string a image will show in authenticator application.
     */
    public $image;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->ensureUserBehaviorAttached();

        parent::init();
    }

    /**
     * @inheritDoc
     * @throws InvalidCallException
     */
    public function run()
    {
        $params = [];

        if ($this->issuer) {
            $params['issuer'] = $this->issuer;
        }

        if ($this->label) {
            $params['label'] = $this->issuer;
        }

        if ($this->image) {
            $params['image'] = $this->image;
        }

        $uri = $this->user->getQrCodeUri($params);

        if ($uri) {
            return Html::img($uri, $this->options);
        } else {
            throw new InvalidCallException('Current user not enabled MFA or is guest!');
        }
    }

}
