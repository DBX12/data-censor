<?php
/*
 * Example usage of the data censor tool
 * -------------------------------------
 * Assume, the $inputData is the response of an API request (yes, the design of the "API response" is horrible)
 *
 */

require_once __DIR__ . '/vendor/autoload.php';

use dbx12\jsonCensor\Censor;
use dbx12\jsonCensor\censorStrategies\ConstantCensorStrategy;
use dbx12\jsonCensor\censorStrategies\HashCensorStrategy;

$inputData = [
    'users'     => [
        [
            'name'       => 'john',
            'role'       => 'Admin',
            'email'      => 'john.doe@example.org',
            'moneySpent' => 0,
        ],
        [
            'name'       => 'jane',
            'role'       => 'Customer',
            'email'      => 'jane.doe@example.org',
            'moneySpent' => 100,
        ],
        [
            'name'       => 'alex',
            'role'       => 'Subscriber',
            'email'      => 'alex.doe@example.org',
            'moneySpent' => 0,
        ],
    ],
    'purchases' => [
        [
            'customerName' => 'jane',
            'items'        => ['apple' => 5, 'banana' => 2],
        ],
    ],
];

$censor = new Censor();

// As first measure, we remove the email address of any user. We set [] as condition, which means "always matching"
$censor->addRule('.users.email', [], ConstantCensorStrategy::class);

// As next step, we obfuscate the username, but only if the user has the role Customer or Subscriber. Hashing it with md5 should be
// sufficient and allows us to connect the purchases later on.
$censor->addRule('.users.name', ['role' => ['Customer', 'Subscriber']], HashCensorStrategy::class);

// The amount the user spent on the page should be a secret as well. But only Customers can spent money, so we will
// filter for them to save some processing cycles.
$censor->addRule('.users.moneySpent', ['role' => 'Customer']);

// We still want to be able to see how many purchases per customer there are. So we obfuscate the customerName of every
// purchase the same way as we did above. This will result in identical hashes.
$censor->addRule('.purchases.customerName', [], HashCensorStrategy::class);

// You maybe think, that the items jane purchased should be secret as well and only the quantity listed. You could use
// the HashCensorStrategy for that, but that would obfuscate the values as well. So I commented that out.
// If you need that functionality, you might want to add an own strategy for that? ;)
# $censor->addRule('.purchases.items',[],HashCensorStrategy::class);

#echo json_encode($censor->censor($inputData), JSON_PRETTY_PRINT);
echo json_encode(($inputData), JSON_PRETTY_PRINT);

