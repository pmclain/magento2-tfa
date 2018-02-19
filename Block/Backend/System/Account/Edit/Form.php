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

namespace Pmclain\Tfa\Block\Backend\System\Account\Edit;

use PragmaRX\Google2FA\Google2FA;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\OptionInterface;

class Form extends \Magento\Backend\Block\System\Account\Edit\Form
{
    /** @var \PragmaRX\Google2FA\Google2FA */
    protected $google2fa;

    /** @var OptionInterface */
    private $deployedLocales;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Locale\ListsInterface $localeLists,
        Google2FA $google2FA,
        array $data = [],
        OptionInterface $deployedLocales = null
    ) {
        $this->google2fa = $google2FA;
        $this->deployedLocales = $deployedLocales
            ?: ObjectManager::getInstance()->get(OptionInterface::class);
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $userFactory,
            $authSession,
            $localeLists,
            $data
        );
    }

    protected function _prepareForm()
    {
        $userId = $this->_authSession->getUser()->getId();
        $user = $this->_userFactory->create()->load($userId);
        $user->unsetData('password');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Account Information')]);

        $fieldset->addField(
            'username',
            'text',
            ['name' => 'username', 'label' => __('User Name'), 'title' => __('User Name'), 'required' => true]
        );

        $fieldset->addField(
            'firstname',
            'text',
            ['name' => 'firstname', 'label' => __('First Name'), 'title' => __('First Name'), 'required' => true]
        );

        $fieldset->addField(
            'lastname',
            'text',
            ['name' => 'lastname', 'label' => __('Last Name'), 'title' => __('Last Name'), 'required' => true]
        );

        $fieldset->addField('user_id', 'hidden', ['name' => 'user_id']);

        $fieldset->addField(
            'email',
            'text',
            ['name' => 'email', 'label' => __('Email'), 'title' => __('User Email'), 'required' => true]
        );

        $fieldset->addField(
            'password',
            'password',
            [
                'name' => 'password',
                'label' => __('New Password'),
                'title' => __('New Password'),
                'class' => 'validate-admin-password admin__control-text'
            ]
        );

        $fieldset->addField(
            'confirmation',
            'password',
            [
                'name' => 'password_confirmation',
                'label' => __('Password Confirmation'),
                'class' => 'validate-cpassword admin__control-text'
            ]
        );

        $fieldset->addField(
            'interface_locale',
            'select',
            [
                'name' => 'interface_locale',
                'label' => __('Interface Locale'),
                'title' => __('Interface Locale'),
                'values' => $this->deployedLocales->getTranslatedOptionLocales(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'require_tfa',
            'select',
            [
                'name' => 'require_tfa',
                'label' => __('Enable Two Factor Authentication'),
                'title' => __('Enable Two Factor Authentication'),
                'values' => [
                    ['label' => __('Yes'), 'value' => 1],
                    ['label' => __('No'), 'value' => 0]
                ],
                'class' => 'select',
                'note' => $this->getTfaAfterHtml($user),
            ]
        );

        $verificationFieldset = $form->addFieldset(
            'current_user_verification_fieldset',
            ['legend' => __('Current User Identity Verification')]
        );
        $verificationFieldset->addField(
            self::IDENTITY_VERIFICATION_PASSWORD_FIELD,
            'password',
            [
                'name' => self::IDENTITY_VERIFICATION_PASSWORD_FIELD,
                'label' => __('Your Password'),
                'id' => self::IDENTITY_VERIFICATION_PASSWORD_FIELD,
                'title' => __('Your Password'),
                'class' => 'validate-current-password required-entry admin__control-text',
                'required' => true
            ]
        );

        $data = $user->getData();
        unset($data[self::IDENTITY_VERIFICATION_PASSWORD_FIELD]);
        $form->setValues($data);
        $form->setAction($this->getUrl('adminhtml/system_account/save'));
        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setId('edit_form');

        $this->setForm($form);

        return $this;
    }

    protected function getTfaAfterHtml(\Magento\User\Model\User $user)
    {
        if (is_null($user->getTfaSecret())) {
            return '<p>' . __('The QR code for Google Authenticator will appear after saving.') . '</p>';
        }
        $qrImage = $this->google2fa->getQRCodeInline(
            $this->getBaseUrl(),
            $user->getEmail(),
            $user->getTfaSecret()
        );
        $img = "<img id=\"qr-img\" src=\"$qrImage\" />";
        $message = __('Scan the QR code below with Google Authenticator.');
        $updateButton = $this->getUpdateQrButton();

        return "<p>$message</p>$img<p>$updateButton</p>";
    }

    protected function getUpdateQrButton()
    {
        $button = "<button class=\"scalable\" type=\"button\" id=\"update-qr\" data-mage-init='{\"Pmclain_Tfa/js/tfa\":{";
        $button .= "\"url\": \"{$this->getAjaxUrl()}\",";
        $button .= "\"elementId\": \"update-qr\"}, \"validation\": {}}'>";
        $button .= "<span><span><span id=\"update-qr\">{$this->getUpdateButtonLabel()}</span></span></span>";
        $button .= "</button>";

        return $button;
    }

    protected function getAjaxUrl()
    {
        return $this->_urlBuilder->getUrl('admin/system_account_tfa/update');
    }

    protected function getUpdateButtonLabel()
    {
        return __('Update QR');
    }
}
