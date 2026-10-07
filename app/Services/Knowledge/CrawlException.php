<?php

namespace App\Services\Knowledge;

use RuntimeException;

/** A problem with a data source that is safe to show to the workspace owner as-is. */
class CrawlException extends RuntimeException
{
}
