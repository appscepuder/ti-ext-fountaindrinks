<?php

return [
    'side_menu' => 'Fountain Drinks + QR',
    'page_title' => 'Fountain Drinks + QR',
    'permissions' => 'Manage the Spire licence and see issued drink codes',
    'intro' => 'When an order is paid, each fountain drink gets its own QR code on the customer\'s order page and in the confirmation email. The customer scans it at the Spire dispenser, which pours that one drink. The Spire works offline: it checks the code with the store\'s licence key. Orders paid at the counter get their codes when you mark them completed.',

    // Licence
    'licence' => 'Spire licence',
    'customer' => 'Customer',
    'tier' => 'Tier',
    'allowed_sizes' => 'Sizes allowed',
    'no_sizes' => 'None: this tier can\'t issue codes',
    'secret_id' => 'Secret ID',
    'help_secret_id' => 'Last 2 characters of the key, as shown on the Spire. They must match.',
    'no_licence' => 'No licence yet: no codes are issued. Upload the config.license.json you received from Cepuder or your bottler.',
    'upload_licence' => 'Upload licence (config.license.json)',
    'replace_licence' => 'Replace licence (config.license.json)',
    'button_upload' => 'Upload',
    'help_upload' => 'The key in the file is stored encrypted and never shown again.',
    'button_remove' => 'Remove licence',
    'confirm_remove' => 'Remove the Spire licence? Orders stop getting drink codes.',
    'alert_licence_saved' => 'Licence saved (Secret ID %s).',
    'alert_licence_removed' => 'Licence removed.',
    'error_choose_file' => 'Choose the config.license.json file first.',
    'error_licence_not_json' => 'This is not a valid config.license.json file.',
    'error_licence_no_secrets' => 'The licence file has no terminalSecrets.',
    'error_licence_malformed' => 'The licence key/iv is malformed (expected 64/32 hex characters).',

    // Menu items
    'tab_fountain_drink' => 'Fountain drink',
    'label_size' => 'Fountain drink size',
    'help_size' => 'Set a size to make this item a fountain drink: each one ordered gets a QR code for the Spire dispenser. Leave empty for other items.',
    'label_sugar' => 'Sugared drinks allowed',
    'help_sugar' => 'The code also pours sugared drinks (otherwise only zero/light drinks).',
    'label_flavors' => 'Flavor shots allowed',
    'help_flavors' => 'The code also allows flavor shots.',
    'size_none' => '- Not a fountain drink -',
    'size_small' => 'Small',
    'size_medium' => 'Medium',
    'size_large' => 'Large',
    'drinks' => 'Fountain drinks',
    'help_drinks' => 'Menu items with a fountain drink size. Set it on the item\'s Fountain drink tab.',
    'no_drinks' => 'No fountain drinks yet: set a size on a menu item.',
    'not_in_licence' => 'not allowed by the licence',
    'column_item' => 'Menu item',

    // Issued codes
    'issued_codes' => 'Issued codes (latest 50)',
    'help_issued_codes' => 'The Spire logs the code ID and time of every pour, so its log can be matched to the order here.',
    'no_codes_yet' => 'No codes issued yet.',
    'column_issued' => 'Issued',
    'column_order' => 'Order',
    'column_drink' => 'Drink',
    'column_code_id' => 'Code ID',

    // Admin order page
    'tab_drink_codes' => 'Drink codes',
    'admin_waiting' => 'Paid at the counter: the drink codes are issued when you mark this order completed.',
    'no_drinks_on_order' => 'No fountain drinks on this order.',
    'missing_no_licence' => 'No drink codes: the Spire licence is missing (Restaurant > Fountain Drinks + QR).',
    'missing_size' => 'No drink code for %s: the Spire licence doesn\'t allow this size.',
    'missing_codes' => 'Only %s of %s drink codes were issued.',

    // Customer facing
    'scan_at_dispenser' => 'Scan at the drinks dispenser',
    'one_code_per_drink' => 'One code per drink. Show it to the dispenser\'s scanner.',
    'valid_one_drink' => 'Valid for one drink',
    'codes_after_payment' => 'Your drink codes will appear here once you\'ve paid at the counter.',
];
