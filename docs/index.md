---
layout: home

hero:
  name: "Laravel Shopping Cart"
  text: "Simple & Elegant Cart Implementation"
  tagline: A powerful, flexible shopping cart package for Laravel applications
  image:
    src: /hero-logo.svg
    alt: Laravel Shopping Cart
  actions:
    - theme: brand
      text: Get Started
      link: /installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/soap/laravel-shopping-cart

features:
  - icon: 🛒
    title: Easy to Use
    details: Simple API with intuitive methods for managing cart items and calculations
  - icon: 🔧
    title: Highly Configurable
    details: Customize tax rates, storage drivers, and cart behavior to fit your needs
  - icon: 🎯
    title: Laravel Integration
    details: Built specifically for Laravel with Facade support and service container integration
  - icon: 💾
    title: Coupons and Conditions Support
    details: Use coupons and discount condtion to boost your
  - icon: 🔄
    title: Events & Hooks
    details: Listen to cart events and implement custom logic with Laravel's event system
  - icon: 🌍
    title: Multi-Instance
    details: Support multiple cart instances for complex e-commerce scenarios
---
<style>
.VPHero .image {
  max-width: 400px;
  margin: 0 auto;
}

.VPHero .image img {
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  transition: transform 0.3s ease;
}

.VPHero .image img:hover {
  transform: scale(1.02);
}

@media (max-width: 768px) {
  .VPHero .image {
    max-width: 280px;
  }
}
</style>
## Quick Example

```php
use Soap\ShoppingCart\Facades\ShoppingCart;

// Add items to cart
ShoppingCart::add('293ad', 'Product Name', 1, 99.99, ['color' => 'red']);

// Get cart contents
$cartItems = ShoppingCart::content();

// Calculate totals
$subtotal = ShoppingCart::subtotal();
$total = ShoppingCart::total();

// Remove items
ShoppingCart::remove('293ad');