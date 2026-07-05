<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Uji coba: ambil berita dari berita.kendarikota.go.id lewat layanan
    // scraper (IP residensial), karena IP VPS diblokir WAF Cloudflare situs itu.
    // Provider-agnostic — cukup ubah param sesuai layanan yang dipakai.
    // Jika 'scraper_endpoint' kosong, route menembak WordPress langsung
    // (berguna saat tes dari laptop yang IP-nya lolos).
    //
    // Contoh .env per layanan (semua punya free-tier):
    //   ScraperAPI : ENDPOINT=https://api.scraperapi.com/  KEY_PARAM=api_key  EXTRA="premium=true"
    //   ScrapingBee: ENDPOINT=https://app.scrapingbee.com/api/v1/  KEY_PARAM=api_key  EXTRA="stealth_proxy=true"
    //   ZenRows    : ENDPOINT=https://api.zenrows.com/v1/  KEY_PARAM=apikey  EXTRA="premium_proxy=true"
    //   Scrape.do  : ENDPOINT=https://api.scrape.do/  KEY_PARAM=token  EXTRA="super=true"
    'berita' => [
        'scraper_endpoint' => env('BERITA_SCRAPER_ENDPOINT'),
        'scraper_key' => env('BERITA_SCRAPER_KEY'),
        'scraper_key_param' => env('BERITA_SCRAPER_KEY_PARAM', 'api_key'),
        'scraper_url_param' => env('BERITA_SCRAPER_URL_PARAM', 'url'),
        'scraper_extra' => env('BERITA_SCRAPER_EXTRA', ''),
    ],

];
