<?php

namespace App\Enums;

enum AiDataSourceType: string
{
    case Url = 'url';
    case Pdf = 'pdf';
    case Faq = 'faq';
    case HelpCenter = 'help_center';
}
