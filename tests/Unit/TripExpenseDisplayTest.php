<?php

namespace Tests\Unit;

use App\Models\TripExpense;
use App\Models\TripExpenseType;
use PHPUnit\Framework\TestCase;

class TripExpenseDisplayTest extends TestCase
{
    public function test_amount_only_expense_displays_amount_and_dash_hours(): void
    {
        $expense = $this->makeExpense(
            ['amount', 'image'],
            amount: 100,
            hours: null
        );

        $this->assertSame('100.00', $expense->displayAmount());
        $this->assertSame('-', $expense->displayHours());
    }

    public function test_hours_only_expense_displays_dash_amount_and_hours(): void
    {
        $expense = $this->makeExpense(
            ['hours'],
            amount: 0,
            hours: 3.5
        );

        $this->assertSame('-', $expense->displayAmount());
        $this->assertSame('3.50 hrs', $expense->displayHours());
    }

    public function test_amount_and_hours_expense_displays_both(): void
    {
        $expense = $this->makeExpense(
            ['amount', 'hours'],
            amount: 100,
            hours: 2.5
        );

        $this->assertSame('100.00', $expense->displayAmount());
        $this->assertSame('2.50 hrs', $expense->displayHours());
    }

    public function test_legacy_hours_remain_visible_when_type_no_longer_has_hours(): void
    {
        $expense = $this->makeExpense(
            ['amount', 'image'],
            amount: 50,
            hours: 1.25
        );

        $this->assertSame('50.00', $expense->displayAmount());
        $this->assertSame('1.25 hrs', $expense->displayHours());
    }

    public function test_null_hours_display_as_dash(): void
    {
        $expense = $this->makeExpense(
            ['hours'],
            amount: 0,
            hours: null
        );

        $this->assertSame('-', $expense->displayHours());
    }

    private function makeExpense(array $inputTypes, float $amount, ?float $hours): TripExpense
    {
        $type = new TripExpenseType([
            'title' => 'Test Type',
            'input_types' => $inputTypes,
        ]);

        $expense = new TripExpense([
            'amount' => $amount,
            'hours' => $hours,
        ]);
        $expense->setRelation('expenseType', $type);

        return $expense;
    }
}
