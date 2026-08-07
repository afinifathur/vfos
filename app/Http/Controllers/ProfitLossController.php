<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Finance\Kernel\FinancialKernelService;

class ProfitLossController extends Controller
{
    protected FinancialKernelService $kernel;

    public function __construct(FinancialKernelService $kernel)
    {
        $this->kernel = $kernel;
    }

    public function index(Request $request)
    {
        $userId = auth()->id();
        
        $month = (int) $request->query('month', now()->month);
        $year  = (int) $request->query('year', now()->year);
        $owner = $request->query('owner', 'all');

        // Compile Profit & Loss data through the FinancialKernelService
        $data = $this->kernel->getProfitLoss($month, $year, $owner, $userId);

        $incomeItems  = $data['incomeItems'];
        $expenseItems = $data['expenseItems'];
        $totalIncome  = $data['totalIncome'];
        $totalExpense = $data['totalExpense'];
        $netProfit    = $data['netProfit'];
        $profitMargin = $data['profitMargin'];

        // Generate months for dropdown
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = date('F', mktime(0, 0, 0, $m, 1));
        }

        $years = range(now()->year - 3, now()->year + 1);

        return view('profit-loss.index', compact(
            'month', 'year', 'owner', 'months', 'years',
            'incomeItems', 'expenseItems',
            'totalIncome', 'totalExpense',
            'netProfit', 'profitMargin'
        ));
    }

    public function pdf(Request $request)
    {
        $userId = auth()->id();
        
        $month = (int) $request->query('month', now()->month);
        $year  = (int) $request->query('year', now()->year);
        $owner = $request->query('owner', 'all');

        // Compile Profit & Loss data through the FinancialKernelService
        $data = $this->kernel->getProfitLoss($month, $year, $owner, $userId);

        $incomeItems  = $data['incomeItems'];
        $expenseItems = $data['expenseItems'];
        $totalIncome  = $data['totalIncome'];
        $totalExpense = $data['totalExpense'];
        $netProfit    = $data['netProfit'];
        $profitMargin = $data['profitMargin'];

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = date('F', mktime(0, 0, 0, $m, 1));
        }

        $user = auth()->user();

        return view('profit-loss-pdf', compact(
            'month', 'year', 'owner', 'months', 'user',
            'incomeItems', 'expenseItems',
            'totalIncome', 'totalExpense',
            'netProfit', 'profitMargin'
        ));
    }
}
