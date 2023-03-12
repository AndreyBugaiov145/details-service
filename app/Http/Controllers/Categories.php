<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\ApiResponseServices;
use Illuminate\Http\Request;

class Categories extends Controller
{
    public function getMainCategories()
    {
        $categories = CategoryRepository::getMainCategories();

        $categories = $categories->map(function ($item) {
            $item->child_type = CategoryRepository::CHILD_TYPE_YEAR;
            return $item;
        });

        return ApiResponseServices::successCustomData($categories->toArray());
    }

    public function getChildrenCategories(Request $request,$id)
    {
        $categories = CategoryRepository::getChildrenCategories($id,$request->get('child_type'));

        return ApiResponseServices::successCustomData($categories->toArray());
    }

    public function getChildMainModelCategories($id)
    {
        $categories = Category::where('parent_id', $id)->get();

        return ApiResponseServices::successCustomData($categories->toArray());
    }

    public function getCategory($id)
    {
        $categories = Category::find($id);

        return ApiResponseServices::successCustomData(optional($categories->toArray()));
    }
}
