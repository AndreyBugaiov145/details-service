<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailView extends Migration
{
    public function up()
    {
        DB::statement(<<<SQL
            CREATE VIEW `unique_details` AS
            SELECT MIN(`details`.`id`) AS `id`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`title` ORDER BY `details`.`id`), ',', 1) AS `title`,
                `details`.`s_number` AS `s_number`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`short_description` ORDER BY `details`.`id`), ',', 1) AS `short_description`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`interchange_numbers` ORDER BY `details`.`id`), ',', 1) AS `interchange_numbers`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`price` ORDER BY `details`.`id`), ',', 1) AS `price`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`us_shipping_price` ORDER BY `details`.`id`), ',', 1) AS `us_shipping_price`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`ua_shipping_price` ORDER BY `details`.`id`), ',', 1) AS `ua_shipping_price`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`price_markup` ORDER BY `details`.`id`), ',', 1) AS `price_markup`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`stock` ORDER BY `details`.`id`), ',', 1) AS `stock`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`partkey` ORDER BY `details`.`id`), ',', 1) AS `partkey`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`category_id` ORDER BY `details`.`id`), ',', 1) AS `category_id`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`currency_id` ORDER BY `details`.`id`), ',', 1) AS `currency_id`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`is_parsing_analogy_details` ORDER BY `details`.`id`), ',', 1) AS `is_parsing_analogy_details`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`analogy_details` ORDER BY `details`.`id`), ',', 1) AS `analogy_details`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`is_manual_added` ORDER BY `details`.`id`), ',', 1) AS `is_manual_added`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`created_at` ORDER BY `details`.`id`), ',', 1) AS `created_at`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`updated_at` ORDER BY `details`.`id`), ',', 1) AS `updated_at`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`is_fetched_i_n` ORDER BY `details`.`id`), ',', 1) AS `is_fetched_i_n`,
                SUBSTRING_INDEX(GROUP_CONCAT(`details`.`info_link` ORDER BY `details`.`id`), ',', 1) AS `info_link`
            FROM `details`
            GROUP BY `details`.`s_number`;
        SQL);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement($this->dropView());
    }

    private function dropView(): string
    {
        return <<<SQL
            drop view if exists `unique_details`;
            SQL;
    }
}
