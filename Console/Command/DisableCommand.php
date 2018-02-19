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

namespace Pmclain\Tfa\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory;
use Magento\User\Model\ResourceModel\User\Collection;

class DisableCommand extends Command
{
    const ADMIN_EMAIL = 'email';

    /** @var CollectionFactory */
    private $userCollectionFactory;

    /** @var OutputInterface */
    private $output;

    public function __construct(
        CollectionFactory $userCollectionFactory
    ) {
        parent::__construct();
        $this->userCollectionFactory = $userCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pmclain:tfa:disable')
            ->setDescription('Disable Two Factor Authentication for admin users.')
            ->setDefinition([
                new InputArgument(
                    self::ADMIN_EMAIL,
                    InputArgument::OPTIONAL,
                    'Admin User Email'
                )
            ]);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $email = $input->getArgument(self::ADMIN_EMAIL);

        if (is_null($email)) {
            $this->disableAll();
        } else {
            $this->disableSingle($email);
        }
    }

    private function disableAll()
    {
        /** @var Collection $collection */
        $collection = $this->userCollectionFactory->create();
        foreach ($collection->getItems() as $user) {
            if ((int)$user->getRequireTfa() === 0) {
                $this->output->writeln('<info>TFA already disabled for '. $user->getEmail() . '</info>');
                continue;
            }

            $user->setRequireTfa(0);

            try {
                $user->getResource()->save($user);
                $this->output->writeln('<info>Disabled TFA for '. $user->getEmail() . '</info>');
            } catch (\Exception $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
    }

    private function disableSingle($email)
    {
        /** @var Collection $collection */
        $collection = $this->userCollectionFactory->create();
        $collection->addFieldToFilter('email', $email);

        $user = $collection->getFirstItem();
        if (!$user || !$user->getId()) {
            $this->output->writeln('<error>User ' . $email . ' was not found.</error>');
            return;
        }

        if ((int)$user->getRequireTfa() === 0) {
            $this->output->writeln('<info>TFA already disabled for '. $user->getEmail() . '</info>');
            return;
        }

        $user->setRequireTfa(0);

        try {
            $user->getResource()->save($user);
            $this->output->writeln('<info>Disabled TFA for '. $user->getEmail() . '</info>');
        } catch (\Exception $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
