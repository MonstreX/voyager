<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeyToMenuItemsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('menu_items', 'key')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->string('key')->nullable()->unique()->after('title');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('menu_items', 'key')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->dropUnique(['key']);
                $table->dropColumn('key');
            });
        }
    }
}
