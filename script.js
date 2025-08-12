/* scripts.js
   Basic frontend helpers for Farm2Home:
   - load header/footer
   - add simple cart (localStorage)
   - smooth scrolling
   - "Shop Now" behavior
*/

/* ---------- Helper: load HTML partials (header/footer) ---------- */
function loadPartial(url, placeholderId, callback) {
  fetch(url)
    .then(res => {
      if (!res.ok) throw new Error('Network response was not ok');
      return res.text();
    })
    .then(html => {
      document.getElementById(placeholderId).innerHTML = html;
      if (typeof callback === 'function') callback();
    })
    .catch(err => {
      console.error(`Error loading ${url}:`, err);
    });
}

/* Load header/footer */
loadPartial('header.html', 'header-placeholder', setupHeader);
loadPartial('footer.html', 'footer-placeholder');

/* ---------- Cart (localStorage) ---------- */
const CART_KEY = 'farm2home_cart';

function getCart() {
  try {
    return JSON.parse(localStorage.getItem(CART_KEY)) || [];
  } catch (e) {
    console.error('Error reading cart', e);
    return [];
  }
}

function saveCart(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartCount();
}

function addToCart(item) {
  const cart = getCart();
  // if product already in cart, increase quantity
  const idx = cart.findIndex(p => p.id === item.id);
  if (idx >= 0) {
    cart[idx].quantity += item.quantity;
  } else {
    cart.push(item);
  }
  saveCart(cart);
}

/* ---------- Cart UI & interactions ---------- */
function ensureCartUI() {
  // create a simple cart icon in header if not present
  const header = document.getElementById('header-placeholder') || document.querySelector('header');
  if (!header) return;

  // Check if cart container already exists
  if (!document.getElementById('cart-container')) {
    const cartContainer = document.createElement('div');
    cartContainer.id = 'cart-container';
    cartContainer.style.display = 'inline-block';
    cartContainer.style.marginLeft = '16px';
    cartContainer.style.cursor = 'pointer';
    cartContainer.innerHTML = `
      <span id="cart-icon" title="View Cart">🛒</span>
      <span id="cart-count" style="background:#e53935;color:white;border-radius:50%;padding:2px 7px;margin-left:6px;font-size:12px;vertical-align:middle;display:inline-block;">0</span>
    `;
    // If header has a nav, append after it, otherwise append to header placeholder
    const nav = header.querySelector('nav');
    if (nav && nav.parentNode) nav.parentNode.appendChild(cartContainer);
    else header.appendChild(cartContainer);

    cartContainer.addEventListener('click', showCartModal);
  }

  updateCartCount();
}

function updateCartCount() {
  const countEl = document.getElementById('cart-count');
  if (!countEl) return;
  const cart = getCart();
  const totalItems = cart.reduce((s, p) => s + (p.quantity || 0), 0);
  countEl.textContent = totalItems;
}

function showCartModal() {
  const cart = getCart();
  if (!cart.length) {
    alert('Your cart is empty.');
    return;
  }

  // Build a simple cart summary
  let text = 'Your Cart:\n\n';
  let total = 0;
  cart.forEach((p, i) => {
    const lineTotal = (p.price || 0) * (p.quantity || 1);
    total += lineTotal;
    text += `${i + 1}. ${p.name} — ${p.quantity} x ${p.price} = ${lineTotal}\n`;
  });
  text += `\nTotal: ${total.toFixed(2)}\n\nClick OK to clear cart, Cancel to keep.`;

  // For this simple demo: OK clears the cart.
  if (confirm(text)) {
    localStorage.removeItem(CART_KEY);
    updateCartCount();
    alert('Cart cleared.');
  }
}

/* ---------- Wire up "Add to Cart" buttons on product cards ---------- */
function setupAddToCartButtons() {
  // Assumes each product card has: .product, contains h4 for name, p for price, and a button
  const products = document.querySelectorAll('.product');
  products.forEach(product => {
    const btn = product.querySelector('button');
    if (!btn) return;

    btn.addEventListener('click', () => {
      // Try to read product details from DOM
      const nameEl = product.querySelector('h4');
      const priceEl = product.querySelector('p');
      const imgEl = product.querySelector('img');

      const name = nameEl ? nameEl.textContent.trim() : 'Product';
      // price text like "$2.50 / kg" — attempt to extract numeric price
      let price = 0;
      if (priceEl) {
        const m = priceEl.textContent.replace(',', '').match(/[\d.]+/);
        if (m) price = parseFloat(m[0]);
      }
      const image = imgEl ? imgEl.src : '';

      // Create a simple unique id by name+price (in production use a proper product id)
      const id = `${name.toLowerCase().replace(/\s+/g, '-')}-${price}`;

      addToCart({ id, name, price, quantity: 1, image });
      alert(`Added "${name}" to cart.`);
    });
  });
}

/* ---------- Smooth scrolling & Shop Now button ---------- */
function smoothScrollInit() {
  // Smooth scroll for internal anchors
  document.addEventListener('click', function (e) {
    const target = e.target;
    if (target.tagName === 'A' && target.getAttribute('href') && target.getAttribute('href').startsWith('#')) {
      e.preventDefault();
      const id = target.getAttribute('href').slice(1);
      const el = document.getElementById(id);
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });

  // If there's a "Shop Now" button in hero, scroll to #products
  const shopNowBtns = Array.from(document.querySelectorAll('.hero button, button.shop-now'));
  shopNowBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const productsEl = document.getElementById('products');
      if (productsEl) productsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
}

/* ---------- Setup header after it's loaded ---------- */
function setupHeader() {
  // ensure cart UI exists in header
  ensureCartUI();

  // If header contains any login/signup links that should show a modal or route, you can wire them here.
  // Example: if there is a "Login" link with id="login-link":
  const loginLink = document.getElementById('login-link');
  if (loginLink) {
    loginLink.addEventListener('click', (e) => {
      // For demo, prevent default and show alert
      e.preventDefault();
      alert('Login flow not implemented in demo. Integrate with your auth system.');
    });
  }

  // After header is ready, wire add-to-cart (products might already be present)
  setupAddToCartButtons();
  smoothScrollInit();
}

/* ---------- Initialization on DOM ready ---------- */
document.addEventListener('DOMContentLoaded', () => {
  // If header already existed in HTML (not loaded via fetch), still ensure cart and wiring
  if (document.getElementById('header-placeholder') && document.getElementById('header-placeholder').innerHTML.trim()) {
    setupHeader();
  } else if (document.querySelector('header')) {
    // header is directly in page
    setupHeader();
  }

  // Ensure cart count is correct on load
  updateCartCount();

  // If later the header/footer are loaded via fetch (async), setupHeader will run as callback
});
