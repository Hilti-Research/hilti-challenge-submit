<?php

use App\Kernel;

ini_set('upload_max_filesize', '22M');
ini_set('post_max_size', '50M');

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context): Kernel {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
