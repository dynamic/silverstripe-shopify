(function () {
  var scriptURL = 'https://sdks.shopifycdn.com/buy-button/latest/buy-button-storefront.min.js';
  window.dataLayer = window.dataLayer || [];

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

  function isObject(item) {
    return (item && typeof item === 'object' && !Array.isArray(item));
  }

  function mergeDeep(target, ...sources) {
    if (!sources.length) return target;
    const source = sources.shift();

    if (isObject(target) && isObject(source)) {
      for (const key in source) {
        if (isObject(source[key])) {
          if (!target[key]) Object.assign(target, { [key]: {} });
          mergeDeep(target[key], source[key]);
        } else {
          Object.assign(target, { [key]: source[key] });
        }
      }
    }

    return mergeDeep(target, ...sources);
  }

  function addVariantToCart(product) {
      var selectedVariant = product.selectedVariant;
      var productModel = product.model;
      dataLayer.push({
        event: 'addToCart',
        ecommerce: {
          currencyCode: selectedVariant.priceV2.currencyCode,
          add: {
            products: [{
              name: selectedVariant.title == 'Default Title' ? productModel.title : selectedVariant.title,
              id: selectedVariant.sku,
              price: selectedVariant.priceV2.amount,
              brand: productModel.vendor,
              category: productModel.productType,
              variant: selectedVariant.title,
              quantity: product.selectedQuantity
            }]
          }
        }
      });
  }

  function updateItemQuantity(cart) {
    console.log(cart);
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
        options: mergeDeep(JSON.parse(cartElement.dataset.cartOptions), {
          cart: {
            events: {
              updateItemQuantity
            }
          }
        })
      });

      for (let elem of document.getElementsByTagName('div')) {
        if (/^product-component-/.test(elem.id)) {
          var optionsConfig = JSON.parse(JSON.stringify(elem.dataset.productOptions));

          ui.createComponent('product', {
            id: elem.dataset.productid,
            node: elem,
            moneyFormat: elem.dataset.moneyFormat,
            options: mergeDeep(JSON.parse(elem.dataset.productOptions), {
              product: {
                events: {
                  addVariantToCart
                }
              }
            })
          });
        }
      }
    });
  }
})();
