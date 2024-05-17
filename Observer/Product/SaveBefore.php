<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Observer\Product;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MageOS\CatalogDataAI\Model\Config;
use MageOS\CatalogDataAI\Model\Product\Enricher;

class SaveBefore implements ObserverInterface
{
    public function __construct(
        private Config $config,
        private Enricher $enricher,
        private RequestInterface $request,
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getProduct();
        $storeId = (int)$this->request->getParam('store');

        if($this->config->canEnrich($product) && !$this->config->isAsync()) {
            $this->enricher->execute($product, $storeId);
        }
    }
}
