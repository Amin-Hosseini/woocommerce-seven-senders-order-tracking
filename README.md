# WooCommerce Seven Senders Order Tracking
Interacts with the Seven Senders API to provide order tracking functionality to your WooCommerce shop.

## How to Install
### From this repository
Go to the [releases section of the repository](https://github.com/hypeventures/woocommerce-seven-senders-order-tracking/releases) and download the most recent release.

Then, from your WordPress administration panel, go to `Plugins > Add New` and click the `Upload Plugin` button at the top of the page.

### From source
You will need Git installed to install the plugin. To complete the following steps, you will need to open a command line terminal.

Navigate to the `wp-content/plugins` directory of your WordPress installation:

`cd path/to/wordpress/wp-content/plugins`

Clone the Github repository:

`git clone git@github.com:hypeventures/woocommerce-seven-senders-order-tracking.git`

## How to Use
From your WordPress administration panel go to `Plugins > Installed Plugins` and scroll down until you find `WooCommerce Seven Senders Order Tracking`. You will need to activate it first, then click on the `Order Tracking` sub-menu to configure it. In order to use the plugin in all its capacity, you will have to adhere to the following workflow:

1. When an order is placed by your customer, and its status changes to `Processing`, the plugin will export the order data to the Seven Senders API and set the remote order state to `in_preparation`.
2. Before you change the order status to `Completed`, you will have to set the order meta keys `wcssot_shipping_carrier` with a supported shipping carrier identifier (e.g.: `dhl`, `ups`, `dpd`, etc.) and `wcssot_shipping_tracking_code` with the corresponding tracking code provided by your shipping carrier.
3. When the order status changes to `Completed` and the meta keys above are set and valid, the shipment will be exported and the remote order state will change to `in_production`.

### Notice
The planned pickup time for the shipment is calculated as the next business day at 12:00.

## Configuration
#### API Base URL
The base URL of the Seven Sender API. Default value is `https://api.sevensenders.com/v2`.

#### API Access Key
The access key of your Seven Senders account. You can find it at [your Seven Senders dashboard](https://sendwise.sevensenders.com/settings/shop/integrations).

#### Tracking Page Base URL
The base URL of your Seven Senders account. It's something like `https://[COMPANY].tracking.7senders.com/#/order`.

## Requirements
The plugin requires that WooCommerce 3.5.0 or later is installed, otherwise it will exit silently.

## License
Copyright (C) 2018-2019 Invincible Brands GmbH

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.