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

namespace Pmclain\Tfa\Test\Unit\Plugin;

use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Pmclain\Tfa\Plugin\User;
use Magento\User\Model\User as AdminUser;
use Magento\Framework\App\Request\Http;
use Magento\Backend\Model\Auth\Session;
use PragmaRX\Google2FA\Google2FA;

class UserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var User
     */
    private $userPlugin;

    /**
     * @var AdminUser|MockObject
     */
    private $userMock;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var Google2FA|MockObject
     */
    private $google2faMock;

    /**
     * @var Session|MockObject
     */
    private $authSessionMock;

    protected function setUp()
    {
        $this->userMock = $this->getMockBuilder(AdminUser::class)
            ->setMethods(['getRequireTfa', 'getTfaSecret'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->createMock(Http::class);
        $this->google2faMock = $this->createMock(Google2FA::class);
        $this->authSessionMock = $this->createMock(Session::class);

        $this->userPlugin = new User(
            $this->requestMock,
            $this->google2faMock,
            $this->authSessionMock
        );
    }

    public function testBeforeVerifyIdentity()
    {
        $password = 'abc123';

        $this->userMock->method('getRequireTfa')->willReturn(true);
        $this->authSessionMock->method('isLoggedIn')->willReturn(false);
        $this->requestMock->method('getPost')
            ->with('tfa')
            ->willReturn('999999');
        $this->google2faMock->method('verifyKey')->willReturn(true);

        $this->assertEquals(
            [$password],
            $this->userPlugin->beforeVerifyIdentity(
                $this->userMock,
                $password
            )
        );
    }

    public function testBeforeVerifyIdentityUserLoggedIn()
    {
        $password = 'abc123';

        $this->authSessionMock->method('isLoggedIn')->willReturn(true);

        $this->assertEquals(
            [$password],
            $this->userPlugin->beforeVerifyIdentity(
                $this->userMock,
                $password
            )
        );
    }

    public function testBeforeVerifyIdentityTfaDisabled()
    {
        $password = 'abc123';

        $this->userMock->method('getRequireTfa')->willReturn(false);

        $this->assertEquals(
            [$password],
            $this->userPlugin->beforeVerifyIdentity(
                $this->userMock,
                $password
            )
        );
    }

    public function testBeforeVerifyIdentityTfaInvalid()
    {
        $this->userMock->method('getRequireTfa')->willReturn(true);
        $this->authSessionMock->method('isLoggedIn')->willReturn(false);
        $this->requestMock->method('getPost')
            ->with('tfa')
            ->willReturn('999999');
        $this->google2faMock->method('verifyKey')->willReturn(false);

        $this->assertEquals(
            [false],
            $this->userPlugin->beforeVerifyIdentity(
                $this->userMock,
                'abc123'
            )
        );
    }
}
