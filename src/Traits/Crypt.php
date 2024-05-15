<?php
namespace Budgetcontrol\Authtentication\Traits;

use Illuminate\Support\Facades\Crypt as FacadesCrypt;

trait Crypt
{
    public function encrypt(string $data): string
    {
        return FacadesCrypt::encrypt($data);
    }

    public function decrypt(string $data): string
    {
        return FacadesCrypt::decrypt($data);
    }
}