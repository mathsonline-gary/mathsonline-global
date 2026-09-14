<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A campaign is membership's only grouping of plans. Ported from membership's 'campaigns'
     * without 'description' and 'tags', which nothing on this side reads.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')
                ->index()
                ->comment('The brand this campaign prices. Campaigns never cross brands.');
            $table->string('code')
                ->index()
                ->comment('The campaign\'s identity within its brand. The brand\'s default campaign is the one coded "DEFAULT" — membership carries no flag for it and the code is the convention.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
