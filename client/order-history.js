document.addEventListener('click', function (e) {
  var orderList = document.getElementById('order-list');
  var target = e.target;
  if (target && target.id == 'orders-previous-link') {
    history.pushState(target.href, document.title, target.href);
    fetch(target.href, function (data) {
      var orders = data.getElementsByClassName('order');
      for (var i = orders.length - 1; 0 <= i; i--) {
        orderList.insertBefore(orders[i], orderList.firstChild);
      }

      var fetchButton = data.querySelector('#orders-previous-link');
      var existingFetchButton = document.getElementById('orders-previous-link');
      if (!fetchButton) {
        existingFetchButton.outerHTML = "";
      } else {
        existingFetchButton.setAttribute('href', fetchButton.getAttribute('href'));
      }
    });
    e.preventDefault()
    return false;
  }
  if (target && target.id == 'orders-next-link') {
    history.pushState(target.href, document.title, target.href);
    fetch(target.href, function (data) {
      var orders = data.getElementsByClassName('order');
      for (var i = 0; i < orders.length; i++) {
        orderList.appendChild(orders[i]);
      }

      var fetchButton = data.querySelector('#orders-next-link');
      var existingFetchButton = document.getElementById('orders-next-link');
      if (!fetchButton) {
        existingFetchButton.outerHTML = "";
      } else {
        existingFetchButton.setAttribute('href', fetchButton.getAttribute('href'));
      }
    });
    e.preventDefault()
    return false;
  }
});

function fetch(url, callback, callbackDelay) {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      var data = document.createElement('div');
      data.innerHTML = this.responseText;
      if (callback instanceof Function) {
        if (callbackDelay === undefined || callbackDelay < 1) {
          callback(data);
        } else {
          // for animations to trigger
          setTimeout(callback.bind(null, data), callbackDelay);
        }
      }
    }
  };
  xhttp.open('GET', url, true);
  xhttp.setRequestHeader('x-requested-with', 'XMLHttpRequest');
  xhttp.send();
}
