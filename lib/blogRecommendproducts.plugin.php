<?php

class blogRecommendproductsPlugin extends blogPlugin {

    protected static $plugin_id = array('blog', 'recommendproducts');
    protected $recommend = array(
        'status' => 0,
        'route' => '',
        'default_output_products' => 1,
        'title_products' => 'Рекомендуемые товары',
        'count_products' => 3,
        'product_mode' => 'find',
        'set_id' => '',
        'category_filter' => 0,
        'categories' => '',
        'filter' => 0,
        'filters' => '',
        'add_filters' => '',
        'default_output_reviews' => 1,
        'title_reviews' => 'Отзывы о товарах',
        'count_reviews' => 3,
        'product_count_reviews' => 1,
    );

    public function postSave($post) {
        $recommendproducts = waRequest::post('blog_recommendproducts');
        $categories = waRequest::post('categories', array());
        $filters = waRequest::post('filters', array());
        $add_filters = waRequest::post('add_filters', array());
        $recommendproducts['categories'] = json_encode($categories);
        $recommendproducts['filters'] = json_encode($filters);
        $recommendproducts['add_filters'] = json_encode($add_filters);
        $model = new blogRecommendproductsPluginModel();
        $exists = $model->getByField('post_id', $post['id']);
        if ($exists) {
            $model->updateByField('post_id', $post['id'], $recommendproducts);
        } else {
            $recommendproducts['post_id'] = $post['id'];
            $model->insert($recommendproducts);
        }
    }

    public function backendPostEdit($post) {
        if ($this->getSettings('status') && !empty($post['id'])) {
            try {
                $model = new blogRecommendproductsPluginModel();
                $recommend = $model->getByField('post_id', $post['id']);
                if (!$recommend) {
                    $recommend = $this->recommend;
                }
                $recommend['categories'] = $recommend['categories'] ? json_decode($recommend['categories'], true) : array();
                $recommend['filters'] = $recommend['filters'] ? json_decode($recommend['filters'], true) : array();
                $recommend['add_filters'] = $recommend['add_filters'] ? json_decode($recommend['add_filters'], true) : array();

                $filters = $this->getCategoryFilter();
                $sets = $this->getSets();

                $routing = wa()->getRouting();
                $domain_routes = $routing->getByApp('shop');

                $view = wa()->getView();
                $view->assign('recommend', $recommend);
                $view->assign('filters', $filters);
                $view->assign('sets', $sets);
                $view->assign('domain_routes', $domain_routes);

                $html = $view->fetch($this->path . '/templates/BackendPostEdit.html');
                return array('toolbar' => $html);
            } catch (Exception $ex) {
                return array('toolbar' => 'Ошибка. ' . $ex->getMessage());
            }
        }
    }

    public function frontendPost($post) {
        $html = '';
        $app_settings_model = new waAppSettingsModel();
        $model = new blogRecommendproductsPluginModel();
        $recommend = $model->getByField('post_id', $post['id']);
        if ($recommend && $recommend['default_output_products']) {
            $html .= self::displayProducts($post['id']);
        }
        if ($recommend && $recommend['default_output_reviews']) {
            $html .= self::displayReviews($post['id']);
        }

        return array('footer' => $html);
    }

    public static function getPostProducts($post_id) {
        $products = array();
        $app_settings_model = new waAppSettingsModel();
        $model = new blogRecommendproductsPluginModel();
        $recommend = $model->getByField('post_id', $post_id);
        if ($app_settings_model->get(self::$plugin_id, 'status') && $recommend && $recommend['status']) {
            if (!wa()->appExists('shop')) {
                throw new waException('Не установлено приложение Shop-Script');
            }
            $params_key = serialize($recommend);
            $cache_id = md5('blogRecommendproductsPlugin::displayProducts' . $params_key);

            $cache_time = wa()->getConfig()->isDebug() ? 0 : 1800;
            $cache = new waSerializeCache($cache_id, $cache_time);
            if ($cache && $cache->isCached()) {
                $products = $cache->get();
            } else {
                if ($recommend['product_mode'] == 'find') {
                    $recommend['categories'] = json_decode($recommend['categories'], true);
                    $recommend['filters'] = json_decode($recommend['filters'], true);
                    $recommend['add_filters'] = json_decode($recommend['add_filters'], true);
                    wa('shop');
                    $collection = new blogRecommendproductsProductsCollection();
                    $collection->filters($recommend);
                } else {
                    if (!wa()->appExists('shop')) {
                        throw new waException('Не установлено приложение Shop-Script');
                    }
                    wa('shop');
                    $collection = new shopProductsCollection('set/' . $recommend['set_id']);
                }
                $products = $collection->getProducts('*', 0, $recommend['count_products']);
                $products = self::prepareProducts($products, $recommend['route']);

                if ($products && $cache) {
                    $cache->set($products);
                }
            }
        }
        return $products;
    }

    public static function displayProducts($post_id) {
        $app_settings_model = new waAppSettingsModel();
        $model = new blogRecommendproductsPluginModel();
        $recommend = $model->getByField('post_id', $post_id);
        if ($app_settings_model->get(self::$plugin_id, 'status') && $recommend && $recommend['status']) {
            try {
                $products = self::getPostProducts($post_id);

                $view = wa()->getView();
                $view->assign('title_products', $recommend['title_products']);
                $view->assign('products', $products);

                $template_path = wa()->getDataPath('/plugins/recommendproducts/templates/RecommendProducts.html', false, 'blog', true);
                if (!file_exists($template_path)) {
                    $template_path = wa()->getAppPath('/plugins/recommendproducts/templates/RecommendProducts.html', 'blog');
                }
                $html = $view->fetch($template_path);
                return $html;
            } catch (Exception $ex) {
                return 'Ошибка. ' . $ex->getMessage();
            }
        }
    }

    private static function prepareProducts($products, $route) {
        wa('shop');
        $route = explode(':', $route);
        $domain = $route[0];
        $url = $route[1];
        $routing = wa()->getRouting();
        $domain_routes = $routing->getByApp('shop');
        $category_model = new shopCategoryModel();
        $categories = $category_model->getFullTree('id, name, depth, url, full_url', true);
        $routes = $domain_routes[$domain];

        $route = $routing->getRoute();
        foreach ($products as &$product) {
            foreach ($routes as $r) {
                if (empty($r['private']) && $r['url'] == $url && (empty($r['type_id']) || (in_array($product['type_id'], (array) $r['type_id'])))) {
                    $routing->setRoute($r, $domain);
                }
            }
            $params = array('product_url' => $product['url']);
            if ($product['category_id'] && isset($categories[$product['category_id']])) {
                $params['category_url'] = $categories[$product['category_id']]['full_url'];
            }

            $product['frontend_url'] = $routing->getUrl('shop/frontend/product', $params, true);
        }
        $routing->setRoute($route, $domain);
        unset($product);
        return $products;
    }

    public static function displayReviews($post_id) {
        $app_settings_model = new waAppSettingsModel();
        $model = new blogRecommendproductsPluginModel();
        $recommend = $model->getByField('post_id', $post_id);
        if ($app_settings_model->get(self::$plugin_id, 'status') && $recommend && $recommend['status']) {
            try {
                if (!wa()->appExists('shop')) {
                    throw new waException('Не установлено приложение Shop-Script');
                }

                $params_key = serialize($recommend);
                $cache_id = md5('blogRecommendproductsPlugin::displayReviews' . $params_key);
                $cache_time = wa()->getConfig()->isDebug() ? 0 : 1800;

                $cache = new waSerializeCache($cache_id, $cache_time);
                if ($cache && $cache->isCached()) {
                    $reviews = $cache->get();
                } else {
                    wa('shop');
                    $products = self::getPostProducts($post_id);

                    $reviews_model = new shopProductReviewsModel();
                    $reviews = array();
                    foreach ($products as $product) {
                        $_reviews = $reviews_model->getReviews($product['id'], 0, $recommend['product_count_reviews'], 'datetime DESC');
                        if ($_reviews) {
                            foreach ($_reviews as $index => $_review) {
                                $_reviews[$index]['product'] = $product;
                            }
                            $reviews = array_merge($reviews, $_reviews);
                        }
                    }

                    $reviews = array_slice($reviews, 0, $recommend['count_reviews']);

                    if ($reviews && $cache) {
                        $cache->set($reviews);
                    }
                }

                $view = wa()->getView();
                $view->assign('title_reviews', $recommend['title_reviews']);

                $view->assign('reviews', $reviews);
                $template_path = wa()->getDataPath('/plugins/recommendproducts/templates/ProductReviews.html', false, 'blog', true);
                if (!file_exists($template_path)) {
                    $template_path = wa()->getAppPath('/plugins/recommendproducts/templates/ProductReviews.html', 'blog');
                }
                $html = $view->fetch($template_path);
                return $html;
            } catch (Exception $ex) {
                return 'Ошибка. ' . $ex->getMessage();
            }
        }
    }

    protected function getSets() {
        if (!wa()->appExists('shop')) {
            throw new waException('Не установлено приложение Shop-Script');
        }
        wa('shop');
        $set_model = new shopSetModel();
        return $set_model->getAll();
    }

    protected function getCategoryFilter() {
        if (!wa()->appExists('shop')) {
            throw new waException('Не установлено приложение Shop-Script');
        }
        wa('shop');
        $feature_model = new shopFeatureModel();
        $features = $feature_model->getFeatures(true, null, 'id');

        $collection = new shopProductsCollection();

        $filter_ids = array('price');
        foreach ($features as $feature) {
            $filter_ids[] = $feature['id'];
        }

        $feature_model = new shopFeatureModel();
        $features = $feature_model->getById(array_filter($filter_ids, 'is_numeric'));
        if ($features) {
            $features = $feature_model->getValues($features);
        }
        $category_value_ids = $collection->getFeatureValueIds();

        $filters = array();
        foreach ($filter_ids as $fid) {
            if ($fid == 'price') {
                $range = $collection->getPriceRange();
                if ($range['min'] != $range['max']) {
                    $filters['price'] = array(
                        'min' => shop_currency($range['min'], null, null, false),
                        'max' => shop_currency($range['max'], null, null, false),
                    );
                }
            } elseif (isset($features[$fid]) && isset($category_value_ids[$fid])) {
                $filters[$fid] = $features[$fid];
                $min = $max = $unit = null;
                foreach ($filters[$fid]['values'] as $v_id => $v) {
                    if (!in_array($v_id, $category_value_ids[$fid])) {
                        unset($filters[$fid]['values'][$v_id]);
                    } else {
                        if ($v instanceof shopRangeValue) {
                            $begin = $this->getFeatureValue($v->begin);
                            if ($min === null || $begin < $min) {
                                $min = $begin;
                            }
                            $end = $this->getFeatureValue($v->end);
                            if ($max === null || $end > $max) {
                                $max = $end;
                                if ($v->end instanceof shopDimensionValue) {
                                    $unit = $v->end->unit;
                                }
                            }
                        } else {
                            $tmp_v = $this->getFeatureValue($v);
                            if ($min === null || $tmp_v < $min) {
                                $min = $tmp_v;
                            }
                            if ($max === null || $tmp_v > $max) {
                                $max = $tmp_v;
                                if ($v instanceof shopDimensionValue) {
                                    $unit = $v->unit;
                                }
                            }
                        }
                    }
                }
                if (!$filters[$fid]['selectable'] && ($filters[$fid]['type'] == 'double' ||
                        substr($filters[$fid]['type'], 0, 6) == 'range.' ||
                        substr($filters[$fid]['type'], 0, 10) == 'dimension.')) {
                    if ($min == $max) {
                        unset($filters[$fid]);
                    } else {
                        $type = preg_replace('/^[^\.]*\./', '', $filters[$fid]['type']);
                        if ($type != 'double') {
                            $filters[$fid]['base_unit'] = shopDimension::getBaseUnit($type);
                            $filters[$fid]['unit'] = shopDimension::getUnit($type, $unit);
                            if ($filters[$fid]['base_unit']['value'] != $filters[$fid]['unit']['value']) {
                                $dimension = shopDimension::getInstance();
                                $min = $dimension->convert($min, $type, $filters[$fid]['unit']['value']);
                                $max = $dimension->convert($max, $type, $filters[$fid]['unit']['value']);
                            }
                        }
                        $filters[$fid]['min'] = $min;
                        $filters[$fid]['max'] = $max;
                    }
                }
            }
        }

        return $filters;
    }

    protected function getFeatureValue($v) {
        if ($v instanceof shopDimensionValue) {
            return $v->value_base_unit;
        }
        if (is_object($v)) {
            return $v->value;
        }
        return $v;
    }

}
