<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Observer\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MageOS\CatalogDataAI\Model\Config;
use MageOS\CatalogDataAI\Model\Product\Enricher;

class SaveBefore implements ObserverInterface
{
    public function __construct(
        private readonly Config           $config,
        private readonly Enricher         $enricher,
        private readonly RequestInterface $request,
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var Product $product */
        $product = $observer->getProduct();
        $storeId = (int)$this->request->getParam('store');

        if($this->config->canEnrich($product) && !$this->config->isAsync()) {
            $this->enricher->execute($product, $storeId);
        }
    }
}
