<?php


$UNC_PRINTS = array(
    'defaults' => array(
        'prices' => array('A4, printed on high quality photo paper' => 400, 'A4 + frame' => 500),
        'remarks' => 'Print will be without the logo. We\'ll deliver the prints to you for free.',
    ),
    1 => array( // 1
        'title' => 'Don\'t Panic John Street',
        'description' => 'John Prymmer, performing with his band "Don\'t Panic" at the Wanch',
        'gig_post_id' => 1155,
        'file_path' => '/2014/11/23/IMG_1580.jpeg',
    ),
    2 => array( // 2
        'title' => 'The Rolling Stoned John',
        'description' => 'John Prymmer, performing with his band "The Rolling Stoned at The Wanch"',
        'gig_post_id' => 34,
        'file_path' => '/2015/11/28/_MG_6955.jpeg',
    ),
    3 => array( // 6
        'title' => 'Rubicube',
        'description' => 'Iris Pascual is singing for the band Rubicube',
        'gig_post_id' => 31,
        'file_path' => '/2016/01/10/_MG_7547.jpeg',
    ),
    4 => array( // 4
        'title' => 'Don\'t Panic Geoff Street',
        'description' => 'Geoff Wheeler, playing bass for Don\'t Panic in heavy rain outside The Wanch',
        'gig_post_id' => 684,
        'file_path' => '/2016/08/28/_MG_0172.jpeg',
    ),
    5 => array( // 7
        'title' => 'Shumking Jem',
        'description' => 'Jem, keytar player at "Shumking Mansion"',
        'gig_post_id' => 799,
        'file_path' => '/2016/12/09/4H8A5896.jpeg',
    ),
    6 => array( // 8
        'title' => 'Shum Jump',
        'description' => 'Shum YS, playing Bass for "Shuming Mansion"',
        'gig_post_id' => 799,
        'file_path' => '/2016/12/09/4H8A5943.jpeg',
    ),
    7 => array( // 9
        'title' => 'Shumking Zaid',
        'description' => 'Zaid Saadat, playing guitar for "Shuming Mansion"',
        'gig_post_id' => 1284,
        'file_path' => '/2017/05/13/4H8A9739.jpeg',
    ),
    8 => array( // 5
        'title' => 'Opium',
        'description' => 'Ingrid, singing for Opium',
        'gig_post_id' => 1318,
        'file_path' => '/2017/06/23/4H8A1454.jpeg',
    ),
    9 => array( // 10
        'title' => 'Don\'t Panic John Sunglasses',
        'description' => 'John, rocking for Don\'t Panic',
        'gig_post_id' => 1749,
        'file_path' => '/2018/06/01/4H8A9659.jpeg',
    ),
    10 => array( // 11
        'title' => 'Sideburns Ziad',
        'description' => 'Ziad Samman, singing for "The Sideburns"',
        'gig_post_id' => 1900,
        'file_path' => '/2018/06/30/4H8A3171-2.jpeg',
    ),
    11 => array( // 3
        'title' => 'Last Orders Zack',
        'description' => 'Zack, lead singer for "Last Orders"',
        'gig_post_id' => 2001,
        'file_path' => '/2018/07/06/4H8A5466.jpeg',
    ),
    12 => array( // 12
        'title' => 'The Thin White Ukes',
        'description' => 'The lead singer for the "Thin White Ukes"',
        'gig_post_id' => 2092,
        'file_path' => '/2018/08/10/4H8A6271-2.jpeg',
    ),
    13 => array( // 13
        'title' => 'Mutant Monster Jump',
        'description' => 'The Japanese baby metal band Mutant Monster',
        'gig_post_id' => 2246,
        'file_path' => '/2018/10/24/4H8A0287.jpeg',
    ),
    14 => array( // 14
        'title' => 'The Prowlers 1',
        'description' => 'Josephine Persson, singing for The Prowlers',
        'gig_post_id' => 2896,
        'file_path' => '/2019/06/29/4H8A2587.jpeg',
    ),
    15 => array( // 15
        'title' => 'Don\'t Panic Mic Hold',
        'description' => 'John Prymmer, singing for Don\'t Panic',
        'gig_post_id' => 2924,
        'file_path' => '/2019/06/29/4H8A3298.jpeg',
    ),
    16 => array( // 16
        'title' => 'The Triplejacks',
        'description' => 'Chris Parker, playing for The Triplejacks',
        'gig_post_id' => 3579,
        'file_path' => '/2019/09/22/4H8A8114.jpeg',
    ),
    17 => array( // 17
        'title' => 'Parpaing Papier',
        'description' => 'The French Punk band Parpaing Papier',
        'gig_post_id' => 3728,
        'file_path' => '/2019/11/29/20194694.jpeg',
    ),
    18 => array( // 18
        'title' => 'Shun Kikuta',
        'description' => 'Japanese Blues singer Shun Kikuta',
        'gig_post_id' => 3747,
        'file_path' => '/2019/12/15/20195100.jpeg',
    ),
    19 => array( // 19
        'title' => 'Brother Plainview',
        'description' => 'Jason, playing bass for Borther Plainview',
        'gig_post_id' => 3763,
        'file_path' => '/2020/02/07/20201302.jpeg',
    ),
    20 => array( // 20
        'title' => 'Esimorp',
        'description' => 'Promise Joe Armstrong, singing for Esimorp',
        'gig_post_id' => 3766,
        'file_path' => '/2020/02/07/20201634.jpeg',
    ),
    21 => array(
        'title' => 'The Prowlers 2',
        'description' => 'Josephine Persson, singing for The Prowlers',
        'gig_post_id' => 4383,
        'file_path' => '/2021/08/21/R5216122.jpeg',
    )
);



function unc_prints_list() {
    $prints_list = unc_prints_fix_defaults();
    $purchase = false;

    // check if someone clicked on an image
    $print_id_get = filter_input(INPUT_GET, 'print_id', FILTER_VALIDATE_INT);
    $print_id_post = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    if ($print_id_get && isset($prints_list[$print_id_get])) {
        echo unc_print_show($print_id_get, $prints_list[$print_id_get], false);
        return;
    } elseif ($print_id_post && isset($prints_list[$print_id_post])) {
        // check if someone purchased something
        $purchase_check = filter_input(INPUT_POST, 'purchased', FILTER_SANITIZE_STRING);
        if ($purchase_check && $purchase_check == 'true') {
            $purchase = true;
        }
        echo unc_print_show($print_id_post, $prints_list[$print_id_post], $purchase);
        return;
    }

    $count = count($prints_list);

    $out = "This is our list of $count featured prints. Please click on an image for details.
        Please note that you can contact me if you want any other image to be printed.
        <div class=\"print_list\">";
    foreach ($prints_list as $id => $D) {
        $out .= "<div class=\"print_link\"><a href=\"?print_id=$id\"><img src=\"https://hongkong-rocks.com/wp-content/unc_gallery/photos{$D['file_path']}\"></a></div>\n";
    }
    $out .= "</div>";

    return $out;

}


function unc_print_show($item_id, $print_data, $purchased = false) {
    $out = '';
    $price_array = $print_data['prices'];

    $prices = 'Print options: <ul>';
    foreach ($price_array as $desc => $price) {
        $prices .= "<li>$desc: $price HKD</li>";
    }
    $prices .= '</ul>';

    if (!$purchased) {
        $buy_button = unc_prints_buy_button($item_id, false);
    } else {
        // show purchase information instead
        $buy_button = "<BR><h2>Thanks for your purchase!</h2>";
        // send emails
        unc_print_send_confirmations($item_id);
    }

    $gig_link = get_permalink($print_data['gig_post_id']);

    $out .= "
        <div class=\"print_display\">
            <img id=\"image_noframe\" class=\"print\" src=\"https://hongkong-rocks.com/wp-content/unc_gallery/photos{$print_data['file_path']}\">
            <img id=\"image_framed\" style=\"display:none\" class=\"print\" src=\"https://hongkong-rocks.com/wp-content/unc_gallery/photos{$print_data['file_path']}\">
            <h2>Description</h2>
            <div class=\"print_description\">{$print_data['description']} <span class=\"print_gig_link\"><a href=\"$gig_link\">Link to gig shoot</a></span></div>
            <span class=\"prices_details\">$prices</span>
            <span class=\"remarks\">{$print_data['remarks']}</span>
            <span class=\"buy_button\">$buy_button</span>
        </div>";

    $out .= "<a href=\"https://hongkong-rocks.com/buy-prints/\">Go back to the list of prints</a>";

    return $out;
}

function unc_print_send_confirmations($item_id) {
    global $UNC_PRINTS;

    $i = $UNC_PRINTS[$item_id];

    $contents = filter_input(INPUT_POST, 'contents', FILTER_SANITIZE_URL);
    $fixed = str_replace("\\", "", $contents);
    $j = json_decode($fixed, true);

    $contents_list = umc_array2file_line($j, 0);

    $email_admin = "
        Purchase received!

        Item sold: $item_id

$contents_list
    ";
    wp_mail('oliver@uncovery.net', 'Hong Kong Rocks Purchase Data', $email_admin);

    $customer_email = "
    Dear {$j['payer']['name']['given_name']} {$j['payer']['name']['surname']},

    Thanks a lot for your purchase.
    We would like to confirm with you the purchase of the following item:

    Item Name: {$i['title']}
    Item ID: $item_id
    Item description: {$i['description']}
    Price: {$j['purchase_units'][0]['amount']['value']} {$j['purchase_units'][0]['amount']['currency_code']}

    We will ship the item to you as soon as possible.

    Thanks a lot for your business

    best regards,

    Hong Kong Rocks
    ";
    wp_mail(array($j['payer']['email_address'], 'oliver@hongkong-rocks.com'), 'Hong Kong Rocks Purchase confirmation', $customer_email);
}

/**
 * support function to add default values to the prints list
 *
 * @global type $UNC_PRINTS
 * @return array
 */
function unc_prints_fix_defaults() {
    global $UNC_PRINTS;

    $defaults = $UNC_PRINTS['defaults'];
    unset($UNC_PRINTS['defaults']);

    foreach ($defaults as $d_key => $d_value) {
        foreach ($UNC_PRINTS as $key => $p_value) {
            if (!isset($p_value[$d_key])) {
                $UNC_PRINTS[$key][$d_key] = $d_value;
            }
        }
    }

    return $UNC_PRINTS;
}


function unc_prints_buy_button($item_id, $sandbox = false) {
    global $UNC_PRINTS;

    $I = $UNC_PRINTS[$item_id];
    $paypal_vars = array(
        'sandbox' => array(
            'paypal_url' => "https://www.sandbox.paypal.com/cgi-bin/webscr",
            'business_email' => 'sb-a4cot2332118@business.example.com', // password = }7w>eN4W
        //  'customer_email' => 'sb-ayvgl2333377@personal.example.com', // passwprd = z!c*-)6E
            'button_id' => '',  // not used?
            'client_id' => '',
            'secret' => '',
        ),
        'operation' => array(
            'paypal_url' => "https://www.paypal.com/cgi-bin/webscr",
            'business_email' => 'oliver@hongkong-rocks.com',
            'client_id' => '',
            'secret' => '',

        ),
    );

    if ($sandbox) {
        $P = $paypal_vars['sandbox'];
    } else {
        $P = $paypal_vars['operation'];
    }

    $button = '
    <p>
    <script src="https://www.paypal.com/sdk/js?currency=HKD&client-id='. $P['client_id'] .'"></script>
    <h2>Buy now:</h2>
    <label>
        <input type="radio" name="frame-option" value="notframed" checked>
        Not Framed: 400 HKD
    </label>
    <label>
        <input type="radio" name="frame-option" value="framed">
        Framed: 500 HKD
    </label>
    <div id="inset_form"></div>
    <div id="paypal-button-container"></div>
    <script>
        var price = 400;
        document.querySelectorAll(\'input[name=frame-option]\').forEach(function(el) {
            el.addEventListener(\'change\', function(event) {

                if (event.target.value === \'notframed\') {
                    price = 400;
                }
                if (event.target.value === \'framed\') {
                    price = 500;
                }
            });
        });

        paypal.Buttons({
            createOrder: function(data, actions) {
                // This function sets up the details of the transaction, including the amount and line item details.
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            custom_id: \''. $item_id . '\',
                            description: \''. urlencode($I['title']) . '\',
                            currency_code: \'HKD\',
                            value: price
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    var post_contents = JSON.stringify(details);
                    post(\'/buy-prints/\', {item_id: \''. $item_id . '\', contents: post_contents, purchased: \'true\'});
                });
            }
        }).render(\'#paypal-button-container\');

        /**
         * sends a request to the specified url from a form. this will change the window location.
         * @param {string} path the path to send the post request to
         * @param {object} params the paramiters to add to the url
         * @param {string} [method=post] the method to use on the form
         */

        function post(path, params, method=\'post\') {

            // The rest of this code assumes you are not using a library.
            // It can be made less wordy if you use one.
            const form = document.createElement(\'form\');
            form.method = method;
            form.action = path;

            for (const key in params) {
                if (params.hasOwnProperty(key)) {
                    const hiddenField = document.createElement(\'input\');
                    hiddenField.type = \'hidden\';
                    hiddenField.name = key;
                    hiddenField.value = params[key];

                    form.appendChild(hiddenField);
                }
            }

            document.body.appendChild(form);
            form.submit();
        }


    </script>
    </p>
    ';

    return $button;
}


function umc_array2file_line($array, $layer, $val_change_func = false) {
    $in_text = umc_array2file_indent($layer);
    $out = "";
    foreach ($array as $key => $value) {
        if ($val_change_func) {
            $value = $val_change_func($key, $value);
        }
        $slash_key = umc_beautify_key($key);
        $out .=  "$in_text$slash_key: ";
        if (is_array($value)) {
            $layer++;
            $out .= "\n"
                . umc_array2file_line($value, $layer,  $val_change_func)
                . "$in_text\n";
            $layer--;
        } else if(is_numeric($value)) {
            $out .= "$value\n";
        } else {
            $out .= "'" . addslashes($value) . "'\n";
        }
    }
    return $out;
}

function umc_beautify_key($string) {
    $fixed = str_replace("_", " ", $string);
    $fixed_2 = ucwords($fixed);
    return $fixed_2;
}


function umc_array2file_indent($layer) {
    $text = '  ';
    $out = '';
    for ($i=0; $i<=$layer; $i++) {
        $out .= $text;
    }
    return $out;
}
