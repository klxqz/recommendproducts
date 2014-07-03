<?php

class blogRecommendproductsPluginBackendSaveSettingsController extends waJsonController {

    protected $templates = array(
        'FrontendCheckout' => array(
            'path' => 'plugins/recommendproducts/templates/RecommendProducts.html',
        ),
        'FrontendDescription' => array(
            'path' => 'plugins/recommendproducts/templates/ProductReviews.html',
        ),
    );
    protected $plugin_id = array('blog', 'recommendproducts');

    public function execute() {
        try {
            $app_settings_model = new waAppSettingsModel();
            $settings = waRequest::post('blog_recommendproducts');

            foreach ($settings as $name => $value) {
                $app_settings_model->set($this->plugin_id, $name, $value);
            }

            $post_templates = waRequest::post('templates');
            $reset_tpls = waRequest::post('reset_tpls');

            foreach ($this->templates as $id => $template) {
                if (isset($reset_tpls[$id])) {
                    $template_path = wa()->getDataPath($template['path'], false, 'blog', true);
                    @unlink($template_path);
                } else {

                    if (!isset($post_templates[$id])) {
                        throw new waException('Не определён шаблон');
                    }
                    $post_template = $post_templates[$id];

                    $template_path = wa()->getDataPath($template['path'], false, 'blog', true);
                    if (!file_exists($template_path)) {
                        $template_path = wa()->getAppPath($template['path'], 'blog');
                    }

                    $template_content = file_get_contents($template_path);
                    if ($template_content != $post_template) {
                        $template_path = wa()->getDataPath($template['path'], false, 'blog', true);

                        $f = fopen($template_path, 'w');
                        if (!$f) {
                            throw new waException('Не удаётся сохранить шаблон. Проверьте права на запись ' . $template_path);
                        }
                        fwrite($f, $post_template);
                        fclose($f);
                    }
                }
            }
            $this->response['message'] = "Сохранено";
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}
