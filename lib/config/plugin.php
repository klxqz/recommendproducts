<?php

return array(
    'name' => 'Рекомендуемые товары',
    'description' => 'Вывод рекомендуемых товаров в блоге',
    'vendor' => '985310',
    'version' => '1.1.4',
    'img' => 'img/recommendproducts.png',
    'frontend' => true,
    'blog_settings' => true,
    'handlers' => array(
        'backend_post_edit' => 'backendPostEdit',
        'frontend_post' => 'frontendPost',
        'post_save' => 'postSave',
    ),
);
//EOF
