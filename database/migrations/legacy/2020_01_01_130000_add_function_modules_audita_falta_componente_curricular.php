<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddFunctionModulesAuditaFaltaComponenteCurricular extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared(
            file_get_contents(__DIR__ . '/../../sqls/functions/modules.audita_falta_componente_curricular.sql')
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared(
            'DROP FUNCTION modules.audita_falta_componente_curricular();'
        );
    }
}
