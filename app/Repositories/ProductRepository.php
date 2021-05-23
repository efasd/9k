<?php
/**
 * File name: ProductRepository.php
 * Last modified: 2020.06.07 at 07:02:57
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Repositories;

use App\Models\Product;
use InfyOm\Generator\Common\BaseRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use Prettus\Repository\Traits\CacheableRepository;

/**
 * Class ProductRepository
 * @package App\Repositories
 * @version August 29, 2019, 9:38 pm UTC
 *
 * @method Product findWithoutFail($id, $columns = ['*'])
 * @method Product find($id, $columns = ['*'])
 * @method Product first($columns = ['*'])
 */
class ProductRepository extends BaseRepository implements CacheableInterface
{

    use CacheableRepository;
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'price',
        'discount_price',
        'description',
        'capacity',
        'package_items_count',
        'deliverable',
        'unit',
        'featured',
        'market_id',
        'employee_id',
        'category_id',
        'discount_start_date',
        'discount_end_date',
        'discount_percent'
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Product::class;
    }

    /**
     * get my products
     **/
    public function myProducts()
    {
        return Product::join("user_markets", "user_markets.product_id", "=", "products.market_id")
            ->where('user_markets.user_id', auth()->id())->get();
    }

    /**
     * get my products
     **/
    public function myEmployee()
    {
        return Product::join("employee_product", "employee_product.", "=", "products.id")
            ->where('employee_product.user_id', auth()->id())->get();
    }

    public function groupedByMarkets()
    {
        $products = [];
        foreach ($this->all() as $model) {
            if (!empty($model->market)) {
                $products[$model->market->name][$model->id] = $model->name;
            }
        }
        return $products;
    }

    public function ByProducts()
    {
        $products = [];
        foreach ($this->all() as $model) {
            if (!empty($model->market)) {
                $products[$model->market->name][$model->id] = $model->name;
            }
        }
        return $products;
    }

    public function groupedByEmployee()
    {
        $employee = [];
        foreach ($this->all() as $model) {
            if (!empty($model->employee)) {
                $employee[$model->employee->name][$model->id] = $model->name;
            }
        }
        return $employee;
    }
}
