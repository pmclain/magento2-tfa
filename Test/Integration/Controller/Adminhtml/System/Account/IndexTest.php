<?php

namespace Pmclain\Tfa\Test\Integration\Controller\Adminhtml\System\Account;

use Magento\TestFramework\TestCase\AbstractBackendController;

class IndexTest extends AbstractBackendController
{
    /**
     * @var string
     */
    protected $uri = 'backend/admin/system_account/index';

    protected function tearDown()
    {
        $user = $this->_session->getUser();
        $user->setData('tfa_secret', null);
        $user->save();

        parent::tearDown();
    }

    /**
     * Confirm TFA form fields with TFA disabled and no TFA secret key saved
     */
    public function testIndexAction()
    {
        $this->dispatch($this->uri);
        $result = $this->getResponse()->getBody();
        $this->assertContains('Enable Two Factor Authentication', $result);
        $this->assertContains('The QR code for Google Authenticator will appear after saving.', $result);
    }

    /**
     * Confirm QR code is visible when TFA secret is set
     */
    public function testIndexActionWithQrCode()
    {
        $user = $this->_session->getUser();
        $user->setData('tfa_secret', 'XHUWPTEYX3WTDPB5KFBHPL6L3HGMHMJF');
        $user->save();

        $this->dispatch($this->uri);
        $result = $this->getResponse()->getBody();
        $this->assertContains('Enable Two Factor Authentication', $result);
        $this->assertContains('Scan the QR code below with Google Authenticator.', $result);
    }
}
