<?php

namespace Dynamic\Shopify\Controller;

use Dynamic\Shopify\Client\ShopifyClient;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;

/**
 * Class ShopifyOrderHistoryController
 * @package Dynamic\Shopify\Controller
 */
class ShopifyOrderHistoryController extends \PageController
{

    /**
     * @var ShopifyClient|null
     */
    private $client = null;

    /**
     * @param HTTPRequest $request
     */
    public function index(HTTPRequest $request)
    {
        if ($request === null) {
            $request = $this->getRequest();
        }

        if (!$user = Security::getCurrentUser()) {
            return 'No user';
        }

        $email = $user->Email;

        $response = $this->getClient()->graph('{
  orders(first: 5) {
    edges {
      node {
        id
        email
        createdAt
        shippingAddress {
          formatted(withName: true)
        }
        shippingLine {
          title
          discountedPriceSet {
            ...presentmentMoney
          }
          originalPriceSet {
            ...presentmentMoney
          }
        }
        subtotalPriceSet {
          ...presentmentMoney
        }
        taxLines {
          title
          rate
        }
        discountApplications(first: 5) {
          edges {
            node {
              __typename
              ... on DiscountCodeApplication {
                code
              }
              ... on AutomaticDiscountApplication {
                title
              }
              ... on ManualDiscountApplication {
                title
                description
              }
              ... on ScriptDiscountApplication {
                title
              }
              allocationMethod
              targetSelection
              targetType
              value {
                __typename
                ... on MoneyV2 {
                  amount
                  currencyCode
                }
                ... on PricingPercentageValue {
                  percentage
                }
              }
            }
          }
        }
        totalDiscountsSet {
          ...presentmentMoney
        }
        totalPriceSet {
          ...presentmentMoney
        }
        lineItems(first: 10) {
          edges {
            node {
              id
              sku
              name
              variantTitle
              title
              quantity
              originalTotalSet {
                ...presentmentMoney
              }
              originalUnitPriceSet {
                ...presentmentMoney
              }
              product {
                id
                featuredImage {
                  originalSrc
                }
              }
            }
          }
        }
      }
    }
  }
}

fragment presentmentMoney on MoneyBag {
  presentmentMoney {
    amount
    currencyCode
  }
}
');
        echo '<style>pre {overflow: auto;margin: 10px 0;padding: 12px;background-color: #e9f0f4;border: 1px solid #d9dee2;color: #4f5861;font-size: 14px;}</style>';
        $body = $response['body'];
        $ordersData = (array)$body->data->orders->edges->container;
        $orders = $this->parseOrders($ordersData);
        echo '<pre>' . print_r($orders, true) . '</pre>';

        $orders = new ArrayList((array)$body->data->orders->edges);
        return '<pre>' . print_r($orders, true) . '</pre>';
    }

    /**
     * @param array $ordersData
     * @return ArrayList
     */
    private function parseOrders($ordersData)
    {
        $orders = new ArrayList();
        foreach ($ordersData as $orderData) {
            $order = new ArrayData((array) $orderData['node']);
            $orders->push(
                new ArrayData([
                    'ID' => $order->id,
                    'Email' => $order->email,
                    'CreatedAt' => $order->createdAt,
                    'LineItems' => $this->parseLineItems($order->lineItems),
                    'Taxes' => $this->parseTaxes($order->taxLines),
                ])
            );
        }

        return $orders;
    }

    /**
     * @param array $lineItems
     * @return ArrayList
     */
    private function parseLineItems($lineItems)
    {
        $items = new ArrayList();
        foreach ($lineItems->edges as $lineItem) {
            $item = new ArrayData((array) $lineItem['node']);
            $items->push(
                new ArrayData([
                    'ID' => $item->id,
                    'ProductID' => $item->product->id,
                    'Sku' => $item->sku,
                    'Name' => $item->name,
                    'ImageSrc' => $item->product->featuredImage->originalSrc,
                    'Quantity' => $item->quantity,
                    'OriginalPriceSingle' => $item->originalUnitPriceSet->presentmentMoney->amount,
                    'OriginalPriceTotal' => $item->originalTotalSet->presentmentMoney->amount,
                ])
            );
        }

        return $items;
    }

    /**
     * @param array $taxLines
     * @return ArrayList
     */
    private function parseTaxes($taxLines)
    {
        $taxes = new ArrayList();
        foreach ($taxLines as $taxLine) {
            $tax = (object) $taxLine;
            $taxes->push(
                new ArrayData([
                    'Title' => $tax->title,
                    'Rate' => $tax->rate,
                ])
            );
        }

        return $taxes;
    }

    /**
     * @return ShopifyClient
     */
    private function getClient()
    {
        if ($this->client === null) {
            $this->client = ShopifyClient::create();
        }

        return $this->client;
    }
}
