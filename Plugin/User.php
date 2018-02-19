<?php
/**
 * Pmclain_Tfa extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the OSL 3.0 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  Pmclain
 * @package   Pmclain_Tfa
 * @copyright Copyright (c) 2017-2018
 * @license   Open Software License (OSL 3.0)
 */

namespace Pmclain\Tfa\Plugin;

use Magento\Framework\App\Request\Http;
use PragmaRX\Google2FA\Google2FA;
use Magento\Backend\Model\Auth\Session;

class User
{
    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /** @var \PragmaRX\Google2FA\Google2FA; */
    protected $google2fa;

    /** @var \Magento\Backend\Model\Auth\Session */
    protected $authSession;

    public function __construct(
        Http $request,
        Google2FA $google2FA,
        Session $session
    ) {
        $this->request = $request;
        $this->google2fa = $google2FA;
        $this->authSession = $session;
    }

    public function beforeVerifyIdentity(
        \Magento\User\Model\User $user,
        $password
    ) {
        if (!$user->getRequireTfa() || $this->authSession->isLoggedIn()) {
            return [$password];
        }

        $authCode = $this->request->getPost('tfa');
        $valid = $this->google2fa->verifyKey($user->getTfaSecret(), $authCode);

        if ($valid) {
            return [$password];
        }

        return [false];
    }

    public function beforeSave(
        \Magento\User\Model\User $user
    ) {
        $user->setRequireTfa($this->request->getParam('require_tfa'));
        if (is_null($user->getTfaSecret())) {
            $user->setTfaSecret($this->google2fa->generateSecretKey(32));
        }

        return;
    }
}
