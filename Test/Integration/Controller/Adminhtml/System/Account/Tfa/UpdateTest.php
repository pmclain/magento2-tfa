<?php

namespace Pmclain\Tfa\Controller\Adminhtml\System\Account\Tfa;

use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\TestFramework\Bootstrap;
use Magento\User\Block\User\Edit\Tab\Main;
use \Magento\Framework\Serialize\Serializer\Json;

class UpdateTest extends AbstractBackendController
{
    /**
     * @var string
     */
    protected $uri = 'backend/admin/system_account_tfa/update';

    /**
     * @var Json
     */
    private $serializer;

    protected function setUp()
    {
        parent::setUp();
        $this->serializer = $this->_objectManager->create(Json::class);
    }

    protected function tearDown()
    {
        $user = $this->_session->getUser();
        $user->setData('tfa_secret', null);
        $user->save();

        parent::tearDown();
    }

    public function testExecute()
    {
        $this->getRequest()->setParams([
            Main::CURRENT_USER_PASSWORD_FIELD => Bootstrap::ADMIN_PASSWORD
        ]);

        $this->dispatch($this->uri);
        $result = $this->serializer->unserialize($this->getResponse()->getBody());

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('qr', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals(
            $result['message'],
            'QR has been updated. Scan with Google Authenticator before leaving this page.'
        );
    }
}
