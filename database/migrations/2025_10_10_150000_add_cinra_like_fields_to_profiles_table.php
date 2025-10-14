<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // 氏名/カナ
            $table->string('last_name')->nullable()->after('display_name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('last_name_kana')->nullable()->after('first_name');
            $table->string('first_name_kana')->nullable()->after('last_name_kana');

            // 基本
            $table->string('gender', 20)->nullable()->after('first_name_kana');
            $table->string('phone', 50)->nullable()->after('gender');

            // 住所
            $table->string('postal_code', 16)->nullable()->after('location');
            $table->string('prefecture')->nullable()->after('postal_code');
            $table->string('city')->nullable()->after('prefecture');
            $table->string('address1')->nullable()->after('city');
            $table->string('address2')->nullable()->after('address1');
            $table->string('nearest_station')->nullable()->after('address2');

            // URL / SNS
            $table->string('portfolio_url')->nullable()->after('website_url');
            $table->string('sns_x')->nullable()->after('x_url');
            $table->string('sns_instagram')->nullable()->after('instagram_url');

            // JSON ブロック
            $table->json('educations')->nullable()->after('birthday');
            $table->json('work_histories')->nullable()->after('educations');
            $table->json('skills')->nullable()->after('work_histories');
            $table->json('desired')->nullable()->after('skills');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'last_name','first_name','last_name_kana','first_name_kana',
                'gender','phone',
                'postal_code','prefecture','city','address1','address2','nearest_station',
                'portfolio_url','sns_x','sns_instagram',
                'educations','work_histories','skills','desired',
            ]);
        });
    }
};
