<?php

namespace Dynamic\Shopify\SEO;

use Broarm\Schema\Builder\SchemaBuilder;
use Broarm\Schema\Type\OfferSchema;
use Broarm\Schema\Type\ProductSchema;
use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Page\ShopifyProduct;

/**
 * Class ProductSchemaBuilder
 * @package Dynamic\Shopify\SEO
 */
class ProductSchemaBuilder extends SchemaBuilder
{

    /**
     * @inheritDoc
     */
    public function getSchema($page)
    {
        /** @var ShopifyProduct $page */
        $images = [];
        foreach ($page->Files() as $file) {
            if ($file->Type === ShopifyFile::IMAGE) {
                $images[] = $file->getURL();
            }
        }

        return new ProductSchema(
            $page->Title,
            $page->Content,
            new OfferSchema(
                number_format($page->getPrice()->getValue(), 2),
                'USD',
                $page->ProductActive ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
            ),
            $page->getSKU(),
            null,
            null,
            $images
        );
    }
}
