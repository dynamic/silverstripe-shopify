(function () {
  var scriptURL = 'https://sdks.shopifycdn.com/buy-button/latest/buy-button-storefront.min.js';
  var lineItems = [];
  var client;
  var cart;

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

  function addVariantToCart(product, quantity) {
    var selectedVariant = product.hasOwnProperty('selectedVariant') ? product.selectedVariant : product.variant;
    var productModel = product.hasOwnProperty('model') ? product.model : product;
    if (typeof quantity === 'undefined') {
      quantity = product.selectedQuantity;
    }
    dataLayer.push({ ecommerce: null });  /* Clear the previous ecommerce object. */
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
            quantity: quantity
          }]
        }
      }
    });
  }

  function removeVariantFromCart(product, quantity) {
    var selectedVariant = product.hasOwnProperty('selectedVariant') ? product.selectedVariant : product.variant;
    var productModel = product.hasOwnProperty('model') ? product.model : product;
    if (typeof quantity === 'undefined') {
      quantity = product.selectedQuantity;
    }
    dataLayer.push({ ecommerce: null });  /* Clear the previous ecommerce object. */
    dataLayer.push({
      event: 'removeFromCart',
      ecommerce: {
        currencyCode: selectedVariant.priceV2.currencyCode,
        remove: {
          products: [{
            name: selectedVariant.title == 'Default Title' ? productModel.title : selectedVariant.title,
            id: selectedVariant.sku,
            price: selectedVariant.priceV2.amount,
            brand: productModel.vendor,
            category: productModel.productType,
            variant: selectedVariant.title,
            quantity: quantity
          }]
        }
      }
    });
  }

  function updateItemQuantity(cart) {
    /* buy button js fires event before anything is updated in cart */
    lineItems = cart.model.lineItems;
  }

  function afterRender(component) {
    if (!lineItems) {
      lineItems = cart.model.lineItems;
      return;
    }

    if (!component.hasOwnProperty('lineItemCache')) {
      return;
    }

    if (!component.hasOwnProperty('model') || component.model == null) {
      return;
    }

    if (!component.model.lineItems) {
      return;
    }

    var lineItemsMerged = getLineItemsMerged(lineItems, component.model.lineItems);
    var quantityDiffs = getLineItemQuantityDiffs(lineItemsMerged);
    quantityDiffs.forEach(function(lineItem) {
      if (lineItem.quantity > lineItem.newQuantity) {
        removeVariantFromCart(lineItem, lineItem.quantity - lineItem.newQuantity);
      } else {
        addVariantToCart(lineItem, lineItem.newQuantity - lineItem.quantity);
      }
    });
  }

  function getLineItemQuantityDiffs(lineItems) {
    return lineItems.filter(function(lineItem) {
      return lineItem.hasOwnProperty('newQuantity') && lineItem.quantity !== lineItem.newQuantity;
    });
  }

  function getLineItemsMerged(oldLineItems, newLineItems) {
    var lineItems = [];
    oldLineItems.forEach(function(oldLineItem) {
      var newLineItem = newLineItems.filter(function(newLineItem) {
        return newLineItem.id === oldLineItem.id;
      })[0];
      if (newLineItem) {
        oldLineItem.newQuantity = newLineItem.quantity;
      } else {
        oldLineItem.newQuantity = 0;
      }
      lineItems.push(oldLineItem);
    });

    newLineItems.forEach(function(newLineItem) {
      if (lineItems.filter(function(lineItem) {
        return lineItem.id == newLineItem.id;
      }).length === 0) {
        lineItems.push(newLineItem);
      }
    });

    return lineItems;
  }

  function ShopifyBuyInit() {
    var cartElement = document.getElementById('shopify-cart');
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
              updateItemQuantity,
              afterInit: function(cart) {
                if (!cart.hasOwnProperty('model') || cart.model == null) {
                  return;
                }
                lineItems = cart.model.lineItems;
              },
              afterRender
            }
          },
          toggle: { /* for easier cart opened tracking */
            iframe: false
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

  window.addEventListener('load',function(event) {
    var products = document.querySelectorAll('[data-sku]:not(.trustpilot-widget)');
    var impressions = [];

    products.forEach(function(product) {
      if (impressions.filter(function(impression) {
        return impression.id === product.dataset.sku;
      }).length > 0) {
        return;
      }~

      impressions.push({
        name: product.dataset.title,
        id: product.dataset.sku,
        brand: product.dataset.vendor,
        category: product.dataset.category,
        position: impressions.length + 1
      });
    });

    dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
    dataLayer.push({
      ecommerce: {
        impressions: impressions
      }
    });
  });
})();
