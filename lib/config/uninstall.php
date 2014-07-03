<?php

$model = new waModel();
try {
    $model->exec("DROP TABLE `blog_recommend_products`");
} catch (waDbException $e) {
    
}


