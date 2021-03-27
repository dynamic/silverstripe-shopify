<?php

namespace Dynamic\Shopify\Page;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\PaginatedList;

class ShopifyCollectionController extends \PageController
{
    /**
     * @param HTTPRequest|null $request
     * @return PaginatedList
     */
    public function ProductPaginatedList(HTTPRequest $request = null)
    {
        if (!$request instanceof HTTPRequest) {
            $request = $this->getRequest();
        }
        $products = $this->data()->getProductList();
        $start = ($request->getVar('start')) ? (int)$request->getVar('start') : 0;
        $records = PaginatedList::create($products, $request);
        $records->setPageStart($start);
        $records->setPageLength($this->data()->ProductsPerPage);

        // allow $records to be updated via extension
        $this->extend('updateProductPaginatedList', $records);

        return $records;
    }
}
