<?php

namespace App\Enums;

enum TrackingProvider: string
{
    case GoogleAnalytics = 'google_analytics';
    case FacebookPixel = 'facebook_pixel';
    case Gtm = 'gtm';
    case Custom = 'custom';
}
