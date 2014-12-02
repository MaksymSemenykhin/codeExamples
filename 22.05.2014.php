   <?php
   /*
     * delete links product-to-category which have
     * category to keyword links that don't exist
     * product to keyword links that don't exist
     * keywords that don't exist
     * */
    public static function removeProductCategoryLinks($merchant = null, $product_id = null){
        if(!$merchant && Zend_Registry::isRegistered('merchant')){
            $merchant = Zend_Registry::get('merchant');
        }
        $db = Zend_Registry::isRegistered('db')?Zend_Registry::get('db'):null;
        if (!$db){
            return false;
        }

        //get product categories links based on common keywords which are:
        //built on category_keyword link that doesn't exist anymore OR
        //built on product_keyword link that doesn't exist anymore OR
        //built on keyword that doesn't exist anymore
        //Notice: keyword table is joined using existing links to category_keyword or product_keyword tables,
        //record will be deleted if no corresponding category_keyword or product_keyword link exist or any of them exists but linked to keyword that doesn't exist

        $sql  =' SELECT DISTINCT pc.id as `id` FROM product_category pc';
        $sql .= ' LEFT JOIN '.self::TABLE_CATEGORY_KEYWORD . ' ck ON ck.category_id = pc.category_id';
        $sql .= ' LEFT JOIN '. self::TABLE_PRODUCT_KEYWORD . ' pk ON pk.product_id  = pc.product_id AND pk.keyword_id  = ck.keyword_id ';
        $sql .= ' WHERE (pc.type = '.[!!!]_Product_Category::TYPE_WEAK.') AND (ck.id IS NULL OR pk.id IS NULL)';
        if ($merchant && $merchant->getId()){
            $sql .=  'AND (pc.merchant_id = '.$merchant->getId().')';
        }
        if ($product_id){
            $sql .=  'AND (pc.product_id = '.$product_id.')';
        }
        $product_category_all = $db->fetchAll($sql);
        $product_category_all = self::arrayKey($product_category_all,'id', false);

        $sql = '';
        $sql .='SELECT
                DISTINCT
                    pc.id as `id`
                FROM
                    product_category pc
                INNER JOIN category_keyword ck ON ck.category_id = pc.category_id
                INNER JOIN product_keyword pk ON pk.product_id = pc.product_id AND pk.keyword_id = ck.keyword_id
                WHERE (pc.type = '.[!!!]_Product_Category::TYPE_WEAK.')';
        if ($merchant && $merchant->getId()){
            $sql .=  ' AND (pc.merchant_id = '.$merchant->getId().')';
        }
        if ($product_id){
            $sql .=  ' AND (pc.product_id = '.$product_id.')';
        }

        $product_category_need_to_save = $db->fetchAll($sql);
        $product_category_need_to_save = self::arrayKey($product_category_need_to_save,'id', false);
        $product_category_do_delete = array_diff_assoc($product_category_all, $product_category_need_to_save);

        if(empty($product_category_do_delete))return true;
        foreach($product_category_do_delete as $key => $value){
            $product_category_do_delete[$key]=$key;
        }
        $sql = ' DELETE FROM product_category  WHERE id IN('.implode(',',$product_category_do_delete).')';
        $db->query($sql);

        return true;
    }
