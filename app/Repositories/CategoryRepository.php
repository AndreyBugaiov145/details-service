<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\ParsingSetting;
use Illuminate\Database\Query\JoinClause;

class CategoryRepository
{
    const CHILD_TYPE_YEAR = 'year';
    const CHILD_TYPE_MODEL = 'model';

    public static function getLastChildrenCategories($category_title, $year, $car_models = '')
    {

        if (strlen($car_models)) {
            $category = Category::where('title', $year)
                ->whereHas('parent', function ($query) use ($category_title) {
                    $query->where('title', $category_title);
                })
                ->with('children', function ($query) use ($car_models) {
                    $query->whereIn('title', explode(',', $car_models))->withAllChildren();
                })
                ->first();
            $categories = self::getLastChildren([$category->toArray()]);
        } else {
            $category = Category::where('title', $year)
                ->whereHas('parent', function ($query) use ($category_title) {
                    $query->where('title', $category_title);
                })
                ->withAllChildren()
                ->first();
            $categories = self::getLastChildren([$category->toArray()]);
        }

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
                $join->on('parsing_settings.brand', 'like', 'categories.title')
                    ->where('parsing_settings.is_show', true)
                    ->whereNull('deleted_at');
            })->get();

        return $categories->unique('id');
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
        $parentMainCategory = Category::select(['title', 'id'])->find($id);
        if (is_null($parentMainCategory)) {
            return collect();
        }

        $categories = Category::where('parent_id', $id)->orderByDesc('title')->get();
        $parsingSetting = ParsingSetting::where('brand', $parentMainCategory->title)->where('parsing_settings.is_show', true)->get()->pluck('year')->toArray();
//        $categories = Category::where('parent_id', $id)->select(['categories.title', 'categories.id'])
//            ->join('parsing_settings', function (JoinClause $join) use ($parentMainCategory) {
//                $join->on('parsing_settings.year', 'categories.title')
//                    ->where('parsing_settings.brand', 'like', $parentMainCategory->title)
//                    ->where('parsing_settings.is_show', true);
//            })->orderByDesc('title')->get();

        $categories = $categories->unique('id')
            ->filter(function ($item) use ($parsingSetting) {
            $item->child_type = self::CHILD_TYPE_MODEL;
            return  in_array($item->title,$parsingSetting);
        })
            ->map(function ($item) {
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
        $categories = Category::select(['title', 'id'])->where('parent_id', $id)->get();

        $parsingSetting = ParsingSetting::where([
            ['brand', 'like', $parentMainCategory->title],
            ['year', 'like', $parentMainYarCategory->title],
        ])->where('parsing_settings.is_show', true)->first();
        if (is_null($parsingSetting)) {
            return collect();
        }

        if (strlen($parsingSetting->car_models)) {
            $categories = $categories->filter(function ($category, $key) use ($parsingSetting) {
                return str_contains($parsingSetting->car_models, $category->title);
            })->each(function ($item) {
                return $item;
            });
        }

        $categoriesData = collect();
        $categories->unique('id')->each(function ($item) use ($categoriesData) {
            $categoriesData->add($item);
        });

        return $categoriesData;
    }

    public static function getChildCategories($id)
    {
        $categories = Category::select(['title', 'id'])->where('parent_id', $id)->orderByDesc('title')->get();

        return $categories;
    }

}
