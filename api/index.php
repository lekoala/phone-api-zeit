<?php

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;
use libphonenumber\geocoding\PhoneNumberOfflineGeocoder;

require_once __DIR__ . '/../vendor/autoload.php';

$number = $_GET['number'] ?? null;
$region = $_GET['region'] ?? null;
$locale = $_GET['locale'] ?? 'en_US';

// With international numbers, no need for region
// that's %2B encoded if needed...
if (strpos($number, '+') === 0) {
    $region = null;
}
if ($region) {
    // make sure we have uppercased region
    $region = strtoupper($region);
}

$data = [];

if (!$number) {
    $data = [
        'error' => true,
        'message' => "No parameters provided.",
        'availableParameters' => [
            'number' => "The number you want to format.",
            'region' => "The region code you want to format.",
        ]
    ];
} else {
    $phoneUtil = PhoneNumberUtil::getInstance();
    try {
        $proto = $phoneUtil->parse($number, $region, null, true);
        $geocoder = PhoneNumberOfflineGeocoder::getInstance();

        $data = [
            'success' => true,
            'result' => [
                'countryCode' => $proto->getCountryCode(),
                'rawNumber' => $proto->getNationalNumber(),
                'nationalNumber' => $phoneUtil->format($proto, PhoneNumberFormat::NATIONAL),
                'internationalNumber' => $phoneUtil->format($proto, PhoneNumberFormat::INTERNATIONAL),
                // same as international, without space
                'e164Number' => $phoneUtil->format($proto, PhoneNumberFormat::E164),
                'extension' => $proto->getExtension(),
                'italianLeadingZero' => $proto->hasItalianLeadingZero(),
                'numberOfLeadingZeros' => $proto->getNumberOfLeadingZeros(),
                'rawInput' => $proto->getRawInput(),
                'countryCodeSource' => $proto->getCountryCodeSource(),
                'preferredDomesticCarrierCode' => $proto->getPreferredDomesticCarrierCode(),
                'numberType' => $phoneUtil->getNumberType($proto),
                'description' => $geocoder->getDescriptionForNumber($proto, $locale),
            ],
            'data' => [
                'number' => $number,
                'region' => $region
            ]
        ];
    } catch (NumberParseException $e) {
        $data = [
            'error' => true,
            'message' => $e->getMessage(),
            'data' => [
                'number' => $number,
                'region' => $region
            ]
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
