<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{

    public static function getLastChildrenCategories($category_title)
    {
        $category = Category::where('title', $category_title)->withAllChildren()->first();
        $categories = self::getLastChildren([$category->toArray()]);

        return $categories;
    }

    protected static function getLastChildren(array $categoriesArray)
    {
        $categories = [];
        foreach ($categoriesArray as $category) {
            if (count($category['children'])) {
                $result = self::getLastChildren($category['children']);
                $categories = array_merge($categories, $result);
            } else {
                $categories[] = $category;
            }
        }

        return $categories;
    }

}
