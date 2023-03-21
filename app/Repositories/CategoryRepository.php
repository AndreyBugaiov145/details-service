<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\ParsingSetting;
use Illuminate\Database\Query\JoinClause;

class CategoryRepository
{
    const CHILD_TYPE_YEAR = 'year';
    const CHILD_TYPE_MODEL = 'model';

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

    public static function getMainCategories()
    {
        $categories = Category::where('parent_id', 0)->select(['categories.title', 'categories.id'])
            ->join('parsing_settings', function (JoinClause $join) {
                $join->on('parsing_settings.brand', 'like', 'categories.title')->where('parsing_settings.is_show', true);
            })->get();

        $categories = $categories->unique('id')->map(function ($item) {
            $item->child_type = self::CHILD_TYPE_YEAR;
            return $item;
        });

        return $categories;
    }

    public static function getChildrenCategories($id, $child_type = null)
    {
        if ($child_type == self::CHILD_TYPE_YEAR) {
            return self::getChildMainYearsCategories($id);
        } else if ($child_type == self::CHILD_TYPE_MODEL) {
            return self::getChildMainModelsCategories($id);
        } else {
            return self::getChildCategories($id);
        }
    }

    public static function getChildMainYearsCategories($id)
    {
        $parentMainCategory = Category::select(['title','id'])->find($id);
        if (is_null($parentMainCategory)) {
            return collect();
        }

        $categories = Category::where('parent_id', $id)->select(['categories.title', 'categories.id'])
            ->join('parsing_settings', function (JoinClause $join) use ($parentMainCategory) {
                $join->on('parsing_settings.year', 'categories.title')
                    ->where('parsing_settings.brand', 'like', $parentMainCategory->title)
                    ->where('parsing_settings.is_show', true);
            })->get();

        $categories = $categories->unique('id')->$categories->map(function ($item) {
            $item->child_type = self::CHILD_TYPE_MODEL;
            return $item;
        });


        return $categories;
    }

    public static function getChildMainModelsCategories($id)
    {
        $parentMainYarCategory = Category::select(['title', 'parent_id'])->find($id);
        if (is_null($parentMainYarCategory)) {
            return collect();
        }
        $parentMainCategory = Category::find($parentMainYarCategory->parent_id);
        if (is_null($parentMainYarCategory)) {
            return collect();
        }
        $categories = Category::select(['title','id'])->where('parent_id', $id)->get();

        $parsingSetting = ParsingSetting::where([
            ['brand', 'like', $parentMainCategory->title],
            ['year', 'like', $parentMainYarCategory->title],
        ])->where('parsing_settings.is_show', true)->first();
        if (is_null($parsingSetting)) {
            return collect();
        }

        $categories = $categories->filter(function ($category, $key) use ($parsingSetting) {
            return str_contains($parsingSetting->car_models, $category->title);
        });

        return $categories->unique('id');
    }

    public static function getChildCategories($id)
    {
        $categories = Category::select(['title','id'])->where('parent_id', $id)->get();

        return $categories;
    }

}