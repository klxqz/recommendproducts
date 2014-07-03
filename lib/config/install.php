<?php

$plugin_id = array('blog', 'recommendproducts');
$app_settings_model = new waAppSettingsModel();
$app_settings_model->set($plugin_id, 'status', '1');
