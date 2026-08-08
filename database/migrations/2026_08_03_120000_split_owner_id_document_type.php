<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The single "Owner ID" document slot was split into front and back of the
 * driver's license. Existing uploads were the front of the license, so they
 * are remapped to owner_id_front — otherwise every previously-completed case
 * would suddenly report a missing required document.
 *
 * Cases created before this migration will still show owner_id_back as
 * missing, which is correct: that file was never collected.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('uploaded_files')
            ->where('document_type', 'owner_id')
            ->update(['document_type' => 'owner_id_front']);
    }

    public function down(): void
    {
        // Back-of-license uploads have no pre-split equivalent. Dropping them
        // would lose files that exist on disk, so they are left untouched and
        // only the front is folded back into the original slot.
        DB::table('uploaded_files')
            ->where('document_type', 'owner_id_front')
            ->update(['document_type' => 'owner_id']);
    }
};
