<?php namespace App\Ninja\Import\CSV;

use App\Ninja\Import\BaseTransformer;
use League\Fractal\Resource\Item;

/**
 * Class PaymentTransformer
 */
class PaymentTransformer extends BaseTransformer
{
    /**
     * @param $data
     * @return Item
     */
    public function transform($data)
    {
        return new Item($data, function ($data) {
            return [
                'amount' => $data->paid,
                'payment_date_sql' => isset($data->invoice_date) ? $data->invoice_date : null,
                'relation_id' => $data->relation_id,
                'invoice_id' => $data->invoice_id,
            ];
        });
    }
}