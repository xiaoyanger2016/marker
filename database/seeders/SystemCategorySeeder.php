<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * 8 大类系统预设分类 (user_id = null 表示全局共享)
 *
 * 注意：跟 Place::PLACE_TYPES 是同名但不同维度
 *   - place_type: 物理属性 (这是个玩水点)
 *   - category:   组织维度 (归到「玩水」分类)
 */
class SystemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => '自驾',     'slug' => 'self_drive',     'icon' => 'truck',      'color' => '#114B5F', 'description' => '公路旅行相关', 'sort' => 1],
            ['name' => '玩水',     'slug' => 'play_water',     'icon' => 'waves',      'color' => '#0D3A4A', 'description' => '可下水戏水的地点', 'sort' => 2],
            ['name' => '徒步',     'slug' => 'hiking',         'icon' => 'mountain',   'color' => '#2D5F3F', 'description' => '行走探索的路径', 'sort' => 3],
            ['name' => '桨板',     'slug' => 'paddle',         'icon' => 'sailboat',   'color' => '#0D5C5C', 'description' => '桨板 / SUP 水域', 'sort' => 4],
            ['name' => '拍照',     'slug' => 'photo',          'icon' => 'camera',     'color' => '#A1461E', 'description' => '出片取景地', 'sort' => 5],
            ['name' => '美食',     'slug' => 'food',           'icon' => 'utensils',   'color' => '#C45626', 'description' => '值得专程去吃的店', 'sort' => 6],
            ['name' => '露营',     'slug' => 'camping',        'icon' => 'tent',       'color' => '#1A3A3A', 'description' => '可以过夜的营地', 'sort' => 7],
            ['name' => '日出日落', 'slug' => 'sunrise_sunset', 'icon' => 'sun',        'color' => '#7A4A1A', 'description' => '专门看日出日落的位置', 'sort' => 8],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['user_id' => null, 'slug' => $cat['slug']],
                array_merge($cat, ['user_id' => null, 'is_active' => true, 'parent_id' => null]),
            );
        }
    }
}
