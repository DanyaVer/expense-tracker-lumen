<?php

namespace App\Http\Controllers;

use App\Receipt;
use App\IncomeExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Receipt
 * @authenticated
 */
class ReceiptController extends Controller
{
    /**
     * Get receipts.
     *
     * @queryParam per_page Rows per page (default: 10) Example: 10
     * @queryParam sort_col Column name to sort (default: id) Example: date
     * @queryParam sort_order Column sort order (asc|desc) Example: desc
     * @queryParam search_col Column name to search Example: receipt_number
     * @queryParam search_by Text to search for Example: REC-12345
     *
     * @url /api/v1/receipt
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->validate($request, [
            'per_page'   => 'integer|min:0',
            'sort_col'   => 'string|max:100',
            'sort_order' => 'string|max:4|in:asc,desc',
            'search_col' => 'string|max:100',
            'search_by'  => 'string|max:100',
        ]);

        $query = Receipt::where('created_by', Auth::id())
            ->orderBy(
                $request->get('sort_col') ?: 'id',
                $request->get('sort_order') ?: 'asc'
            );

        if ($request->filled('search_col') && $request->filled('search_by')) {
            $query->where($request->get('search_col'), 'like', '%' . $request->get('search_by') . '%');
        }

        return response()->json(
            $query->paginate($request->get('per_page') ?: 10)
        );
    }

    /**
     * Store a new receipt along with its expenses (optional).
     *
     * @bodyParam date date required Receipt date Example: 2023-02-08
     * @bodyParam receipt_number string required Receipt number Example: REC-12345
     * @bodyParam total double required Total amount Example: 150.50
     * @bodyParam store string required Store name Example: SuperMart
     * @bodyParam expenses array optional List of expenses. Each expense should include:
     * - category_id: integer
     * - spent_on: string (e.g., Expense reason)
     * - remarks: string (optional)
     * - amount: numeric
     * - transaction_date: date/datetime
     * - currency_id: integer
     *
     * @url /api/v1/receipt
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'date'           => 'required|date',
            'receipt_number' => 'required|string|max:255',
            'total'          => 'required|numeric',
            'store'          => 'required|string|max:255',
            'currency_id'    => 'required|integer',
            // Validate expenses array if provided
            'expenses'                   => 'sometimes|array',
            'expenses.*.category_id'     => 'required_with:expenses|integer',
            'expenses.*.spent_on'        => 'required_with:expenses|string|max:100',
            'expenses.*.remarks'         => 'nullable|string|max:200',
            'expenses.*.amount'          => 'required_with:expenses|numeric',
            'expenses.*.transaction_date'=> 'required_with:expenses|date',
            'expenses.*.currency_id'     => 'required_with:expenses|integer',
        ]);

        $receipt = new Receipt();
        $receipt->date = $request->date;
        $receipt->receipt_number = $request->receipt_number;
        $receipt->total = $request->total;
        $receipt->store = $request->store;
        $receipt->currency_id = $request->currency_id;
        $receipt->created_by = Auth::id();
        $receipt->save();

        // If expenses are provided, create each expense and associate it with the receipt.
        if ($request->has('expenses') && is_array($request->expenses)) {
            foreach ($request->expenses as $expenseData) {
                $expense = new IncomeExpense();
                $expense->category_id = $expenseData['category_id'];
                $expense->spent_on = $expenseData['spent_on'];
                $expense->remarks = $expenseData['remarks'] ?? '';
                $expense->amount = $expenseData['amount'];
                $expense->transaction_date = $expenseData['transaction_date'];
                $expense->transaction_type = 'Expense';
                $expense->currency_id = $expenseData['currency_id'];
                $expense->created_by = Auth::id();
                $expense->receipt_id = $receipt->id;
                $expense->save();
            }
        }

        // Return the new receipt along with its expenses.
        return response()->json(['data' => 'receipt_added', 'receipt' => $receipt->load('expenses')], 201);
    }

    /**
     * Show receipt details along with expenses.
     *
     * @urlParam id required Receipt id to show Example: 1
     *
     * @url /api/v1/receipt/{id}
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $receipt = Receipt::with('expenses')->findOrFail($id);

        // Ensure the receipt belongs to the authenticated user.
        if ($receipt->created_by !== Auth::id()) {
            return response()->json(['error' => 'unauthorised'], 403);
        }

        return response()->json($receipt);
    }

    /**
     * Update receipt details (expenses are not updated here).
     *
     * @urlParam id required Receipt id to update Example: 1
     * @bodyParam date date required Receipt date Example: 2023-02-08
     * @bodyParam receipt_number string required Receipt number Example: REC-12345
     * @bodyParam total double required Total amount Example: 150.50
     * @bodyParam store string required Store name Example: SuperMart
     *
     * @url /api/v1/receipt/{id}
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'date'           => 'required|date',
            'receipt_number' => 'required|string|max:255',
            'total'          => 'required|numeric',
            'store'          => 'required|string|max:255',
            'currency_id'    => 'required|integer',
        ]);

        $receipt = Receipt::findOrFail($id);

        if ($receipt->created_by !== Auth::id()) {
            return response()->json(['error' => 'unauthorised'], 403);
        }

        $receipt->date = $request->date;
        $receipt->receipt_number = $request->receipt_number;
        $receipt->total = $request->total;
        $receipt->store = $request->store;
        $receipt->currency_id = $request->currency_id;
        $receipt->updated_by = Auth::id();
        $receipt->save();

        return response()->json(['data' => 'receipt_updated', 'receipt' => $receipt->load('expenses')], 200);
    }

    /**
     * Delete a receipt.
     *
     * @urlParam id required Receipt id to delete Example: 1
     *
     * @url /api/v1/receipt/{id}
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $receipt = Receipt::findOrFail($id);

        if ($receipt->created_by !== Auth::id()) {
            return response()->json(['error' => 'unauthorised'], 403);
        }

        // Optionally, you can delete associated expenses:
        // $receipt->expenses()->delete();

        $receipt->delete();
        return response()->json(['data' => 'receipt_deleted'], 200);
    }
}
