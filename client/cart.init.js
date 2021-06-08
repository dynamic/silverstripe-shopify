(function () {
  var scriptURL = 'https://sdks.shopifycdn.com/buy-button/latest/buy-button-storefront.min.js';

  if (window.ShopifyBuy) {
    if (window.ShopifyBuy.UI) {
      ShopifyBuyInit();
    } else {
      loadScript();
    }
  } else {
    loadScript();
  }

  function loadScript() {
    var script = document.createElement('script');
    script.async = true;
    script.src = scriptURL;
    script.onload = function () {
      shopifyBuyListener();
    }

    document.getElementsByTagName('body')[0].appendChild(script);
  }

  function shopifyBuyListener() {
    if (window.ShopifyBuy) {
      if (window.ShopifyBuy.UI) {
        ShopifyBuyInit();
      } else {
        loadScript();
      }
    } else {
      loadScript();
    }
  }

  function ShopifyBuyInit() {
    var cartElement = document.getElementById('shopify-cart'),
      client = ShopifyBuy.buildClient({
        domain: '' + cartElement.dataset.domain + '',
        storefrontAccessToken: '' + cartElement.dataset.token,
      });

    ShopifyBuy.UI.onReady(client).then(function (ui) {
      ui.createComponent('cart', {
        node: cartElement,
        moneyFormat: cartElement.dataset.currencySymbol,
        options: JSON.parse(cartElement.dataset.cartOptions)
      });

      for (let elem of document.getElementsByTagName('div')) {
        if (/^product-component-/.test(elem.id)) {
          var optionsConfig = JSON.parse(JSON.stringify(elem.dataset.productOptions));

          ui.createComponent('product', {
            id: elem.dataset.productid,
            node: elem,
            moneyFormat: elem.dataset.moneyFormat,
            options: JSON.parse(elem.dataset.productOptions)
          });
        }
      }
    });
  }
})();
