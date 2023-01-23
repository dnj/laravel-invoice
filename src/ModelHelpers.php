<?php

namespace dnj\Invoice;

trait ModelHelpers
{
    protected function getUserModel(): ?string
    {
        return config('invoice.user_model');
    }

    protected function getUserTable(): ?string
    {
        $userModel = $this->getUserModel();

        $userTable = null;
        if ($userModel) {
            $userTable = (new $userModel())->getTable();
        }

        return $userTable;
    }

    protected function getFloatScale(): int
    {
        return config('currency.float_scale', 10);
    }
}
