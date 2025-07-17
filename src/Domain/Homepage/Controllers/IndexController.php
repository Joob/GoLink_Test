<?php
namespace Domain\Homepage\Controllers;

use Domain\Localization\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\View\View;
use Domain\Settings\Actions\GetConfigAction;

class IndexController
{
    public function __construct(
        public GetConfigAction $getConfig,
    ) {
    }

    /**
     * Show index page
     */
    public function __invoke(): View
    {
        return view('index')
            ->with('config', json_decode(json_encode(($this->getConfig)())))
            ->with('languages', Cache::remember('language_list', 5*60, function () {
                return Language::query()->get()->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'code' => $item->locale,
                        'flag' => $item->flag,
                    ];
                });
            }));
    }

}

