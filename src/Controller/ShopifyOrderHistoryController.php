<?php

namespace Dynamic\Shopify\Controller;

use Dynamic\Shopify\Client\ShopifyClient;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBText;
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
            return $this->customise([
                'Orders' => false,
                'HasPreviousPage' => false,
                'PreviousPageLink' => "",
                'HasNextPage' => false,
                'NextPageLink' => "",
            ]);
        }

        $email = $user->Email;
        $cursor = $request->getVar('cursor');
        $limit = (int)$request->getVar('limit') ?: 5;
        if ($limit > 5) {
            $limit = 5;
        }

        $firstLast = 'first';
        $beforeAfter = 'after';
        if ($request->getVar('direction') === "previous") {
            $firstLast = 'last';
            $beforeAfter = 'before';
        }

        if (!Director::isLive() && $request->getVar('email')) {
            $email = $request->getVar('email');
        }

        $response = $this->getClient()->graph(
            'query ($limit: Int!, $cursor: String, $query: String) {
  orders(' . $firstLast . ': $limit, ' . $beforeAfter . ': $cursor, sortKey: CREATED_AT, reverse: true, query: $query) {
    edges {
      node {
        id
        name
        email
        note
        createdAt
        shippingAddress {
          formatted(withName: true)
        }
        totalWeight
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
          ratePercentage
          priceSet {
            ...presentmentMoney
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
              discountAllocations {
                discountApplication {
                  __typename
                  ... on DiscountCodeApplication {
                    code
                  }
                  ... on AutomaticDiscountApplication {
                    title
                  }
                  ... on ManualDiscountApplication {
                    title
                  }
                  ... on ScriptDiscountApplication {
                    title
                  }
                }
                allocatedAmountSet {
                  ...presentmentMoney
                }
              }
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
      cursor
    }
    pageInfo {
      hasPreviousPage
      hasNextPage
    }
  }
}

fragment presentmentMoney on MoneyBag {
  presentmentMoney {
    amount
    currencyCode
  }
}
',
            [
                "query" => isset($email) && $email ? "email:'$email'" : null,
                "limit" => $limit,
                "cursor" => $cursor,
            ]
        );

        $body = $response['body'];
        $data = $this->getCustomizedData(
            $request,
            $this->parseOrders($body->data->orders->edges),
            $body->data->orders->pageInfo->hasPreviousPage,
            $body->data->orders->pageInfo->hasNextPage
        );
        if ($request->isAjax()) {
            return $this->customise($data)->renderWith('Dynamic\Shopify\Page\Layout\ShopifyOrderHistory');
        }
        return $this->customise($data);
    }


    /**
     * @param HTTPRequest $request
     * @param ArrayList $orders
     * @param bool $hasPrevious
     * @param bool $hasNext
     * @return array
     */
    private function getCustomizedData($request, $orders, $hasPrevious, $hasNext)
    {
        if ($orders->Count() === 0) {
            return [
                'Orders' => $orders,
                'HasPreviousPage' => $hasPrevious,
                'PreviousPageLink' => "",
                'HasNextPage' => $hasNext,
                'NextPageLink' => "",
            ];
        }

        $queryPrevious = http_build_query(
            array_merge($request->getVars(), [
                'direction' => 'previous',
                'cursor' => $orders->first()->Cursor,
            ])
        );
        $queryNext = http_build_query(
            array_merge($request->getVars(), [
                'direction' => 'next',
                'cursor' => $orders->last()->Cursor,
            ])
        );
        return [
            'Orders' => $orders,
            'HasPreviousPage' => $hasPrevious,
            'PreviousPageLink' => "{$this->Link()}?{$queryPrevious}",
            'HasNextPage' => $hasNext,
            'NextPageLink' => "{$this->Link()}?{$queryNext}",
        ];
    }

    /**
     * @param double $value
     * @return DBCurrency
     */
    private function toCurrency($value)
    {
        return DBCurrency::create()->setValue($value);
    }

    /**
     * @param array $ordersData
     * @return ArrayList
     */
    private function parseOrders($ordersData)
    {
        $orders = new ArrayList();
        foreach ($ordersData as $orderData) {
            $order = $orderData->node;
            $orders->push(
                new ArrayData([
                    'ID' => $order->id,
                    'Name' => $order->name,
                    'Email' => $order->email,
                    'Note' => $order->note,
                    'CreatedAt' => DBDatetime::create()->setValue($order->createdAt),
                    'LineItems' => $this->parseLineItems($order->lineItems),
                    'SubTotal' => $this->toCurrency($order->subtotalPriceSet->presentmentMoney->amount),
                    'ShippingAddress' => $this->parseShippingAddress($order->shippingAddress->formatted),
                    'Shipping' => ArrayData::create([
                        'Title' => $order->shippingLine->title,
                        'Amount' => $this->toCurrency($order->shippingLine->originalPriceSet->presentmentMoney->amount),
                        'Weight' => $order->totalWeight,
                    ]),
                    'TotalDiscount' => $this->toCurrency($order->totalDiscountsSet->presentmentMoney->amount),
                    'Taxes' => $this->parseTaxes($order->taxLines),
                    'Total' => $this->toCurrency($order->totalPriceSet->presentmentMoney->amount),
                    'Cursor' => $orderData->cursor,
                ])
            );
        }

        return $orders;
    }

    /**
     * @param $address
     */
    private function parseShippingAddress($address)
    {
        $addr = ArrayList::create();
        foreach ($address as $line) {
            $addr->push(ArrayData::create(['Line' => $line]));
        }
        return $addr;
    }

    /**
     * @param array $lineItems
     * @return ArrayList
     */
    private function parseLineItems($lineItems)
    {
        $items = new ArrayList();
        foreach ($lineItems->edges as $lineItem) {
            $item = $lineItem->node;
            $singlePrice = $this->toCurrency($item->originalUnitPriceSet->presentmentMoney->amount);
            $totalPrice = $this->toCurrency($item->originalTotalSet->presentmentMoney->amount);
            $discountTotal = $this->getTotalDiscount($item->discountAllocations);
            $discountSingle = $this->toCurrency($discountTotal->getValue() / $item->quantity);
            $discountSinglePrice = $this->toCurrency($discountSingle->getValue() / $item->quantity);
            $items->push(
                new ArrayData([
                    'ID' => $item->id,
                    'ProductID' => $item->product->id,
                    'Sku' => $item->sku,
                    'Name' => $item->name,
                    'Title' => $item->title,
                    'VariantTitle' => $item->variantTitle,
                    'ImageSrc' => $item->product->featuredImage->originalSrc,
                    'Quantity' => $item->quantity,
                    'OriginalPriceSingle' => $singlePrice,
                    'OriginalPriceTotal' => $totalPrice,
                    'Discounts' => $this->parseDiscounts($item->discountAllocations),
                    'DiscountSingle' => $discountSingle,
                    'TotalDiscount' => $discountTotal,
                    'DiscountedPriceSingle' => $this->toCurrency($singlePrice->getValue() - $discountSingle->getValue()),
                    'DiscountedPrice' => $this->toCurrency($totalPrice->getValue() - $discountTotal->getValue()),
                ])
            );
        }

        return $items;
    }

    /**
     * @param array $discountAllocations
     * @return DBCurrency
     */
    private function getTotalDiscount($discountAllocations)
    {
        $discountTotal = 0;
        foreach ($discountAllocations as $discount) {
            $discountTotal += $discount->allocatedAmountSet->presentmentMoney->amount;
        }

        return $this->toCurrency($discountTotal);
    }

    /**
     * @param array $discountAllocations
     * @return ArrayList
     */
    private function parseDiscounts($discountAllocations)
    {
        $discounts = ArrayList::create();
        foreach ($discountAllocations as $discount) {
            $isCodeField = $discount->discountApplication->__typename === 'DiscountCodeApplication';
            $discounts->push(
                ArrayData::create([
                    'Code' => $isCodeField ? $discount->code : $discount->discountApplication->title,
                    'Amount' => $this->toCurrency($discount->allocatedAmountSet->presentmentMoney->amount),
                ])
            );
        }
        return $discounts;
    }

    /**
     * @param array $taxLines
     * @return ArrayList
     */
    private function parseTaxes($taxLines)
    {
        $taxes = new ArrayList();
        foreach ($taxLines as $tax) {
            $taxes->push(
                new ArrayData([
                    'Title' => $tax->title,
                    'Rate' => $tax->ratePercentage,
                    'Amount' => $this->toCurrency($tax->priceSet->presentmentMoney->amount),
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
