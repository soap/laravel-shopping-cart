export default {
    title: 'Laravel Shopping Cart',
    description: 'A simple and elegant shopping cart implementation for Laravel',
    base: '/laravel-shopping-cart/',
    
    head: [
      ['link', { rel: 'icon', href: '/laravel-shopping-cart/favicon.ico' }],
      ['meta', { name: 'theme-color', content: '#3c8772' }],
      ['meta', { property: 'og:type', content: 'website' }],
      ['meta', { property: 'og:locale', content: 'en' }],
      ['meta', { property: 'og:title', content: 'Laravel Shopping Cart | Documentation' }],
      ['meta', { property: 'og:site_name', content: 'Laravel Shopping Cart' }],
    ],
  
    themeConfig: {
      logo: '/logo.svg',
      
      nav: [
        { text: 'Guide', link: '/installation' },
        { text: 'API Reference', link: '/api/' },
        { text: 'Examples', link: '/examples/' },
        {
          text: 'v2.0',
          items: [
            { text: 'v2.0 (Current)', link: '/installation' },
            { text: 'v1.x', link: 'https://v1.laravel-shopping-cart.com' }
          ]
        }
      ],
  
      sidebar: [
        {
          text: 'Getting Started',
          collapsed: false,
          items: [
            { text: 'Installation', link: '/installation' },
            { text: 'Quick Start', link: '/quick-start' },
            { text: 'Configuration', link: '/configuration' }
          ]
        },
        {
          text: 'Usage',
          collapsed: false,
          items: [
            { text: 'Basic Usage', link: '/basic-usage' },
            { text: 'Advanced Features', link: '/advanced/' },
            { text: 'Custom Models', link: '/advanced/custom-models' },
            { text: 'Events & Listeners', link: '/advanced/events' },
            { text: 'Storage Options', link: '/advanced/storage' },
            { text: 'Multiple Instances', link: '/advanced/multiple-instances' }
          ]
        },
        {
          text: 'API Reference',
          collapsed: false,
          items: [
            { text: 'Overview', link: '/api/' },
            { text: 'Cart', link: '/api/cart' },
            { text: 'CartItem', link: '/api/cartitem' },
            { text: 'Facades', link: '/api/facades' }
          ]
        },
        {
          text: 'Examples',
          collapsed: false,
          items: [
            { text: 'Overview', link: '/examples/' },
            { text: 'AJAX Cart', link: '/examples/ajax-cart' },
            { text: 'Checkout Flow', link: '/examples/checkout-flow' },
            { text: 'Multi-Currency', link: '/examples/multi-currency' }
          ]
        },
        {
          text: 'Help',
          collapsed: false,
          items: [
            { text: 'Troubleshooting', link: '/troubleshooting' }
          ]
        }
      ],
  
      socialLinks: [
        { icon: 'github', link: 'https://github.com/soap/laravel-shopping-cart' },
        { icon: 'discord', link: 'https://discord.gg/laravel' }
      ],
  
      footer: {
        message: 'Released under the MIT License.',
        copyright: 'Copyright © 2024 SOAP Team'
      },
  
      editLink: {
        pattern: 'https://github.com/soap/laravel-shopping-cart/edit/main/docs/:path'
      },
  
      search: {
        provider: 'local'
      }
    },
  
    markdown: {
      theme: 'github-dark-dimmed',
      lineNumbers: true
    }
  }