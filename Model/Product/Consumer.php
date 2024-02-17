<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Model\Product;

use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\CatalogDataAI\Model\Product\Enricher;

/**
 * Class Consumer
 * @package Gaiterjones\RabbitMQ\MessageQueues\Product
 */
class Consumer
{
    /**
     * Consumer constructor.
     */
    public function __construct(
        private Enricher $enricher,
        private ProductRepository $productRepository,
        private StoreManagerInterface $storeManager
    ) {}

    public function execute($id)
    {
        // @TODO: enrich for all stores if different value or language
        $this->storeManager->setCurrentStore(0);
        $product = $this->productRepository->getById($id);
        $this->enricher->execute($product);
        $this->productRepository->save($product);
    }

}
