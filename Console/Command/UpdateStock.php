<?php
/*
 * *
 *  * Copyright © Vaimo Group. All rights reserved.
 *  * See LICENSE_VAIMO.txt for license details.
 *
 */

namespace Gundo\StockX\Console\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStock extends Command
{
    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('UpdateStock');
        $this->setDescription('Update Stock By Running Commands');
        parent::configure();
    }

    /**
     * CLI command description.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Exception
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // todo: implement CLI command logic here
        //Get SKU, LOOK Up and Update Stock from StockX API
        //API CALL
        $newConnection = new Client();
        $response = $newConnection->request('GET', 'https://stockx.com/api/browse?productCategory=sneakers&sort=release_date&order=DESC&country=US&currency=USD&limit=100&page=1&query=nike%20air%20force%201%20low');
        $responseBody = $response->getBody();
        $output->writeln('StockX API Called');
        //Decode JSON
        $output->writeln('Decoding JSON');
        $responseArray = json_decode($responseBody, true);
        $sneakers = $responseArray['Products'];
        foreach ($sneakers as $sneaker) {
            $sku = $sneaker['styleId'];
            $stock = $sneaker['market']['numberOfAsks'];
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->create('Magento\Catalog\Model\Product')->loadByAttribute('sku', $sku);
            $productId = $product->getId();
            $stockItem = $objectManager->get('Magento\CatalogInventory\Api\Data\StockItemInterface')->load($productId, 'product_id');
            $stockItem->setQty($stock);
            $stockItem->setIsInStock((bool)$stock);
            $stockItem->save();
            if ($stockItem->save()) {
                $output->writeln('Stock Updated for ' . $sku);
            }
        }
        //Output to CLI
        $output->writeln('Stock Updated');
        //Total stock updated
        $output->writeln('Total Stock Updated: ' . count($sneakers));

        return 0;

    }
}
