<?php
echo '<!-- /path/to/template.php -->' . PHP_EOL;

foreach ($args ?? [] as $key => $value) {
    printf('<p>%s:%s</p>' . PHP_EOL, $key, $value);
}
