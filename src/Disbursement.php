<?php

namespace Duitku\Laravel;

use Duitku\Laravel\Services\Disbursement\CashOut;
use Duitku\Laravel\Services\Disbursement\Clearing;
use Duitku\Laravel\Services\Disbursement\Finance;
use Duitku\Laravel\Services\Disbursement\Transfer;
use Duitku\Laravel\Support\DuitkuConfig;

class Disbursement
{
    public function __construct(
        protected DuitkuConfig $config
    ) {}

    /**
     * Transfer Online Features
     * (Standard Disbursement)
     */
    public function transfer(): Transfer
    {
        return new Transfer($this->config);
    }

    /**
     * Clearing Features
     * (BIFAST, RTGS, LLG, H2H)
     */
    public function clearing(): Clearing
    {
        return new Clearing($this->config);
    }

    /**
     * Cash Out Features
     * (Indomaret / Pos Indonesia)
     */
    public function cashOut(): CashOut
    {
        return new CashOut($this->config);
    }

    /**
     * Finance & Utility Features
     * (Check Status, Balance, List Bank)
     */
    public function finance(): Finance
    {
        return new Finance($this->config);
    }
}
