<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Model\Product;

use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\CatalogDataAI\Model\Product\Request;
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

    public function execute(Request $request)
    {
        // @TODO: enrich for all stores if different value or language
        $storeId = $request->getStoreId() ?? 0;
        $this->storeManager->setCurrentStore($storeId);
        $product = $this->productRepository->getById($request->getId(), false, $storeId);
        $product->setData('mageos_catalogai_overwrite', $request->getOverwrite());
        $this->enricher->execute($product, $storeId);
        $this->productRepository->save($product);
    }
}
