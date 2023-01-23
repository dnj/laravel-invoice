<?php

namespace dnj\Invoice;

class AccountLocator
{
	/**
	 * @var array<int,int>
	 */
    protected array $expenseAccounts = [];

    public function setExpenseAccountId(int $currencyId, int $accountId): void
    {
        $this->expenseAccounts[$currencyId] = $accountId;
    }

    public function getExpenseAccountId(int $currencyId): int
    {
        if (!isset($this->expenseAccounts[$currencyId])) {
            $id = config('invoice.accounts.expense.' . $currencyId);
            if (!$id or !is_int($id)) {
                throw new \Exception("needs to set accounts.expense.{$currencyId} in config file: invoice.php");
            }
            $this->expenseAccounts[$currencyId] = $id;
        }

        return $this->expenseAccounts[$currencyId];
    }
}
