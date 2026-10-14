# Fountain Drinks + QR

A QR code for every fountain drink ordered through TastyIgniter, for use with **Pepsi Spire** controlled-pour dispensers. The customer scans the code from their phone at the dispenser, which pours that one drink in the size that was paid for.

**[Watch QR controlled pour in action](https://media.cepuder.com/fialka?src=tastyigniter)** (49 seconds, at a movie theater in the Czech Republic): the customer pays, scans the code at the Spire and pours.

## What it solves
Self-serve fountain drinks lose money when customers buy a small cup and fill a large one, or pour a sugared drink after paying for a zero-sugar one (which matters where sugar taxes apply). With this extension, each paid drink gets its own code, and the Spire only pours what the code allows: the size and, depending on the licence, sugared drinks and flavor shots.

<img src="https://raw.githubusercontent.com/appscepuder/ti-ext-fountaindrinks/main/docs/phone-codes.png" width="300" alt="Drink codes on the customer's order page">

## How it works
- When an order is paid, the extension issues one code per fountain drink (two Medium drinks on one line give two codes).
- The codes appear on the customer's order page, one per drink, ready to scan from the phone screen. They can also go in the order confirmation email (see below).
- **Online payments:** codes are issued as soon as the payment goes through.
- **Pay at the counter:** TastyIgniter treats these orders as paid straight away, so the codes wait until staff confirm the payment. Open the order in the admin, click its **Status** at the top of the page and choose **Completed**. The customer's order page then shows the codes (it refreshes every two minutes, or on reload).
- The Spire checks each code itself with the store's licence key. It needs no network, no cloud service and no connection to TastyIgniter.
- Each code's ID and issue time are kept with the order (the order's **Drink codes** tab, and the latest 50 on the extension page). The Spire logs the same ID on every pour, so its log can be matched to the order.

<img src="https://raw.githubusercontent.com/appscepuder/ti-ext-fountaindrinks/main/docs/admin-settings.png" width="700" alt="The Fountain Drinks + QR admin page">

## Setup
1. Install the extension from the marketplace (**Manage > Updates**, or the extensions search) and run the update.
2. Open **Restaurant > Fountain Drinks + QR** and upload the `config.license.json` you received from your PepsiCo bottler or from Cepuder. The page shows the licence tier, the sizes it allows and its **Secret ID**, which must match the one on the Spire. The key is stored encrypted and never shown again.
3. On each fountain drink menu item, open the **Fountain drink** tab and set its size (Small, Medium or Large). Optionally allow **sugared drinks** and **flavor shots**. Menu items without a size are not drinks and get no code.
4. Optional: to put the codes in the order confirmation email, add `{!! $drink_codes !!}` to the order mail template under **Design > Mail templates**.

## Requirements
TastyIgniter 4.4 (tested on 4.4.5) with PHP 8.3, the Cart extension and the Orange theme (or a theme based on it) for the customer's order page.

## Licences, dispensers and support
The codes only work on Spire dispensers set up with the same store licence. Licences and dispensers come from your PepsiCo bottler or from Cepuder: https://cepuder.com, info@cepuder.com.

The extension is free software under the GNU GPL v3 or later (`LICENSE`). Pepsi and Spire are trademarks of their respective owners. This extension is made by Cepuder Group Ltd and is not an official PepsiCo product.
