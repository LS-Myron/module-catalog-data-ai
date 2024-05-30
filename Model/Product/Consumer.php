<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Model\Product;

use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\StoreManagerInterface;

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
        private readonly Enricher          $enricher,
        private readonly ProductRepository $productRepository,
        private readonly StoreManagerInterface $storeManager
    ) {}

    public function execute(Request $request): void
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
