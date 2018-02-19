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

namespace Pmclain\Tfa\Controller\Adminhtml\System\Account\Tfa;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;
use PragmaRX\Google2FA\Google2FA;

class Update extends Action
{
    /** @var JsonFactory */
    protected $jsonFactory;

    /** @var Session */
    protected $authSession;

    /** @var \PragmaRX\Google2FA\Google2FA; */
    protected $google2fa;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Session $session,
        Google2FA $google2FA
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->authSession = $session;
        $this->google2fa = $google2FA;
    }

    public function execute()
    {
        $user = $this->authSession->getUser();
        $currentUserPasswordField = \Magento\User\Block\User\Edit\Tab\Main::CURRENT_USER_PASSWORD_FIELD;
        $currentUserPassword = $this->getRequest()->getParam($currentUserPasswordField);

        try {
            $user->performIdentityCheck($currentUserPassword);

            $newSecret = $this->google2fa->generateSecretKey(32);
            $user->setTfaSecret($newSecret);
            $user->save();

            $qrImage = $this->google2fa->getQRCodeInline(
                urlencode('Magento 2 Admin'),
                $user->getEmail(),
                $newSecret
            );

            $result = [
                'success' => true,
                'message' => __('QR has been updated. Scan with Google Authenticator before leaving this page.'),
                'qr' => $qrImage
            ];
        } catch (UserLockedException $e) {
            $this->_auth->logout();
            $result = ['errorMessage' => $e->getMessage()];
        } catch (ValidatorException $e) {
            $result = ['errorMessage' => $e->getMessage()];
        } catch (LocalizedException $e) {
            $result = ['errorMessage' => $e->getMessage()];
        } catch (\Exception $e) {
            $result = ['errorMessage' => 'An error occurred while saving account.'];
        }

        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData($result);
    }
}
