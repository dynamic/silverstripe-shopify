<?php

namespace Dynamic\Shopify\Client;

use Exception;
use GuzzleHttp\Promise\Promise;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\ArrayList;
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
    private static $api_version = '2020-04';

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
        $options->setVersion('2020-01');//static::config()->get('api_version')
        $options->setApiPassword($password);
        $options->setType(true);

        $client = new BasicShopifyAPI($options);
        $client->setSession(new Session($domain));

        $this->client = $client;

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
     * @param array $options
     * @return array|Promise
     * @throws Exception
     */
    public function products(array $options = [])
    {
        return $this->getClient()->graph(
            '
{
    shop {
        products(first: 250) {
            edges {
                node {
                    id
                    title
                    handle
                    descriptionHtml
                    vendor
                    productType
                    createdAt
                    updatedAt
                }
            }
        }
    }
}
            ',
            $options
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
        return $this->getClient()->graph("
{
    product(id: \"gid://shopify/Product/{$productId}\") {
        id
        title
        bodyHtml
        vendor
        productType
        createdAt
        handle
        updatedAt
        tags
    }
}
");
    }

    /**
     * Get available product listing ids
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function productListingIds(array $options = [])
    {
        return $this->getClient()->graph('
{
  products(first: 250) {
    edges {
      node {
        id
      }
    }
  }
}
        ');

        //return $this->client->request('GET', "product_listings/product_ids.json", $options);
    }

    /**
     * Get the available Collections
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function collections(array $options = [])
    {
        return $this->getClient()->graph(
            '
{
    shop {
        collections(first: 250) {
            edges {
                node {
                    id
                    title
                    handle
                    descriptionHtml
                    productsCount
                    updatedAt
                    sortOrder
                    publishedOnCurrentPublication
                }
            }
        }
    }
}
            ',
            $options)
        ;
    }

    /**
     * return products of a given collection by handle
     *
     * @param $handle
     * @param array $options
     * @return array|Promise
     * @throws Exception
     */
    public function collectionProducts($handle, array $options = [])
    {
        return $this->getClient()->graph(
            "
{
    collectionByHandle(handle: \"{$handle}\") {
        products(first: 100) {
            edges {
                node {
                    id
                    title
                }
            }
        }
    }
}
            ",
            $options
        );
    }
}
