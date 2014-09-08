<?php

return array(
    'blog_recommend_products' => array(
        'post_id' => array('int', 11, 'null' => 0),
        'status' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'route' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'default_output_products' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'title_products' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'count_products' => array('int', 11, 'null' => 0),
        'category_filter' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'categories' => array('text'),
        'filter' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'filters' => array('text'),
        'add_filters' => array('text'),
        'default_output_reviews' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'title_reviews' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'count_reviews' => array('int', 11, 'null' => 0),
        'product_count_reviews' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'post_id' => array('post_id', 'unique' => 1),
        ),
    ),
);
