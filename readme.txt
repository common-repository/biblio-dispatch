=== Print Management with Biblio Dispatch ===

Contributors: invoked
Tags: print-services,print-fulfilment,books-printing,order-fetch, webhook
Requires at least: 6.3
Tested up to: 6.6
Stable tag: 1.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin enables user registration and login, creates a webhook upon configuration, and sends orders to a delivery URL when placed.

== Description ==

The **Print Management with Biblio Dispatch** plugin integrates with WooCommerce to automate the process of sending order details to a delivery service API. Upon confihuration, user will be registered to authorsuite dashboard and listens for every new order placement. When an order is placed, the order details are automatically sent to a delivery service URL. Orders payments can be made with authorsuite dashboard as well.

Additionally, the plugin provides a simple registration and login system that integrates with a dashboard, allowing users to manage their delivery information and track their orders.

## Third-Party Service
This plugin relies on the Third Party Api to provide essential features such as user authentication and order tracking.

Domain http://bibliodispatch.com/authorsuite

#Register Api Service
The plugin also communicates with the Delivery Service API at https://bibliodispatch.com/authorsuite/wordpressAuth when:

Users attempt to log in or register to the dashboard.
Users communicate with their account settings.
Creation of webhook

#Delivery Service API
The plugin also communicates with the Delivery Service API at https://bibliodispatch.com/authorsuite/webhookOrders when:

Users place an order
The order details need to be sent to the delivery service for processing.

#Privacy Policy https://bibliodispatch.com/authorsuite/policy


**Key Features:**
* Automatically creates a webhook during plugin configuration.
* Sends order details to a delivery service on each WooCommerce order.
* Provides user login and registration functionality for integration with a dashboard.
* Easy to set up and configure.
* Provides print fulfilment services for the orders placed on website

1. Upload the plugin files to the `/wp-content/plugins/BiblioDispatch` directory or install the plugin directly through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the Rest API settings under the WooCommerce settings section.
4. After installation, a webhook is automatically created, and the plugin will start sending order data to the configured delivery service URL on every new order placement.

= What if i dont have consumer key and consumer secret

Navigate to the Woocommerce advanced setting's Rest Api section, where you can generate the consumer key and consumer secret.

= Do i need to configure Webhook? =

No, webhooks will be automatically generated while clicks on save button of plugin or while access portal.

== Screenshots ==

1. WooCommerce Settings: Configure delivery API and webhook details.
2. Order Integration: Order details sent to the service on order placement.

== Changelog ==

= 1.2 =
* Initial release of the plugin.
* Webhook creation and order details fetching on WooCommerce order placement.
* User login and registration integration with dashboard.

== Upgrade Notice ==

= 1.2 =
Initial release with webhook integration and dashboard connectivity. Upgrade if you need to connect your WooCommerce store with a delivery system.

== A brief Markdown Example ==

**Print Management with Biblio Dispatch** automatically sends WooCommerce order details to a delivery API via webhook.

Ordered list of features:

1. Webhook creation on configuration.
2. Automatic order sending to service.
3. Dashboard login and registration.
4. Provides print fulfilment services for your orders 