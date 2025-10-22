<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\ThemeLayoutPro\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegenerateProductUrlCommand extends AbstractRegenerateCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('codazon:gproduct:url')
            ->setDescription('Regenerate url for given products')
            ->addArgument(
                'pids',
                InputArgument::IS_ARRAY,
                'Product IDs to regenerate'
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        try {
            $this->state->getAreaCode();
        } catch (LocalizedException $e) {
            $this->state->setAreaCode('adminhtml');
        }

        $this->regenerateProductUrl->setOutput($output);

        $stores = $this->getChosenStores();

        foreach ($stores as $storeId) {
            $this->regenerateProductUrl->execute($input->getArgument('pids'), (int)$storeId, $output->isVerbose());
        }

        return Cli::RETURN_SUCCESS;
    }
}