<?php

namespace dnj\Invoice\Exceptions\Contracts;

trait JsonRender
{
    public function render()
    {
        return response()->json([
                                    'message' => $this->message,
                                    'code' => $this->code,
                                ], 400);
    }
}
