<?php

use Illuminate\Database\Seeder;

class SimpleOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        try {
            \DB::table('orders')->insert(array(
                array(
                    'id' => 196,
                    'name' => 'simpleOrder.index',
                    'guard_name' => 'web',
                    'created_at' => '2020-08-23 14:58:02',
                    'updated_at' => '2020-08-23 14:58:02',
                    'deleted_at' => NULL,
                ),
            ));

            \DB::table('role_has_permissions')->insert(array(
                array(
                    'permission_id' => 196,
                    'role_id' => 2,
                )
            ));
        } catch (Exception $exception){}
    }
}
