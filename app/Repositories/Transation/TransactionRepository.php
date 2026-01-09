<?php
namespace App\Repositories\Transation;

use App\Models\Transation\Transaction;
use App\Repositories\AbstractRepository;

class TransactionRepository extends AbstractRepository
{
    public function __construct(Transaction $model)
    {
        parent::__construct($model);
    }
}