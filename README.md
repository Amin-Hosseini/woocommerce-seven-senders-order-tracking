# WooCommerce Seven Senders Order Tracking
Interacts with the Seven Senders API to provide order tracking functionality to your WooCommerce shop.

## Purpose
The purpose of this plugin is to integrate the services provided by [Seven Senders](https://www.sevensenders.com/) with your WooCommerce shop in order to introduce order tracking capabilities. Namely, when the status of an order changes through your shop, the plugin will automatically create shipments, change the state of the remote order entity, and update the local order with the delivery date according to the information from Seven Senders.

## How to Install
### From this repository
Go to the [releases section of the repository](https://github.com/hypeventures/woocommerce-seven-senders-order-tracking/releases) and download the most recent ZIP archive release.

Then, from your WordPress administration panel, go to `Plugins > Add New` and click the `Upload Plugin` button at the top of the page, upload the archive, and finally activate the plugin from the plugins page on Wordpress.

### From source
You will need Git installed to install the plugin. To complete the following steps, you will need to open a command line terminal.

Navigate to the `wp-content/plugins` directory of your WordPress installation:

`cd path/to/wordpress/wp-content/plugins`

Clone the Github repository:

`git clone git@github.com:hypeventures/woocommerce-seven-senders-order-tracking.git`

## How to Use
From your WordPress administration panel go to `Plugins > Installed Plugins` and scroll down until you find `WooCommerce Seven Senders Order Tracking`. You will need to activate it first, then click on the `Order Tracking` sub-menu to configure it. In order to use the plugin in all its capacity, you will have to adhere to the following workflow:

1. When an order is placed by your customer, and its status changes to `Processing`, the plugin will export the order data to Seven Senders via their API and set the remote order state to `in_preparation`.
1. Before you change the order status to `Completed`, you will have to set the order meta keys `wcssot_shipping_carrier` with [a supported shipping carrier identifier](https://api.sevensenders.com/v2/docs.html#/Carrier/getCarrierCollection) (e.g.: `dhl`, `ups`, `dpd`, etc.) and `wcssot_shipping_tracking_code` with the corresponding tracking code provided by your shipping carrier.
1. When the order status changes to `Completed` and the meta keys above are set and valid, the shipment will be exported and the remote order state will change to `in_production`.

**Notice:** The planned pickup time for the shipment is calculated as the next business day at 12:00 of your site's timezone.

### Delivery Date Tracking

The daily and weekly delivery date tracking functionality adds the meta key `wcssot_delivered_at` for every order that has a status of `completed`, `processing`, or `on-hold`, has been created between the specified range for each job *(by default, `10` to `14` days ago for the daily and `15` to `60` days ago for the weekly)*, and its shipment has the status `completed` on Seven Senders.

You can manage this functionality from the administration panel settings page. The purpose of this feature is to provide you with delivery status information regarding your orders' shipments.

## Configuration

### Required Parameters
#### API Base URL
The base URL of the Seven Sender API. Default value is `https://api.sevensenders.com/v2`.

#### API Access Key
The API access key of your Seven Senders account.

#### Tracking Page Base URL
The base URL of your Seven Senders account. It's something like `https://trackingpages.com/[SENDWISE-TRACKINGPAGE-HASH]/<orderId>`.

### Extra Parameters

#### Daily Tracking Enabled
Lets you enable/disable the daily delivery date tracking functionality. The state of the checkbox reflect the state of the event.

#### Weekly Tracking Enabled
Lets you enable/disable the weekly delivery date tracking functionality. The state of the checkbox reflect the state of the event.

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
