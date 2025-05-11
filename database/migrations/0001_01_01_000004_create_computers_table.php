<?php

declare(strict_types=1);

use App\Enums\ComputerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('computers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('mac_address', 25)->unique();
            $table->string('hostname');
            $table->integer('pos_row');
            $table->integer('pos_col');
            $table->string('status')->default(ComputerStatus::OFFLINE);
            $table->timestampTz('last_heartbeat_at')->nullable();
            $table->timestampsTz();
        });

        // Create a trigger function to validate computer positions
        \DB::unprepared('
            CREATE OR REPLACE FUNCTION validate_computer_position()
            RETURNS TRIGGER AS $$
            DECLARE
                max_rows INTEGER;
                max_cols INTEGER;
            BEGIN
                SELECT grid_rows, grid_cols INTO max_rows, max_cols
                FROM rooms
                WHERE id = NEW.room_id;

                IF NEW.pos_row > max_rows OR NEW.pos_col > max_cols THEN
                    RAISE EXCEPTION \'Computer position (%, %) exceeds room dimensions (%, %)\',
                        NEW.pos_row, NEW.pos_col, max_rows, max_cols;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER check_computer_position
            BEFORE INSERT OR UPDATE ON computers
            FOR EACH ROW EXECUTE FUNCTION validate_computer_position();
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('computers');
    }
};
