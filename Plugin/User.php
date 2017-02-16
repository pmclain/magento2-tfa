<?php
/**
 * Pmclain_Tfa extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GPL v3 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl.txt
 *
 * @category  Pmclain
 * @package   Pmclain_Tfa
 * @copyright Copyright (c) 2017
 * @license   https://www.gnu.org/licenses/gpl.txt GPL v3 License
 */

namespace Pmclain\Tfa\Plugin;

use Magento\Framework\App\Request\Http;
use PragmaRX\Google2FA\Google2FA;

class User
{
  /** @var \Magento\Framework\App\Request\Http */
  protected $_request;

  /** @var \PragmaRX\Google2FA\Google2FA; */
  protected $_google2fa;

  public function __construct(
    Http $request,
    Google2FA $google2FA
  ) {
    $this->_request = $request;
    $this->_google2fa = $google2FA;
  }

  public function beforeVerifyIdentity(
    \Magento\User\Model\User $user,
    $password
  ) {
    if(!$user->getRequireTfa()) { return [$password]; }

    $authCode = $this->_request->getPost('tfa');
    $valid = $this->_google2fa->verifyKey($user->getTfaSecret(), $authCode);

    if($valid) { return [$password]; }

    return [false];
  }

  public function beforeSave(
    \Magento\User\Model\User $user
  ) {
    $user->setRequireTfa($this->_request->getParam('require_tfa'));
    if(is_null($user->getTfaSecret())) {
      $user->setTfaSecret($this->_google2fa->generateSecretKey(32));
    }

    return;
  }
}