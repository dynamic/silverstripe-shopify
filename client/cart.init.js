(function () {
  var scriptURL = 'https://sdks.shopifycdn.com/buy-button/latest/buy-button-storefront.min.js';

  if (window.ShopifyBuy) {
    console.log('ShopifyBuy exists');
    if (window.ShopifyBuy.UI) {
      console.log('ShopifyBuy.UI exists');
      ShopifyBuyInit();
    } else {
      console.log('ShopifyBuy.UI does not exists');
      loadScript();
    }
  } else {
    console.log('ShopifyBuy does not exists');
    loadScript();
  }

  function loadScript() {
    console.log('load script called');
    var script = document.createElement('script');
    script.async = true;
    script.src = scriptURL;
    script.onload = function () {
      shopifyBuyListener();
    }//*/

    document.getElementsByTagName('body')[0].appendChild(script);
  }

  function shopifyBuyListener() {
    if (window.ShopifyBuy) {
      if (window.ShopifyBuy.UI) {
        console.log('Listener ShopifyBuy.UI exists');
        ShopifyBuyInit();
      } else {
        console.log('Listener ShopifyBuy.UI does not exists');
        loadScript();
      }
    } else {
      console.log('Listener ShopifyBuy does not exists');
      loadScript();
    }
  }

  function ShopifyBuyInit() {
    console.log('init buy button');
    var cartElement = document.getElementById('shopify-cart'),
      client = ShopifyBuy.buildClient({
        domain: '' + cartElement.dataset.domain + '',
        storefrontAccessToken: '' + cartElement.dataset.token,
      });

    console.log('' + cartElement.dataset.domain + '');
    console.log('' + cartElement.dataset.token);
    console.log(cartElement.dataset.currencySymbol);
    console.log(cartElement.dataset.cartOptions);

    ShopifyBuy.UI.onReady(client).then(function (ui) {
      ui.createComponent('cart', {
        node: cartElement,
        moneyFormat: cartElement.dataset.currencySymbol,
        options: cartElement.dataset.cartOptions
      });

      for (let elem of document.getElementsByTagName('div')) {
        if (/^product-component-/.test(elem.id)) {
          console.log('iterating over ' + elem.id);
          console.log(elem.dataset.productid);
          console.log(elem.dataset.moneyFormat);
          console.log(elem.dataset.productOptions);
          ui.createComponent('product', {
            id: elem.dataset.productid,
            node: elem,
            moneyFormat: elem.dataset.moneyFormat,
            options: elem.dataset.productOptions
          });
        }
      }
    });

    /*window.shopifyClient = ShopifyBuy.UI.init(client);
    window.shopifyClient.createComponent('cart', {
        node: document.getElementById('shopify-cart'),
        moneyFormat: '$CurrencySymbol{{amount}}',
        options: $CartOptions
    });//*/
  }

})();
