<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campos para el rol de inspector y mejora la tabla users
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Modificar user_type para incluir inspector
            // Nota: MySQL no permite modificar ENUM fácilmente, usar raw SQL
        });

        // Modificar el ENUM para agregar 'inspector'
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM('user', 'dealer', 'admin', 'inspector', 'staff') DEFAULT 'user'");

        Schema::table('users', function (Blueprint $table) {
            // Campos para inspectores
            $table->string('inspector_license')->nullable()->after('zones');
            $table->date('inspector_certification_date')->nullable()->after('inspector_license');
            $table->boolean('inspector_active')->default(false)->after('inspector_certification_date');

            // Avatar y preferencias
            $table->string('avatar')->nullable()->after('inspector_active');
            $table->json('preferences')->nullable()->after('avatar');

            // Tracking
            $table->timestamp('last_login_at')->nullable()->after('preferences');

            // Soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'inspector_license',
                'inspector_certification_date',
                'inspector_active',
                'avatar',
                'preferences',
                'last_login_at',
                'deleted_at'
            ]);
        });

        DB::statement("ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM('user', 'dealer', 'admin') DEFAULT 'user'");
    }
};
