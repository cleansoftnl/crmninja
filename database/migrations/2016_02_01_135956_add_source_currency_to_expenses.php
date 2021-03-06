<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSourceCurrencyToExpenses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {

            $table->dropColumn('foreign_amount');

            if (Schema::hasColumn('expenses', 'currency_id')) {
                $table->unsignedInteger('currency_id')->nullable(false)->change();
                $table->renameColumn('currency_id', 'invoice_currency_id');
            }

            $table->unsignedInteger('expense_currency_id');
        });

        Schema::table('expenses', function (Blueprint $table) {

            // set loginaccount value so we're able to create foreign constraint
            DB::statement('update expenses e
                            left join companies a on a.id = e.company_id
                            set e.expense_currency_id = COALESCE(a.currency_id, 1)');

            $table->foreign('invoice_currency_id', 'fk_expenses_invoicecurrency')->references('id')->on('currencies');
            $table->foreign('expense_currency_id', 'fk_expenses_expensecurrency')->references('id')->on('currencies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expenses', function ($table) {

        });
    }
}
