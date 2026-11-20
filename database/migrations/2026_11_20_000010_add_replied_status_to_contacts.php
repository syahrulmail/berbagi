<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRepliedStatusToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `contacts` MODIFY `status` ENUM('prospect', 'contacted', 'replied', 'donated', 'churned') NOT NULL DEFAULT 'prospect'");

            return;
        }

        if ($driver === 'sqlite') {
            $this->rebuildSqliteContacts(true);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::table('contacts')->where('status', 'replied')->update(['status' => 'contacted']);
            $this->rebuildSqliteContacts(false);

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::table('contacts')->where('status', 'replied')->update(['status' => 'contacted']);
            DB::statement("ALTER TABLE `contacts` MODIFY `status` ENUM('prospect', 'contacted', 'donated', 'churned') NOT NULL DEFAULT 'prospect'");
        }
    }

    protected function rebuildSqliteContacts(bool $withReplied): void
    {
        $statuses = $withReplied
            ? "'prospect', 'contacted', 'replied', 'donated', 'churned'"
            : "'prospect', 'contacted', 'donated', 'churned'";

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE "contacts_tmp" (
            "id" integer not null primary key autoincrement,
            "name" varchar not null,
            "phone" varchar not null,
            "status" varchar check ("status" in (' . $statuses . ')) not null default \'prospect\',
            "agen_id" integer,
            "branch_id" integer,
            "notes" text,
            "created_at" datetime,
            "updated_at" datetime,
            "followup_count" integer not null default \'0\',
            "last_messaged_at" datetime,
            "wa_valid" tinyint(1),
            foreign key("agen_id") references "users"("id") on delete set null,
            foreign key("branch_id") references "branches"("id") on delete set null
        )');

        DB::statement('INSERT INTO "contacts_tmp" ("id", "name", "phone", "status", "agen_id", "branch_id", "notes", "created_at", "updated_at", "followup_count", "last_messaged_at", "wa_valid")
            SELECT "id", "name", "phone", "status", "agen_id", "branch_id", "notes", "created_at", "updated_at", "followup_count", "last_messaged_at", "wa_valid" FROM "contacts"');

        DB::statement('DROP TABLE "contacts"');
        DB::statement('ALTER TABLE "contacts_tmp" RENAME TO "contacts"');
        DB::statement('CREATE INDEX "contacts_agen_id_status_index" on "contacts" ("agen_id", "status")');
        DB::statement('CREATE INDEX "contacts_followup_count_index" on "contacts" ("followup_count")');

        DB::statement('PRAGMA foreign_keys = ON');
    }
}
