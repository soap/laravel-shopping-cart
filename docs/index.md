---
layout: home

hero:
  name: "Laravel Shopping Cart"
  text: "Simple & Elegant Cart Implementation"
  tagline: A powerful, flexible shopping cart package for Laravel applications
  image:
    src: /logo.svg
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
    title: Flexible Storage
    details: Store cart data in session, database, or implement custom storage drivers
  - icon: 🔄
    title: Events & Hooks
    details: Listen to cart events and implement custom logic with Laravel's event system
  - icon: 🌍
    title: Multi-Instance
    details: Support multiple cart instances for complex e-commerce scenarios
---

## Quick Example

```php
use Cart;

// Add items to cart
Cart::add('293ad', 'Product Name', 1, 99.99, ['color' => 'red']);

// Get cart contents
$cartItems = Cart::content();

// Calculate totals
$subtotal = Cart::subtotal();
$total = Cart::total();

// Remove items
Cart::remove('293ad');