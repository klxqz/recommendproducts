<?php

class blogRecommendproductsProductsCollection extends shopProductsCollection {

    public function filters($recommend) {
        if ($this->filtered) {
            return;
        }

        if ($recommend['filter'] && isset($recommend['filters']) && is_array($recommend['filters'])) {
            parent::filters($recommend['filters']);
        }


        $categories = array_keys($recommend['categories']);
        if ($recommend['category_filter'] && $categories) {
            $this->where[] = "(p.category_id IN ('" . implode("','", $categories) . "') OR p.id IN (SELECT product_id FROM `shop_category_products` WHERE category_id IN ('" . implode("','", $categories) . "')))";
        }

        if ($recommend['filter'] && isset($recommend['add_filters']['rating'])) {
            $this->where[] = "p.rating >= '" . (int) $recommend['add_filters']['rating'] . "'";
        }
        if ($recommend['filter'] && isset($recommend['add_filters']['compare_price'])) {
            $this->where[] = "p.compare_price > 0";
        }

        $this->filtered = true;
    }

}
