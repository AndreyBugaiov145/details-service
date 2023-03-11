<?php

namespace App\Services;

use App\Exceptions\GrabberException;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Detail;
use App\Models\DetailAnalogue;
use App\Models\ParsingSetting;
use Arr;
use ArrayObject;
use Carbon\Carbon;
use Log;
use phpDocumentor\Reflection\Types\Boolean;

class DetailService
{

    public $grabber;
    protected $currency_id;
    protected $parsingSetting;
    protected $attempts = 0;
    protected $max_attempts = 25;
    protected $i = 0;
    public $request_count = 0;
    public $detailsData = [];

    public function __construct(ParsingSetting $parsingSetting)
    {
        $this->grabber = new FetchingService();
        $this->parsingSetting = $parsingSetting;
        $this->currency_id = Currency::where('code', Currency::UAH_CODE)->first()->id;
    }

    public function fetchCategoriesAndDetailsInfo()
    {
        Log::info('Start fetching');
        try {
            $this->attempts = 0;
            $mainCategoriesData = $this->fetchMainCategories();
            Log::info('fetched mainCategoriesData', $mainCategoriesData);

            $this->attempts = 0;
            $mainYearsCategoriesData = $this->fetchMainYearsCategories($mainCategoriesData);
            $this->request_count++;
            Log::info('fetched mainYearsCategoriesData', $mainYearsCategoriesData
            );
            $this->attempts = 0;
            $carModelsCategoriesData = $this->fetchCarModels($mainYearsCategoriesData);
            $this->request_count++;
            Log::info('fetched carModelsCategoriesData', $carModelsCategoriesData);

            $this->fetchChildCategories($carModelsCategoriesData);
            $this->attempts = 0;

            $this->parsingSetting->category_parsing_status = ParsingSetting::STATUS_SUCCESS;
            $this->parsingSetting->category_parsing_at = Carbon::now();
            Log::info('finish fetching categories');

            Log::info('start fetching details.', $this->detailsData);
            $result = $this->saveDetails($this->array2Dto1DAndAddUid($this->detailsData, false));
            if ($result) {
                $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_SUCCESS;
                $this->parsingSetting->detail_parsing_at = Carbon::now();

                $details = $this->getDetails($this->detailsData);
                $analogyDetailsData = $this->fetchAnalogyDetails($details);
                if ($this->saveAnalogyDetails($analogyDetailsData)) {
                    Detail::whereIn('id', array_unique(array_map(function ($item) {
                        return $item['detail_id'];
                    }, $analogyDetailsData)))->update(['is_parsing_analogy_details' => true]);
                };
            }
        } catch (\Exception $e) {
            $this->parsingSetting->category_parsing_status = ParsingSetting::STATUS_FAIL;
            $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_FAIL;
            $this->parsingSetting->category_parsing_at = Carbon::now();
            $this->parsingSetting->detail_parsing_at = Carbon::now();

            Log::critical($e->getMessage(), $e->getTrace());
        } finally {
            $this->parsingSetting->save();
        }

        Log::info('fetching finish');
    }

    public function fetchDetailsInfo($categoriesData)
    {
        Log::info('Start fetching Details Only');
        try {
            $this->fetchChildCategories($categoriesData);
            $this->attempts = 0;
            Log::info('fetched Details Only ', $this->detailsData);

            $result = $this->saveDetails($this->array2Dto1DAndAddUid($this->detailsData, false));
            if ($result) {
                $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_SUCCESS;
                $this->parsingSetting->detail_parsing_at = Carbon::now();
                $this->parsingSetting->save();

                $details = $this->getDetails($this->detailsData);
                $analogyDetailsData = $this->fetchAnalogyDetails($details);
                if ($this->saveAnalogyDetails($analogyDetailsData)) {
                    Detail::whereIn('id', array_unique(array_map(function ($item) {
                        return $item['detail_id'];
                    }, $analogyDetailsData)))->update(['is_parsing_analogy_details' => true]);
                };
            }
        } catch (\Exception $e) {
            $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_FAIL;
            $this->parsingSetting->detail_parsing_at = Carbon::now();
            $this->parsingSetting->save();

            Log::critical($e->getMessage(), $e->getTrace());
        }

        Log::info('fetching finish');
    }

    public function array2Dto1DAndAddUid(array $data, bool $addUid = true): array
    {
        $uidArr = [];
        $result = [];
        if (is_array($data)) {
            foreach ($data as $category) {
                if (is_array($category)) {
                    foreach ($category as $item) {
                        do {
                            $uid = \Str::random(25);
                        } while (in_array($uid, $uidArr));
                        $uidArr[] = $uid;

                        $newItem = $item;
                        if ($addUid) {
                            $newItem['uid'] = $uid;
                        }
                        $result[] = $newItem;
                    }
                }
            }
        }

        return $result;
    }

    protected function fetchMainCategories()
    {
        Log::info('start fetching main categories');
        do {
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching main categories. attempts > $this->attempts");
            }
            $html = $this->grabber->getMainPage();
            $this->attempts++;
        } while (!$html);
        $parser = new ParserService($html);
        $categoriesData = $parser->getAllChildCategoriesWithJns();
        unset($parser);


        $sliceCategoriesData = array_filter($categoriesData, function ($item) {
            return $item['title'] == $this->parsingSetting->brand;
        });
        $this->saveCategory($sliceCategoriesData);

        Log::info('main categories data', $sliceCategoriesData);
        return $sliceCategoriesData;
    }

    protected function fetchMainYearsCategories(array $data)
    {
        Log::info('start fetching years by main categories.count requests = ' . count($data));
        $categoriesData = [];
        $result = $this->fetchRequestCategories($data);
        Log::info('fetching years by main categories result', $result);

        if ($this->attempts > $this->max_attempts) {
            throw new GrabberException("Failed fetchMainYearsCategories. attempts > $this->attempts");
            Log::critical('Faeil max_attempts', $data);
        }
        do {
            $this->attempts++;
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching main categories. attempts > $this->attempts");
            }
            $rez = $this->fetchRequestCategories($result['rejected']);
            $result['rejected'] = $rez['rejected'];
            $result['success'] = array_replace($result['success'], $rez['success']);
            Log::info(' fetching child categories rejected count' . count($result['rejected']));
        } while (count($result['rejected']));

        foreach ($result['success'] as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    return $item['title'] == $key;
                });
            } else {
                $sliceCategoriesData = $this->parseAndSaveYearsCategoryItems([
                    'data' => $data,
                    'key' => $key,
                    'responseArr' => $responseArr,
                ]);

                $categoriesData[] = $sliceCategoriesData;
            }
        }
        Log::info('finish fetching years by main categories', $categoriesData);

        return $categoriesData;
    }

    protected function fetchCarModels(array $data)
    {
        Log::info('start fetching  Car Models categories.count requests = ' . count($data));
        $data = $this->array2Dto1DAndAddUid($data);
        $categoriesData = [];

        $result = $this->fetchRequestCategories($data);
        Log::info('start fetching  Car Models categories result', $result);

        if ($this->attempts > $this->max_attempts) {
            throw new GrabberException("Failed fetchMainYearsCategories. attempts > $this->attempts");
            Log::critical('Faeil max_attempts', $data);
        }

        do {
            $this->attempts++;
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching main categories. attempts > $this->attempts");
            }
            $rez = $this->fetchRequestCategories($result['rejected']);
            $result['rejected'] = $rez['rejected'];
            $result['success'] = array_replace($result['success'], $rez['success']);
            Log::info(' fetching child categories rejected count' . count($result['rejected']));
        } while (count($result['rejected']));

        foreach ($result['success'] as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    return $item['uid'] == $key;
                });
            } else {
                $sliceCategoriesData = $this->parseAndSaveCarModelsCategoryItems([
                    'data' => $data,
                    'key' => $key,
                    'responseArr' => $responseArr,
                ]);

                $categoriesData[] = $sliceCategoriesData;
            }
        }

        Log::info('finish fetching years by main categories', $categoriesData);

        return $categoriesData;
    }

    protected function parseAndSaveYearsCategoryItems(array $info): array
    {
        $html = $this->getCategoryHtmlFromStream($info['responseArr']['value']);
        $item = Arr::first($info['data'], function ($item) use ($info) {
            return $item['title'] == $info['key'];
        });
        $parser = new ParserService($html);
        $categories = $parser->getAllChildCategoriesWithJns();
        unset($parser);

        $sliceCategoriesData = array_filter($categories, function ($category) use ($item, $info) {
            return intval($category['title']) == intval($this->parsingSetting->year);
        });

        $sliceCategoriesData = array_map(function ($category) use ($item) {
            $category['parent_id'] = $item['id'];
            return $category;
        }, $sliceCategoriesData);


        $this->saveCategory($sliceCategoriesData);

        return $sliceCategoriesData;
    }

    protected function parseAndSaveCarModelsCategoryItems(array $info): array
    {
        $html = $this->getCategoryHtmlFromStream($info['responseArr']['value']);
        $item = Arr::first($info['data'], function ($item) use ($info) {
            return $item['uid'] == $info['key'];
        });
        $parser = new ParserService($html);
        $categories = $parser->getAllChildCategoriesWithJns();
        unset($parser);

        $sliceCategoriesData = array_filter($categories, function ($category) use ($item, $info) {
            if (is_null($this->parsingSetting->car_models) || $this->parsingSetting->car_models == '') {
                return true;
            }
            return in_array($category['title'], explode(',', $this->parsingSetting->car_models));
        });
        $sliceCategoriesData = array_map(function ($category) use ($item) {
            $category['parent_id'] = $item['id'];
            return $category;
        }, $sliceCategoriesData);

        $this->saveCategory($sliceCategoriesData);

        return $sliceCategoriesData;
    }

    protected function getCategoryHtmlFromStream($stream)
    {
        $data = json_decode((string)$stream->getBody(), true);
        if (isset($data['html_fill_sections']) && is_array($data['html_fill_sections'])) {
            return reset($data['html_fill_sections']);
        }

        return '';
    }

    protected function getBuyersGuiHtmlFromStream($stream)
    {
        $data = json_decode((string)$stream->getBody(), true);
        if (isset($data['buyersguidepieces']) && is_array($data['buyersguidepieces'])) {
            return reset($data['buyersguidepieces']);
        }

        return '';
    }

    protected function fetchRequestCategories($data)
    {
        $this->request_count += count($data);
        $rejectedCategoryData = [];
        $successCategoryData = [];
        $result = $this->grabber->getAsyncChildCategories($data);
        foreach ($result as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    $uid = isset($item['uid']) ? $item['uid'] : $item['title'];
                    return $uid == $key;
                });
            } else {
                $successCategoryData[$key] = $responseArr;
            }
        }

        return [
            'success' => $successCategoryData,
            'rejected' => $rejectedCategoryData,
        ];
    }

    protected function fetchChildCategories(array $data)
    {
        Log::info('start fetching child categories', $data);
        $this->attempts = 0;
        $newAllCategoriesData = [];
        $data = $this->array2Dto1DAndAddUid($data);
        Log::info('start fetching child categories count' . count($data));
        $result = $this->fetchRequestCategories($data);

        if ($this->attempts > $this->max_attempts) {
            throw new GrabberException("Failed fetchMainYearsCategories. attempts > $this->attempts");
            Log::critical('Faeil max_attempts', $data);
        }

        do {
            $this->attempts++;
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching main categories. attempts > $this->attempts");
            }
            $rez = $this->fetchRequestCategories($result['rejected']);
            $result['rejected'] = $rez['rejected'];
            $result['success'] = array_replace($result['success'], $rez['success']);
            Log::info(' fetching child categories rejected count' . count($result['rejected']));
        } while (count($result['rejected']));

        foreach ($result['success'] as $key => $responseArr) {
            $html = $this->getCategoryHtmlFromStream($responseArr['value']);
            $item = Arr::first($data, function ($item) use ($key) {
                return $item['uid'] == $key;
            });
            $parser = new ParserService($html);
            if ($parser->isFinalCategory()) {

                $detailsData = $parser->getDetails();

                foreach ($detailsData as $i => $detail) {
                    $detailsData[$i]['category_id'] = $item['id'];
                    $detailsData[$i]['currency_id'] = $this->currency_id;
                }
                $this->detailsData[] = $detailsData;
            } else {
                $categories = $parser->getAllChildCategoriesWithJns();
                unset($parser);

                $categories = array_map(function ($category) use ($item) {
                    $category['parent_id'] = $item['id'];
                    return $category;
                }, $categories);
                $this->saveCategory($categories);
                $newAllCategoriesData[] = $categories;
            }

        }

        Log::info('finish fetching child categories', $newAllCategoriesData);
        if (count($newAllCategoriesData) > 0) {
            $this->fetchChildCategories($newAllCategoriesData);
        }
    }

    protected function saveCategory(&$data)
    {
        foreach ($data as $key => $item) {
            $categoryData = ['title' => $item['title']];
            if (isset($item['parent_id'])) {
                $categoryData['parent_id'] = $item['parent_id'];
            }
            if (isset($item['jsn'])) {
                $categoryData['jsn'] = $item['jsn'];
//                $categoryData['jsn'] = json_encode($item['jsn']);
            }
            $category = Category::firstOrCreate($categoryData);
            $data[$key]['id'] = $category->id;
        }
    }

    protected function saveDetails(array $detailsData)
    {
        Log::info('Saving details',$detailsData);
        $result = Detail::upsert($detailsData, ['title', 'category_id'], [
            'price',
            'short_description',
            's_number',
            'price',
            'partkey'
        ]);
        if ($result) {
            $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_SUCCESS;
            $this->parsingSetting->detail_parsing_at = Carbon::now();
            $this->parsingSetting->save();
        }
        return $result;
    }

    protected function getDetails($detailsData)
    {
        $categoryIds = array_map(function ($details) {
            if (isset($details[0])) {
                return $details[0]['category_id'];
            }
            return 0;
        }, $detailsData);

        return Detail::whereIn('category_id', $categoryIds)->where('is_parsing_analogy_details', false)->get();
    }

    protected function fetchAnalogyDetails($details)
    {
        Log::info('start fetching Analogy Details', $details->toArray());
        $this->attempts = 0;
        $analogyDetails = [];
        $data = $details->toArray();
        Log::info('start fetching Analogy Details count' . count($data));
        $result = $this->fetchRequestAnalogyDetails($data);
        if ($this->attempts > $this->max_attempts) {
            throw new GrabberException("Failed fetchAnalogyDetails. attempts > $this->attempts");
            Log::critical('Faeil max_attempts', $data);
        }

        do {
            $this->attempts++;
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching AnalogyDetails. attempts > $this->attempts");
            }
            $rez = $this->fetchRequestAnalogyDetails($result['rejected']);
            $result['rejected'] = $rez['rejected'];
            $result['success'] = array_replace($result['success'], $rez['success']);
            Log::info(' fetching child categories rejected count' . count($result['rejected']));
        } while (count($result['rejected']));

        foreach ($result['success'] as $key => $responseArr) {
            $html = $this->getBuyersGuiHtmlFromStream($responseArr['value']);
            $item = Arr::first($data, function ($item) use ($key) {
                return $item['id'] == $key;
            });

            $parser = new ParserService($html);

            $detailsData = $parser->getAnalogyDetails();

            foreach ($detailsData as $i => $detail) {
                $detailsData[$i]['detail_id'] = $item['id'];
            }
            $analogyDetails[] = $detailsData;
        }

        return $this->array2Dto1DAndAddUid($analogyDetails, false);
    }

    protected function fetchRequestAnalogyDetails($data)
    {
        $this->request_count += count($data);
        $rejectedCategoryData = [];
        $successCategoryData = [];
        $result = $this->grabber->getAsyncDetailsBuyersGuid($data);

        foreach ($result as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    return $item['id'] == $key;
                });
            } else {
                $successCategoryData[$key] = $responseArr;
            }
        }

        return [
            'success' => $successCategoryData,
            'rejected' => $rejectedCategoryData,
        ];
    }

    private function saveAnalogyDetails(array $analogyDetailsData)
    {
        return DetailAnalogue::upsert($analogyDetailsData, []);
    }
}
