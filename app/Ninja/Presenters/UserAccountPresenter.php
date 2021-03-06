<?php namespace App\Ninja\Presenters;

use Utils;
use Laracasts\Presenter\Presenter;

/**
 * Class CompanyPresenter
 */
class UserAccountPresenter extends Presenter
{

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->entity->name ?: trans('texts.untitled_company');
    }

    /**
     * @return string
     */
    public function website()
    {
        return Utils::addHttp($this->entity->website);
    }

    /**
     * @return mixed
     */
    public function currencyCode()
    {
        $currencyId = $this->entity->getCurrencyId();
        $currency = Utils::getFromCache($currencyId, 'currencies');
        return $currency->code;
    }
}