<?php

class blogRecommendproductsPluginBackendSettingsAction extends waViewAction {

    protected $templates = array(
        'FrontendCheckout' => array(
            'name' => 'Шаблон вывода рекомендуемых товаров',
            'path' => 'plugins/recommendproducts/templates/RecommendProducts.html',
            'change_tpl' => false,
        ),
        'FrontendDescription' => array(
            'name' => 'Шаблон вывода отзывов',
            'path' => 'plugins/recommendproducts/templates/ProductReviews.html',
            'change_tpl' => false,
        ),
    );
    protected $plugin_id = array('blog', 'recommendproducts');

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get($this->plugin_id);
        $this->view->assign('settings', $settings);

        foreach ($this->templates as &$template) {
            $template_path = wa()->getDataPath($template['path'], false, 'blog', true);
            if (file_exists($template_path)) {
                $template['change_tpl'] = true;
            } else {
                $template_path = wa()->getAppPath($template['path'], 'blog');
            }
            $template['content'] = file_get_contents($template_path);
        }
        unset($template);
        $this->view->assign('templates', $this->templates);
    }

}
