<?php

namespace App\Http\Controllers;

use App\IncomeExpense;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Summary
 * @authenticated
 */
class ReportController extends Controller
{
    /**
     * Current & last month expenses
     *
     * @url /api/v1/report/expense/months/summary
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function monthlyExpenseSummary()
    {

        $dateTime = new DateTime();

        $userExpenses = IncomeExpense::join('currencies', 'currencies.id', 'income_expenses.currency_id')
            ->where('income_expenses.transaction_type', 'Expense')
            ->where('income_expenses.created_by', Auth::id())
            ->select(
                'currencies.currency_code',
                'currencies.currency_name',
                DB::raw('SUM(income_expenses.amount) AS total'),
                DB::raw("FORMAT(income_expenses.transaction_date, 'yyyy-MM') AS expense_month")
            )
            ->groupBy(
                'currency_id', 
                'currencies.currency_code',
                'currencies.currency_name',
                DB::raw("FORMAT(income_expenses.transaction_date, 'yyyy-MM')")
            )
            ->get();


        $expenseThisMonth = $userExpenses->where('expense_month', $dateTime->format('Y-m'));

        $expenseLastMonth = $userExpenses->where('expense_month', $dateTime->modify('last day of last month')->format('Y-m'));

        return response()->json(['data' => [
            'expense_this_month' => $expenseThisMonth,
            'expense_last_month' => $expenseLastMonth
        ]], 200);

    }

    /**
     * Current & last month incomes
     *
     * @url /api/v1/report/income/months/summary
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function monthlyIncomeSummary()
{
    try {
        $dateTime = new DateTime();

        $userIncomes = IncomeExpense::join('currencies', 'currencies.id', 'income_expenses.currency_id')
            ->where('income_expenses.transaction_type', 'Income')
            ->where('income_expenses.created_by', Auth::id())
            ->select(
                'currencies.currency_code',
                'currencies.currency_name',
                DB::raw('SUM(income_expenses.amount) AS total'),
                DB::raw("FORMAT(income_expenses.transaction_date, 'yyyy-MM') AS income_month")
            )
            ->groupBy(
                'currency_id', 
                'currencies.currency_code', 
                'currencies.currency_name', 
                DB::raw("FORMAT(income_expenses.transaction_date, 'yyyy-MM')")
            )
            ->get();

        $incomeThisMonth = $userIncomes->where('income_month', $dateTime->format('Y-m'));

        $incomeLastMonth = $userIncomes->where(
            'income_month',
            $dateTime->modify('last day of last month')->format('Y-m')
        );

        return response()->json([
            'data' => [
                'income_this_month' => $incomeThisMonth,
                'income_last_month' => $incomeLastMonth
            ]
        ], 200);

    } catch (\Exception $e) {
        // Log or display the exact error message
        \Log::error($e->getMessage());
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Get all transactions
     *
     * @url /api/v1/report/transaction
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function transaction()
    {

        $userIncomeExpenses = IncomeExpense::join('currencies', 'currencies.id', 'income_expenses.currency_id')
            ->where('income_expenses.created_by', Auth::id())
            ->select(
                'income_expenses.transaction_type',
                'income_expenses.transaction_date',
                'currencies.currency_code',
                'currencies.currency_name',
                DB::raw('SUM(income_expenses.amount) AS total'),
                DB::raw("FORMAT(income_expenses.transaction_date, 'yyyy-MM-dd') AS formatted_date")
            )
            ->groupBy(
                'currency_id', 
                'income_expenses.transaction_type',
                'income_expenses.transaction_date',
                'currencies.currency_code',
                'currencies.currency_name',
                DB::raw("FORMAT(income_expenses.transaction_date, 'yyyy-MM-dd')")
            )
            ->get();

        return response()->json(['transactions' => $userIncomeExpenses], 200);

    }
}
