<?php

$class = glob('storage/runtime/compiled_classes/CustomFieldBehavior*.php')[0]??null;
if ($class !== null) {
    copy($class, 'phpstan_stubs.php');
}
