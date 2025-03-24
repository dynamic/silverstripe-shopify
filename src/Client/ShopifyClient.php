<?php

namespace Dynamic\Shopify\Client;

use Dynamic\Shopify\Extension\ShopifySiteConfigExtension;
use Exception;
use GuzzleHttp\Promise\Promise;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\ArrayList;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\ArrayData;

/**
 * Class ShopifyClient
 * @package Dynamic\Shopify\Client
 *
 * @mixin BasicShopifyAPI
 */
class ShopifyClient
{
    use Configurable;
    use Injectable;

    const EXCEPTION_NO_API_KEY = 0;
    const EXCEPTION_NO_API_PASSWORD = 1;
    const EXCEPTION_NO_DOMAIN = 2;

    /**
     * Configures the version of the api that you want to use
     *
     * @config string
     */
    private static $api_version = '2025-01';

    /**
     * @config null|string
     */
    private static $api_key = null;

    /**
     * @config null|string
     */
    private static $api_password = null;

    /**
     * @config null|string
     */
    private static $storefront_access_token = null;

    /**
     * @config null|string
     */
    private static $shopify_domain = null;

    /**
     * @config null|string
     */
    private static $custom_domain = null;

    /**
     * @config null|string
     */
    private static $shared_secret = null;

    /**
     * Set this to false when creating your own custom shopify buy js
     * @config null|string
     */
    private static $inject_javascript = true;

    /**
     * @var BasicShopifyAPI
     */
    protected $client = null;

    /**
     * Get the configured Guzzle client
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setClient();
    }

    /**
     * @param string $method
     * @param array $args
     */
    public function __call(string $method, array $args)
    {
        return call_user_func_array([$this->getClient(), $method], $args);
    }

    /**
     * @return $this
     * @throws Exception
     *
     * TODO move config fetches to separate methods, supporting ENV values as well
     */
    protected function setClient()
    {
        /*if (!$key = self::config()->get('api_key')) {
            throw new Exception('No api key is set.', self::EXCEPTION_NO_API_KEY);
        }//*/

        if (!$password = self::config()->get('api_password')) {
            throw new Exception('No api password is set.', self::EXCEPTION_NO_API_PASSWORD);
        }

        if (!$domain = self::config()->get('shopify_domain')) {
            throw new Exception('No shopify domain is set.', self::EXCEPTION_NO_DOMAIN);
        }

        $options = new Options();
        $options->setVersion(Config::inst()->get(static::class, 'api_version'));
        $options->setApiPassword($password);
        $options->setType(true);

        $client = new BasicShopifyAPI($options);
        $client->setSession(new Session($domain));

        $this->client = $client;

        $this->updateLocalCache();
        return $this;
    }

    /**
     * @return BasicShopifyAPI|null
     * @throws Exception
     */
    protected function getClient()
    {
        if (!$this->client) {
            $this->setClient();
        }

        return $this->client;
    }

    /**
     * @return mixed
     */
    public static function get_domain()
    {
        if (!$domain = self::config()->get('custom_domain')) {
            $domain = self::config()->get('shopify_domain');
        }
        return $domain;
    }

    /**
     * Updates locally stored config options set in shopify
     */
    protected function updateLocalCache()
    {
        /** @var SiteConfig|ShopifySiteConfigExtension $config */
        $config = SiteConfig::current_site_config();
        $config->ShopCurrencyCode = $this->currencyCode();

        if ($config->isChanged()) {
            $config->write();
        }
    }

    /**
     * Gets the shop's currency code
     * @return array|Promise
     * @throws Exception
     */
    public function currencyCode()
    {
        $result = $this->getClient()->graph('query {shop{currencyCode}}');
        if ($result && $result['body']) {
            return $result['body']->data->shop->currencyCode;
        }
        return '';
    }

    /**
     * @param array $options
     * @return array|Promise
     * @throws Exception
     */
    public function products(int $limit = 10, string $cursor = null)
    {
        return $this->getClient()->graph(
            'query ($limit: Int!, $cursor: String) {
  products(first: $limit, after: $cursor) {
    edges {
      cursor
      node {
        id
        title
        handle
        descriptionHtml
        vendor
        productType
        createdAt
        updatedAt
        publishedOnCurrentPublication
        images(first: 10) {
          edges {
            node {
              id
              altText
              originalSrc
            }
          }
        }
        variants(first: 25) {
          edges {
            node {
              id
              title
              sku
              price
              compareAtPrice
              position
              inventoryQuantity
              image {
                id
                altText
                originalSrc
              }
            }
          }
        }
      }
    }
    pageInfo {
      hasNextPage
    }
  }
}
',
            [
                'limit' => (int)$limit,
                'cursor' => $cursor
            ]
        );
    }

    /**
     * @param $productId
     * @param array $options
     * @return array|Promise
     * @throws Exception
     */
    public function product($productId, array $options = [])
    {
        return $this->getClient()->graph(
            'query ($id: ID!){
    product(id: $id) {
        id
        title
        handle
        descriptionHtml
        vendor
        productType
        createdAt
        updatedAt
        publishedOnCurrentPublication
        images(first: 10) {
          edges {
            node {
              id
              altText
              originalSrc
            }
          }
        }
        variants(first: 25) {
          edges {
            node {
              id
              title
              sku
              price
              compareAtPrice
              position
              inventoryQuantity
              image {
                id
                altText
                originalSrc
              }
            }
          }
        }
    }
}
',
            [
                'id' => "gid://shopify/Product/{$productId}",
            ]
        );
    }

    /**
     * Get the available Collections
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function collections(int $limit = 25, string $cursor = null)
    {
        return $this->getClient()->graph('
query ($limit: Int!, $cursor: String){
    collections(first: $limit, after: $cursor) {
        edges {
            cursor
            node {
                id
                title
                handle
                descriptionHtml
                updatedAt
                sortOrder
                publishedOnCurrentPublication
                image {
                    id
                    altText
                    originalSrc
                }
            }
        }
        pageInfo {
          hasNextPage
        }
    }
}
        ', [
            'limit' => (int)$limit,
            'cursor' => $cursor,
        ]);
    }

    public function collection($collectionId)
    {
        return $this->getClient()->graph('
query ($id: ID!){
    collection(id: $id) {
        id
        title
        handle
        descriptionHtml
        productsCount
        updatedAt
        sortOrder
        publishedOnCurrentPublication
        image {
            id
            altText
            originalSrc
        }
    }
}
        ', [
            'id' => "gid://shopify/Collection/{$collectionId}",
        ]);
    }

    /**
     * @param $productID
     * @param int $limit
     * @param string|null $cursor
     * @return array|Promise
     * @throws Exception
     */
    public function productCollections($productId, int $limit = 25, string $cursor = null)
    {
        return $this->getClient()->graph(
            'query ($id: ID!, $limit: Int!, $cursor: String){
    product(id: $id) {
        collections(first: $limit, after: $cursor) {
            edges {
                cursor
                node {
                    id
                    title
                }
            }
            pageInfo {
              hasNextPage
            }
        }
    }
}
        ',
            [
                'id' => "gid://shopify/Product/{$productId}",
                "limit" => (int)$limit,
                "cursor" => $cursor,
            ]
        );
    }

    /**
     * return products of a given collection by handle
     *
     * @param $handle
     * @param array $options
     * @return array|Promise
     * @throws Exception
     */
    public function collectionProducts($handle)
    {
        return $this->getClient()->graph(
            'query ($handle: String!){
    collectionByHandle(handle: $handle) {
        products(first: 100) {
            edges {
                node {
                    id
                    title
                }
            }
        }
    }
}',
            ["handle" => $handle]
        );
    }

    /**
     * @param $productId
     * @param int $limit
     * @param string|null $cursor
     * @param bool $variant
     * @return array|Promise|ResponseAccess
     * @throws Exception
     */
    public function productMedia($productId, int $limit = 25, $cursor = null, $variant = false)
    {
        $queryType = $variant ? 'productVariant' : 'product';
        $idType = $variant ? 'ProductVariant' : 'Product';
        return $this->getClient()->graph(
            'query ($id: ID!, $limit: Int!, $cursor: String){
    ' . $queryType . '(id: $id) {
        id
        media(first: $limit, after: $cursor) {
            edges {
                cursor
                node {
                    ... fieldsForMediaTypes
                }
            }
            pageInfo {
              hasNextPage
            }
        }
    }
}

fragment fieldsForMediaTypes on Media {
    alt
    mediaContentType
    preview {
        image {
            id
            altText
            originalSrc
            width
            height
        }
    }
    status
    ... on Video {
        id
        sources {
            format
            height
            mimeType
            url
            width
        }
        originalSource {
            format
            height
            mimeType
            url
            width
        }
    }
    ... on ExternalVideo {
        id
        embeddedUrl
    }
    ... on Model3d {
        sources {
            format
            mimeType
            url
        }
        originalSource {
            format
            mimeType
            url
        }
    }
    ... on MediaImage {
        id
        image {
            altText
            originalSrc
            width
            height
        }
    }
}
',
            [
                'id' => "gid://shopify/{$idType}/{$productId}",
                'limit' => (int)$limit,
                'cursor' => $cursor,
            ]
        );
    }

    /**
     * @param string $collectionId
     *
     * @return array|Promise|ResponseAccess
     * @throws Exception
     */
    public function collectionMedia($collectionId)
    {
        return $this->getClient()->graph('
        query ($id: ID!){
    collection(id: $id) {
    	image {
        id
        altText
        originalSrc
        width
        height
      }
  }
}
        ', [
            'id' => "gid://shopify/Collection/{$collectionId}",
        ]);
    }
}
