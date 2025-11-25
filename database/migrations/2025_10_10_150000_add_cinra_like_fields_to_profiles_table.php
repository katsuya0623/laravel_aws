<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profiles')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            // 氏名 / カナ
            if (! Schema::hasColumn('profiles', 'last_name')) {
                $table->string('last_name')->nullable()->after('display_name');
            }
            if (! Schema::hasColumn('profiles', 'first_name')) {
                $table->string('first_name')->nullable()->after('last_name');
            }
            if (! Schema::hasColumn('profiles', 'last_name_kana')) {
                $table->string('last_name_kana')->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('profiles', 'first_name_kana')) {
                $table->string('first_name_kana')->nullable()->after('last_name_kana');
            }

            // 基本
            if (! Schema::hasColumn('profiles', 'gender')) {
                $table->string('gender', 20)->nullable()->after('first_name_kana');
            }
            if (! Schema::hasColumn('profiles', 'phone')) {
                $table->string('phone', 50)->nullable()->after('gender');
            }

            // 住所
            if (! Schema::hasColumn('profiles', 'postal_code')) {
                $table->string('postal_code', 16)->nullable()->after('location');
            }
            if (! Schema::hasColumn('profiles', 'prefecture')) {
                $table->string('prefecture')->nullable()->after('postal_code');
            }
            if (! Schema::hasColumn('profiles', 'city')) {
                $table->string('city')->nullable()->after('prefecture');
            }
            if (! Schema::hasColumn('profiles', 'address1')) {
                $table->string('address1')->nullable()->after('city');
            }
            if (! Schema::hasColumn('profiles', 'address2')) {
                $table->string('address2')->nullable()->after('address1');
            }
            if (! Schema::hasColumn('profiles', 'nearest_station')) {
                $table->string('nearest_station')->nullable()->after('address2');
            }

            // URL / SNS
            if (! Schema::hasColumn('profiles', 'portfolio_url')) {
                $table->string('portfolio_url')->nullable()->after('website_url');
            }
            if (! Schema::hasColumn('profiles', 'sns_x')) {
                $table->string('sns_x')->nullable()->after('x_url');
            }
            if (! Schema::hasColumn('profiles', 'sns_instagram')) {
                $table->string('sns_instagram')->nullable()->after('instagram_url');
            }

            // JSON ブロック
            if (! Schema::hasColumn('profiles', 'educations')) {
                $table->json('educations')->nullable(); // after('birthday') は省略（順番は実質影響なし）
            }
            if (! Schema::hasColumn('profiles', 'work_histories')) {
                $table->json('work_histories')->nullable();
            }
            if (! Schema::hasColumn('profiles', 'skills')) {
                $table->json('skills')->nullable();
            }
            if (! Schema::hasColumn('profiles', 'desired')) {
                $table->json('desired')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('profiles')) {
            return;
        }

        $columns = [
            'last_name', 'first_name', 'last_name_kana', 'first_name_kana',
            'gender', 'phone',
            'postal_code', 'prefecture', 'city', 'address1', 'address2', 'nearest_station',
            'portfolio_url', 'sns_x', 'sns_instagram',
            'educations', 'work_histories', 'skills', 'desired',
        ];

        Schema::table('profiles', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn('profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
