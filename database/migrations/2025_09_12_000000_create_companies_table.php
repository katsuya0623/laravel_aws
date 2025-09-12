
<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration {

    public function up(): void

    {

        Schema::create('companies', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->string('slug')->unique();

            $table->string('logo_path')->nullable();

            $table->string('location')->nullable();

            $table->text('summary')->nullable();

            $table->boolean('is_published')->default(true)->index();

            $table->timestamp('published_at')->nullable()->index();

            $table->timestamps();

        });

    }



    public function down(): void

    {

        Schema::dropIfExists('companies');

    }

};

