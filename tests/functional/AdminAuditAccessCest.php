<?php

class AdminAuditAccessCest
{
    public function guestCannotOpenAuditTrail(FunctionalTester $I): void
    {
        $I->amOnRoute('admin-audit-log/index');
        $I->seeInCurrentUrl('site%2Flogin');
    }
}
