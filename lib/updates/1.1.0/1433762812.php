<?php

$model = new waModel();

try {
    $sql = "SELECT `product_mode` FROM `blog_recommendproducts` WHERE 0";
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = "ALTER TABLE `blog_recommendproducts` ADD `product_mode` ENUM( 'list', 'find' ) NOT NULL DEFAULT 'list' AFTER `count_products`";
    $model->query($sql);
}

try {
    $sql = "SELECT `set_id` FROM `blog_recommendproducts` WHERE 0";
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = "ALTER TABLE `blog_recommendproducts` ADD `set_id` VARCHAR( 64 ) NOT NULL AFTER `count_products`";
    $model->query($sql);
}

